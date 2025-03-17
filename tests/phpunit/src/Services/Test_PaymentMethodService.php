<?php declare(strict_types=1);

use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\Api\PaymentMethods\PaymentMethodListing;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\Services\PaymentMethodService;
use MultiSafepay\WooCommerce\Tests\Fixtures\PaymentMethodFixture;

class Test_PaymentMethodService extends WP_UnitTestCase {

    /**
     * @var PaymentMethodService;
     */
    public $payment_method_service;

    public function set_up() {
        // Ensure WC()->cart is available so that get_total_amount() returns a valid float.
        if ( function_exists( 'WC' ) && ! isset( WC()->cart ) ) {
            WC()->cart = $this->getMockBuilder( 'WC_Cart' )
                ->disableOriginalConstructor()
                ->setMethods( ['get_total'] )
                ->getMock();

            WC()->cart->expects( $this->once() )
                ->method( 'get_total' )
                ->willReturn( '10.00' );
        }

        $this->payment_method_service = new PaymentMethodService();

        $payment_methods_fixture = (new PaymentMethodListing(
            (new PaymentMethodFixture())->get_payment_methods_example_response_from_api()
        ))->asArray();

        $sdk_service = $this->getMockBuilder('SdkService')
            ->disableOriginalConstructor()
            ->setMethods(['get_payment_method_manager'])
            ->getMock();

        $payment_method_manager = $this->getMockBuilder('PaymentMethodManager')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentMethodsAsArray'])
            ->getMock();

        $sdk_service->method('get_payment_method_manager')->willReturn($payment_method_manager);
        $payment_method_manager->method('getPaymentMethodsAsArray')->willReturn( $payment_methods_fixture );

        $this->payment_method_service->payment_method_manager = $payment_method_manager;
    }

    public function test_get_multisafepay_payment_methods_from_api_when_payment_method_manager_is_null() {
            $this->payment_method_service->payment_method_manager = null;
            $payment_methods = $this->payment_method_service->get_multisafepay_payment_methods_from_api();
            $this->assertIsArray( $payment_methods );
            $this->assertEmpty( $payment_methods );
    }

    public function test_get_multisafepay_payment_methods_from_api_when_payment_method_manager_from_api() {
        delete_transient('multisafepay_payment_methods');
        $payment_methods = $this->payment_method_service->get_multisafepay_payment_methods_from_api();
        $this->assertIsArray( $payment_methods );
        $this->assertNotEmpty( $payment_methods );
    }

    public function test_get_woocommerce_payment_gateways() {
        delete_transient('multisafepay_payment_methods');
        $woocommerce_payment_gateways = $this->payment_method_service->get_woocommerce_payment_gateways();
        foreach ($woocommerce_payment_gateways as $woocommerce_payment_gateway) {
            $this->assertInstanceOf(BasePaymentMethod::class, $woocommerce_payment_gateway );
        }
    }

    public function test_get_woocommerce_payment_gateway_by_id() {
        delete_transient('multisafepay_payment_methods');
        $woocommerce_payment_gateway = $this->payment_method_service->get_woocommerce_payment_gateway_by_id( 'multisafepay_ideal' );
        $this->assertInstanceOf(BasePaymentMethod::class, $woocommerce_payment_gateway );
        $this->assertEquals('IDEAL', $woocommerce_payment_gateway->get_payment_method_gateway_code() );
    }

    public function test_get_woocommerce_payment_gateway_ids() {
        delete_transient('multisafepay_payment_methods');
        $woocommerce_payment_gateway_ids = $this->payment_method_service->get_woocommerce_payment_gateway_ids();
        $this->assertEquals( 'multisafepay_amex', $woocommerce_payment_gateway_ids[0]);
        $this->assertEquals( 'multisafepay_ideal', $woocommerce_payment_gateway_ids[1]);
    }

    public function test_get_woocommerce_payment_gateway_ids_with_payment_component_support() {
        delete_transient('multisafepay_payment_methods');
        $woocommerce_payment_gateway_ids = $this->payment_method_service->get_woocommerce_payment_gateway_ids_with_payment_component_support();
        $this->assertEquals( 'multisafepay_amex', $woocommerce_payment_gateway_ids[0]);
        $this->assertEquals( 'multisafepay_ideal', $woocommerce_payment_gateway_ids[1]);
    }

    public function test_get_legacy_woocommerce_payment_gateway_ids() {
        delete_transient('multisafepay_payment_methods');
        $legacy_woocommerce_payment_gateway_id = $this->payment_method_service->get_legacy_woocommerce_payment_gateway_ids( 'bnpl_instm');
        $this->assertEquals( 'multisafepay_payafterdelivery_installments', $legacy_woocommerce_payment_gateway_id);
    }

    public function test_get_legacy_woocommerce_payment_gateway_ids_for_gateway_with_no_legacy() {
        delete_transient('multisafepay_payment_methods');
        $legacy_woocommerce_payment_gateway_id = $this->payment_method_service->get_legacy_woocommerce_payment_gateway_ids( 'whatever');
        $this->assertEquals( 'multisafepay_whatever', $legacy_woocommerce_payment_gateway_id);
    }

    public function test_branded_payment_gateways_are_created_when_brand_has_allowed_countries(): void {
        $multisafepay_payment_method = ( new PaymentMethodFixture() )->get_credit_card_payment_method_fixture();
        $payment_method = new PaymentMethod( $multisafepay_payment_method );
        $result = $this->payment_method_service->create_branded_woocommerce_payment_gateways($multisafepay_payment_method, array(), $payment_method);
        $this->assertEquals(3, count($result));
    }

    public function test_payment_gateways_are_created(): void {
        $multisafepay_payment_method = ( new PaymentMethodFixture() )->get_credit_card_payment_method_fixture();
        $payment_method = new PaymentMethod( $multisafepay_payment_method );
        $result = $this->payment_method_service->create_woocommerce_payment_gateways($multisafepay_payment_method, array());
        $this->assertEquals(4, count($result));
    }
}
