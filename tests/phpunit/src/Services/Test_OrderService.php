<?php declare(strict_types=1);

use MultiSafepay\Exception\InvalidDataInitializationException;
use MultiSafepay\Exception\InvalidTotalAmountException;
use MultiSafepay\WooCommerce\Services\OrderService;
use MultiSafepay\WooCommerce\Services\CustomerService;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\WooCommerce\Services\ShoppingCartService;
use MultiSafepay\WooCommerce\Tests\Fixtures\TaxesFixture;
use MultiSafepay\WooCommerce\Tests\Fixtures\WC_Order_Item_Product_Fixture;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\WooCommerce\Tests\Fixtures\PaymentMethodFixture;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;

/**
 * Covers order request creation, including the Blocks/Store API meta-fallback for Payment Component payload.
 *
 * @covers \MultiSafepay\WooCommerce\Services\OrderService
 */
class Test_OrderService extends WP_UnitTestCase {

    /**
     * @var OrderService
     */
    public $order_service;

    /**
     * @var WC_Order
     */
    public $wc_order;

    /**
     * Holds the Blocks payment data meta returned by WC_Order::get_meta().
     *
     * @var array<string, string>|null
     */
    private $blocks_payment_data_meta;

    /**
     * Snapshot of request globals to avoid cross-test leakage.
     *
     * @var bool
     */
    private $had_rest_route;

    /**
     * Snapshot of request globals to avoid cross-test leakage.
     *
     * @var string
     */
    private $original_rest_route;

    /**
     * Snapshot of request globals to avoid cross-test leakage.
     *
     * @var bool
     */
    private $had_request_uri;

    /**
     * Snapshot of request globals to avoid cross-test leakage.
     *
     * @var string
     */
    private $original_request_uri;

    /**
     * Toggle request globals to emulate Store API or classic checkout context.
     *
     * @param bool $enabled
     * @return void
     */
    private function set_store_api_context( bool $enabled ): void {
        if ( $enabled ) {
            $_GET['rest_route']    = '/wc/store/v1/checkout';
            $_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/checkout';
            return;
        }

        unset( $_GET['rest_route'] );
        $_SERVER['REQUEST_URI'] = '/checkout/';
    }

