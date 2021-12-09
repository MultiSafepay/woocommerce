<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\PaymentMethods\Gateways;

class Test_Gateways extends WP_UnitTestCase {

    public function setUp() {
        parent::setUp();
    }

    public function test_get_gateways_ids_returns_an_array() {
        $gateways_ids = Gateways::get_gateways_ids();
        $this->assertIsArray($gateways_ids);
    }

    public function test_get_payment_method_id_is_equal_to_payment_method_key() {
        foreach (Gateways::GATEWAYS as $key => $gateway) {
            $this->assertSame($key, (new $gateway)->get_payment_method_id());
        }
    }

    public function test_get_payment_method_object_by_gateway_code() {
        $gateway = Gateways::get_payment_method_object_by_gateway_code('VISA');
        $this->assertInstanceOf( WC_Payment_Gateway::class, $gateway);
    }

    public function test_get_payment_method_object_by_gateway_code_that_does_not_exist() {
        $gateway = Gateways::get_payment_method_object_by_gateway_code('CODE-NOT-EXIST');
        $this->assertFalse($gateway);
    }

    public function test_get_payment_method_object_by_payment_method_id() {
        $gateways_ids = Gateways::get_gateways_ids();
        $payment_method_index = array_rand($gateways_ids);
        $gateway = Gateways::get_payment_method_object_by_payment_method_id( $gateways_ids[$payment_method_index] );
        $this->assertInstanceOf( WC_Payment_Gateway::class, $gateway);
    }

    public function test_get_payment_method_object_by_payment_method_id_that_does_not_exist() {
        $gateway = Gateways::get_payment_method_object_by_payment_method_id('PAYMENT-METHOD-ID-NOT-EXIST');
        $this->assertFalse($gateway);
    }
}
