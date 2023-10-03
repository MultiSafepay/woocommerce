<?php declare(strict_types=1);

use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\Tests\Fixtures\PaymentMethodFixture;

class Test_BasePaymentMethod extends WP_UnitTestCase {

    /**
     * @var PaymentMethod
     */
    public $payment_method;

    /**
     * @var BasePaymentMethod;
     */
    public $woocommerce_payment_gateway;


    public function set_up() {
        $this->payment_method = new PaymentMethod( ( new PaymentMethodFixture() )->get_amex_payment_method_fixture() );
        $this->woocommerce_payment_gateway = new BasePaymentMethod( $this->payment_method );
    }

    public function test_is_base_payment_method() {
        $this->assertInstanceOf( BasePaymentMethod::class, $this->woocommerce_payment_gateway );
    }

    public function test_is_wc_payment_gateway() {
        $this->assertInstanceOf( WC_Payment_Gateway::class, $this->woocommerce_payment_gateway );
    }

    public function test_get_payment_method_id() {
        $this->assertEquals( 'multisafepay_amex', $this->woocommerce_payment_gateway->get_payment_method_id() );
    }

    public function test_get_payment_method_gateway_code() {
        $this->assertEquals( 'AMEX', $this->woocommerce_payment_gateway->get_payment_method_gateway_code() );
    }

    public function test_get_payment_method_description() {
        $this->assertNotEmpty( $this->woocommerce_payment_gateway->get_payment_method_description() );
    }

    public function test_get_payment_method_icon() {
        $this->assertEquals( 'https://testmedia.multisafepay.com/img/methods/3x/amex.png', $this->woocommerce_payment_gateway->get_payment_method_icon() );
    }

    public function test_has_fields() {
        $this->assertFalse( $this->woocommerce_payment_gateway->has_fields() );
    }

    public function test_has_payment_component_setting_field() {
        $setting_fields = $this->woocommerce_payment_gateway->add_form_fields();
        $this->assertArrayHasKey( 'payment_component', $setting_fields );
    }

    public function test_has_tokenization_setting_field() {
        $setting_fields = $this->woocommerce_payment_gateway->add_form_fields();
        $this->assertArrayHasKey( 'tokenization', $setting_fields );
    }
}
