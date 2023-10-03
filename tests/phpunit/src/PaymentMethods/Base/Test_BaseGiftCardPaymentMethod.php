<?php declare(strict_types=1);

use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod;

class Test_BaseGiftCardPaymentMethod extends WP_UnitTestCase {

    /**
     * @var PaymentMethod
     */
    public $payment_method;

    /**
     * @var BaseGiftCardPaymentMethod;
     */
    public $woocommerce_payment_gateway;


    public function set_up() {
        $this->payment_method = new PaymentMethod( $this->get_fietsenbon_payment_method_fixture() );
        $this->woocommerce_payment_gateway = new BaseGiftCardPaymentMethod( $this->payment_method );
    }

    public function test_is_base_giftcard_payment_method() {
        $this->assertInstanceOf( BaseGiftCardPaymentMethod::class, $this->woocommerce_payment_gateway );
    }

    public function test_is_wc_payment_gateway() {
        $this->assertInstanceOf( WC_Payment_Gateway::class, $this->woocommerce_payment_gateway );
    }

    public function test_get_payment_method_id() {
        $this->assertEquals( 'multisafepay_fietsenbon', $this->woocommerce_payment_gateway->get_payment_method_id() );
    }

    public function test_get_payment_method_gateway_code() {
        $this->assertEquals( 'FIETSENBON', $this->woocommerce_payment_gateway->get_payment_method_gateway_code() );
    }

    public function test_get_payment_method_description() {
        $this->assertNotEmpty( $this->woocommerce_payment_gateway->get_payment_method_description() );
    }

    public function test_get_payment_method_icon() {
        $this->assertEquals( 'https://testmedia.multisafepay.com/img/coupons/3x/FIETSENBON.png', $this->woocommerce_payment_gateway->get_payment_method_icon() );
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
        $this->assertArrayNotHasKey( 'tokenization', $setting_fields );
    }

    public function test_can_refund_order() {
        $this->assertFalse( $this->woocommerce_payment_gateway->can_refund_order( new WC_Order() ) );
    }

    private function get_fietsenbon_payment_method_fixture(): array {
        return [
            "additional_data" => [],
            "allowed_amount" => [
                "max" => null,
                "min" => 0
            ],
            "allowed_countries" => [],
            "allowed_currencies" => [
                "EUR",
                "GBP",
                "USD"
            ],
            "apps" => [
                "fastcheckout" => [
                    "is_enabled" => true,
                ],
                "payment_components" => [
                    "has_fields" => true,
                    "is_enabled" => true,
                ],
            ],
            "brands" => [],
            "icon_urls" => [
                "large"  => "https://testmedia.multisafepay.com/img/coupons/3x/FIETSENBON.png",
                "medium" => "https://testmedia.multisafepay.com/img/coupons/2x/FIETSENBON.png",
                "vector" => "https://testmedia.multisafepay.com/img/coupons/svg/FIETSENBON.svg",
            ],
            "id" => "FIETSENBON",
            "name" => "fietsenbon",
            "preferred_countries" => [],
            "required_customer_data" => [],
            "shopping_cart_required" => false,
            "tokenization" => [
                "is_enabled" => false,
                "models" => null,
            ],
            "type" => "coupon"
        ];
    }
}
