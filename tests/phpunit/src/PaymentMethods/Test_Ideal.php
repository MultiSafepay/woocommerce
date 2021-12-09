<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods\Ideal;

class Test_Ideal extends WP_UnitTestCase {

    public function setUp() {
        parent::setUp();
        $this->ideal = new Ideal();
    }

    public function test_id() {
        $ideal = $this->ideal;
        $id = $ideal->id;
        $this->assertEquals('multisafepay_ideal', $id);;
    }

    public function test_get_icon() {
        $ideal = $this->ideal;
        $icon = $ideal->get_icon();
        $this->assertRegExp('/ideal.png/', $icon);
    }


}