    /**
     * @throws InvalidDataInitializationException
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function set_up() {
        $this->had_rest_route    = isset( $_GET['rest_route'] );
        $this->original_rest_route = $this->had_rest_route ? (string) $_GET['rest_route'] : '';
        $this->had_request_uri   = isset( $_SERVER['REQUEST_URI'] );
        $this->original_request_uri = $this->had_request_uri ? (string) $_SERVER['REQUEST_URI'] : '';

        // Initialize WooCommerce session and customer if not set
        if ( ! WC()->session ) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }

        // Added a dummy WC_Customer to ensure WC()->customer is not null.
        if ( null === WC()->customer ) {
            $wc_customer = $this->getMockBuilder('WC_Customer')
                ->disableOriginalConstructor()
                ->setMethods(['get_base_country'])
                ->getMock();
            $wc_customer->method('get_base_country')->willReturn('NL');
            WC()->customer = $wc_customer;
        }

        update_option( 'woocommerce_calc_taxes', 'yes');

        $this->order_service = new OrderService();

        $this->wc_order = $this->getMockBuilder(WC_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'get_order_number',
                    'get_total',
                    'get_currency',
                    'get_payment_method',
                    'get_meta',
                    'get_id',
                    'get_billing_address_1',
                    'get_billing_address_2',
                    'get_billing_country',
                    'get_billing_state',
                    'get_billing_city',
                    'get_billing_postcode',
                    'get_billing_email',
                    'get_billing_phone',
                    'get_billing_first_name',
                    'get_billing_last_name',
                    'get_billing_company',
                    'get_shipping_address_1',
                    'get_shipping_address_2',
                    'get_shipping_country',
                    'get_shipping_state',
                    'get_shipping_city',
                    'get_shipping_first_name',
                    'get_shipping_last_name',
                    'get_shipping_company',
                    'get_shipping_postcode',
                    'get_cancel_order_url',
                    'get_checkout_order_received_url',
                    'get_checkout_payment_url',
                    'get_customer_ip_address',
                    'get_customer_user_agent',
                    'get_shipping_total',
                    'get_shipping_tax',
                    'get_items',
                    'needs_shipping_address',
                    'has_shipping_address'
                )
            )->getMock();
        $this->wc_order->method('get_order_number')->will($this->returnValue('123'));
        $this->wc_order->method('get_total')->will($this->returnValue('10.00'));
        $this->wc_order->method('get_currency')->will($this->returnValue('EUR'));
        $this->wc_order->method('get_payment_method')->will($this->returnValue('multisafepay_amex'));
        $this->blocks_payment_data_meta = array();
        $this->wc_order->method( 'get_meta' )
            ->willReturnCallback(
                function ( $key ) {
                    if ( '_multisafepay_blocks_payment_data' === $key ) {
                        return $this->blocks_payment_data_meta;
                    }

                    return array();
                }
            );
        $this->wc_order->method('get_id')->will($this->returnValue(5));
        $this->wc_order->method('get_billing_address_1')->will($this->returnValue('Kraanspoor'));
        $this->wc_order->method('get_billing_address_2')->will($this->returnValue('39C'));
        $this->wc_order->method('get_billing_country')->will($this->returnValue('NL'));
        $this->wc_order->method('get_billing_state')->will($this->returnValue(''));
        $this->wc_order->method('get_billing_city')->will($this->returnValue('Amsterdam'));
        $this->wc_order->method('get_billing_postcode')->will($this->returnValue('1033 SC'));
        $this->wc_order->method('get_billing_email')->will($this->returnValue('john.doe@multisafepay.com'));
        $this->wc_order->method('get_billing_phone')->will($this->returnValue('123456789'));
        $this->wc_order->method('get_billing_first_name')->will($this->returnValue('John'));
        $this->wc_order->method('get_billing_last_name')->will($this->returnValue('Doe'));
        $this->wc_order->method('get_billing_company')->will($this->returnValue('MultiSafepay'));
        $this->wc_order->method('get_customer_ip_address')->will($this->returnValue('127.0.0.1'));
        $this->wc_order->method('get_customer_user_agent')->will($this->returnValue('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.121 Safari/537.36'));
        $this->wc_order->method('get_shipping_address_1')->will($this->returnValue('Kraanspoor'));
        $this->wc_order->method('get_shipping_address_2')->will($this->returnValue('39C'));
        $this->wc_order->method('get_shipping_country')->will($this->returnValue('NL'));
        $this->wc_order->method('get_shipping_state')->will($this->returnValue(''));
        $this->wc_order->method('get_shipping_city')->will($this->returnValue('Amsterdam'));
        $this->wc_order->method('get_shipping_postcode')->will($this->returnValue('1033 SC'));
        $this->wc_order->method('get_shipping_first_name')->will($this->returnValue('John'));
        $this->wc_order->method('get_shipping_last_name')->will($this->returnValue('Doe'));
        $this->wc_order->method('get_shipping_company')->will($this->returnValue('MultiSafepay'));
        $this->wc_order->method('needs_shipping_address')->willReturn(true);
        $this->wc_order->method('has_shipping_address')->willReturn(true);
        $this->wc_order->method('get_cancel_order_url')->willReturn('https://example.test/cancel');
        $this->wc_order->method('get_checkout_order_received_url')->willReturn('https://example.test/received');
        $this->wc_order->method('get_checkout_payment_url')->willReturn('https://example.test/pay');

        $customer_details = ( new CustomerService() )->create_customer_details( $this->wc_order );
        $delivery_details = ( new CustomerService() )->create_delivery_details( $this->wc_order );

        $customer_service = $this->getMockBuilder(CustomerService::class)
            ->disableOriginalConstructor()
            ->setMethods(['create_customer_details', 'create_delivery_details'])
            ->getMock();

        $customer_service->method('create_customer_details')->willReturn($customer_details);
        $customer_service->method('create_delivery_details')->willReturn($delivery_details);

        $shopping_cart_service = $this->getMockBuilder(ShoppingCartService::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'create_shopping_cart'
            ])
            ->getMock();

        // Set taxes.
        $tax_fixture = new TaxesFixture( 'Tax Rate Name', 21, 'Tax Class Name' );
        $tax_fixture->register_tax_rate();

        // Set Products.
        $wc_order_item_product = (new WC_Order_Item_Product_Fixture( 11, 'Vneck Tshirt', 18.00, 2, 21, 10, sanitize_title('Tax Class Name')))->get_wc_order_item_product_mock(
            $this->wc_order
        );

        // Consecutive calls for WC_Order->get_items()
        $this->wc_order->method( 'get_items' )->withConsecutive( array('line_item'),  array('shipping'), array('fee'), array('coupon') )
            ->willReturnOnConsecutiveCalls( array( $wc_order_item_product ), array(), array(), array() );

        $shopping_cart = ( new ShoppingCartService() )->create_shopping_cart($this->wc_order, 'EUR');

        $payment_method_service = $this->getMockBuilder('PaymentMethodService')
            ->disableOriginalConstructor()
            ->setMethods(['get_woocommerce_payment_gateway_by_multisafepay_gateway_code', 'get_woocommerce_payment_gateway_by_id'])
            ->getMock();

        $payment_method = new PaymentMethod( ( new PaymentMethodFixture() )->get_amex_payment_method_fixture() );
        $woocommerce_payment_gateway = new BasePaymentMethod( $payment_method );
        $payment_method_service->method('get_woocommerce_payment_gateway_by_multisafepay_gateway_code')->willReturn($woocommerce_payment_gateway);
        $payment_method_service->method('get_woocommerce_payment_gateway_by_id')->willReturn($woocommerce_payment_gateway);

        $shopping_cart_service->method('create_shopping_cart')->willReturn($shopping_cart);

        $this->order_service->customer_service = $customer_service;
        $this->order_service->shopping_cart_service = $shopping_cart_service;
        $this->order_service->payment_method_service = $payment_method_service;

    }

    /**
     * Restore request globals modified by tests.
     *
     * @return void
     */
    public function tear_down() {
        if ( $this->had_rest_route ) {
            $_GET['rest_route'] = $this->original_rest_route;
        } else {
            unset( $_GET['rest_route'] );
        }

        if ( $this->had_request_uri ) {
            $_SERVER['REQUEST_URI'] = $this->original_request_uri;
        } else {
            unset( $_SERVER['REQUEST_URI'] );
        }

        parent::tear_down();
    }

