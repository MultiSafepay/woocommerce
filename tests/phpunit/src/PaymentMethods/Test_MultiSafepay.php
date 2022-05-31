<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\MultiSafepay;

class Test_MultiSafepay extends WP_UnitTestCase {

    public function set_up() {
        parent::set_up();
        $this->multisafepay = new MultiSafepay();
    }

    public function test_get_payment_method_code() {
        $multisafepay = $this->multisafepay;
        $gateway_code = $multisafepay->get_payment_method_code();
        $this->assertEmpty($gateway_code);
    }


}
