<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Services\PaymentComponentService;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\Tests\Fixtures\PaymentMethodFixture;

class Test_PaymentComponentService extends WP_UnitTestCase {

    /**
     * @var PaymentComponentService
     */
    public $payment_component_service;

    /**
     * @var PaymentMethod
     */
    public $payment_method;

    /**
     * @var BasePaymentMethod;
     */
    public $woocommerce_payment_gateway;

    /**
     * @var WC_Customer|null
     */
    private $original_wc_customer;

    /**
     * @var bool
     */
    private $did_set_wc_customer = false;


    public function set_up() {
        if ( function_exists( 'WC' ) && ! isset( WC()->cart ) ) {
            WC()->cart = $this->getMockBuilder( 'WC_Cart' )
                ->disableOriginalConstructor()
                ->setMethods( ['get_total'] )
                ->getMock();

            WC()->cart->method( 'get_total' )
                ->willReturn( '10.00' );
        }

        if ( function_exists( 'WC' ) ) {
            $this->original_wc_customer = WC()->customer;

            if ( null === WC()->customer ) {
                WC()->customer = $this->getMockBuilder( 'WC_Customer' )
                    ->disableOriginalConstructor()
                    ->setMethods( array( 'get_billing_country' ) )
                    ->getMock();

                WC()->customer->method( 'get_billing_country' )
                    ->willReturn( 'NL' );

                $this->did_set_wc_customer = true;
            }
        }

        $this->payment_method = new PaymentMethod( ( new PaymentMethodFixture() )->get_amex_payment_method_fixture() );
        $this->woocommerce_payment_gateway = new BasePaymentMethod( $this->payment_method );
        $this->payment_component_service = new PaymentComponentService();

        $sdk_service = $this->getMockBuilder('SdkService')
            ->disableOriginalConstructor()
            ->setMethods(['get_test_mode'])
            ->getMock();

        $api_token_service = $this->getMockBuilder('ApiTokenService')
            ->disableOriginalConstructor()
            ->setMethods(['get_api_token'])
            ->getMock();

        $sdk_service->method('get_test_mode')->willReturn(true);
        $api_token_service->method('get_api_token')->willReturn('fake-api-token');

        $this->payment_component_service->sdk_service = $sdk_service;
        $this->payment_component_service->api_token_service = $api_token_service;

    }

    /**
     * @return void
     */
    public function tear_down() {
        if ( function_exists( 'WC' ) && $this->did_set_wc_customer ) {
            WC()->customer = $this->original_wc_customer;
            $this->did_set_wc_customer = false;
        }

        parent::tear_down();
    }

    public function test_payment_component_service() {
        $payment_component_arguments = $this->payment_component_service->get_payment_component_arguments( $this->woocommerce_payment_gateway );
        $this->assertIsArray( $payment_component_arguments );
        $this->assertArrayHasKey( 'debug', $payment_component_arguments );
        $this->assertArrayHasKey( 'env', $payment_component_arguments );
        $this->assertArrayHasKey( 'api_token', $payment_component_arguments );
        $this->assertArrayHasKey( 'orderData', $payment_component_arguments );
        $this->assertIsArray( $payment_component_arguments['orderData'] );
        $this->assertArrayHasKey( 'currency', $payment_component_arguments['orderData'] );
        $this->assertArrayHasKey( 'amount', $payment_component_arguments['orderData'] );
        $this->assertArrayHasKey( 'customer', $payment_component_arguments['orderData'] );
        $this->assertIsArray( $payment_component_arguments['orderData']['customer'] );
        $this->assertArrayHasKey( 'locale', $payment_component_arguments['orderData']['customer'] );
        $this->assertArrayHasKey( 'country', $payment_component_arguments['orderData']['customer'] );
        $this->assertArrayHasKey( 'payment_options', $payment_component_arguments['orderData'] );
        $this->assertIsArray( $payment_component_arguments['orderData']['payment_options'] );
        $this->assertArrayHasKey( 'settings', $payment_component_arguments['orderData']['payment_options']['template'] );
        $this->assertIsArray( $payment_component_arguments['orderData']['payment_options']['template']['settings'] );
        $this->assertArrayHasKey( 'embed_mode', $payment_component_arguments['orderData']['payment_options']['template']['settings'] );
        $this->assertIsArray( $payment_component_arguments['orderData']['payment_options']['settings'] );
        $this->assertArrayHasKey( 'connect', $payment_component_arguments['orderData']['payment_options']['settings'] );
        $this->assertIsArray( $payment_component_arguments['orderData']['payment_options']['settings']['connect'] );
        $this->assertArrayHasKey( 'issuers_display_mode', $payment_component_arguments['orderData']['payment_options']['settings']['connect'] );
        $this->assertNotEmpty( $payment_component_arguments['gateway'] );
    }
}