    /**
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_order_request() {
        $order_request = $this->order_service->create_order_request( $this->wc_order, 'AMEX', 'redirect');
        $this->assertInstanceOf(OrderRequest::class, $order_request);
        $this->assertEquals('AMEX', $order_request->getGatewayCode());
        $this->assertEquals('redirect', $order_request->getType());

    }

    /**
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_add_none_tax_rate() {
        $order_request = $this->order_service->create_order_request( $this->wc_order, 'AMEX', 'redirect');
        $taxTable = $order_request->getCheckoutOptions()->getTaxTable()->getData();
        $this->assertEquals(0, $taxTable['alternate'][1]['rules'][0]['rate']);
    }

    /**
     * Uses the Blocks-stored order meta-payload when classic checkout POST data is missing.
     *
     * @covers \MultiSafepay\WooCommerce\Services\OrderService::create_order_request
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     * @throws InvalidTotalAmountException
     */
    public function test_create_order_request_uses_blocks_payment_component_payload_when_post_empty() {
        $_POST = array();

        $payment_method_id             = 'multisafepay_amex';
        $payment_component_payload_key = $payment_method_id . '_payment_component_payload';

        $this->wc_order->method( 'get_payment_method' )->willReturn( $payment_method_id );
        $this->blocks_payment_data_meta = array(
            $payment_component_payload_key => 'payload-from-blocks',
        );

        $order_request = $this->order_service->create_order_request( $this->wc_order, 'AMEX', 'redirect' );
        $this->assertEquals( 'direct', $order_request->getType() );

        $data = $order_request->getData();
        $this->assertArrayHasKey( 'payment_data', $data );
        $this->assertIsArray( $data['payment_data'] );
        $this->assertArrayHasKey( 'payload', $data['payment_data'] );
        $this->assertEquals( 'payload-from-blocks', $data['payment_data']['payload'] );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\OrderService::get_wallet_payment_token
     */
    public function test_get_wallet_payment_token_prefers_legacy_post_key(): void {
        $this->set_store_api_context( false );
        $_POST = array(
            'payment_token'                    => 'legacy-request-token',
            'multisafepay_amex_payment_token' => 'method-request-token',
        );

        $token = $this->order_service->get_wallet_payment_token( $this->wc_order );
        $this->assertEquals( 'legacy-request-token', $token );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\OrderService::get_wallet_payment_token
     */
    public function test_get_wallet_payment_token_uses_method_scoped_post_key_when_legacy_missing(): void {
        $this->set_store_api_context( false );
        $_POST = array(
            'multisafepay_amex_payment_token' => 'method-request-token',
        );

        $token = $this->order_service->get_wallet_payment_token( $this->wc_order );
        $this->assertEquals( 'method-request-token', $token );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\OrderService::get_wallet_payment_token
     */
    public function test_get_wallet_payment_token_uses_blocks_method_meta_when_request_is_empty(): void {
        $this->set_store_api_context( false );
        $_POST = array();
        $this->blocks_payment_data_meta = array(
            'multisafepay_amex_payment_token' => 'method-meta-token',
        );

        $token = $this->order_service->get_wallet_payment_token( $this->wc_order );
        $this->assertEquals( 'method-meta-token', $token );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\OrderService::get_wallet_payment_token
     */
    public function test_get_wallet_payment_token_uses_blocks_legacy_meta_fallback(): void {
        $this->set_store_api_context( false );
        $_POST = array();
        $this->blocks_payment_data_meta = array(
            'payment_token' => 'legacy-meta-token',
        );

        $token = $this->order_service->get_wallet_payment_token( $this->wc_order );
        $this->assertEquals( 'legacy-meta-token', $token );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\OrderService::get_wallet_payment_token
     */
    public function test_get_wallet_payment_token_normalizes_blocks_meta_values(): void {
        $this->set_store_api_context( false );
        $_POST = array();

        $raw_token = "\0" . str_repeat( 'x', 20050 );
        $this->blocks_payment_data_meta = array(
            'multisafepay_amex_payment_token' => $raw_token,
        );

        $token = $this->order_service->get_wallet_payment_token( $this->wc_order );

        $this->assertStringNotContainsString( "\0", $token );
        $this->assertEquals( 20000, strlen( $token ) );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\OrderService::get_wallet_payment_token
     */
    public function test_get_wallet_payment_token_store_api_path_keeps_raw_request_payload(): void {
        $this->set_store_api_context( true );
        $_POST = array(
            'payment_token' => '{\\"foo\\":\\"bar\\"}',
        );

        $token = $this->order_service->get_wallet_payment_token( $this->wc_order );
        $this->assertEquals( '{\\"foo\\":\\"bar\\"}', $token );
    }
}
