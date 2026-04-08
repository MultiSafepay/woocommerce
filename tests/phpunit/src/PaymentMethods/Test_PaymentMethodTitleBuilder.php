<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Utils\PaymentMethodTitleBuilder;

class Test_PaymentMethodTitleBuilder extends WP_UnitTestCase {

    /**
     * @var PaymentMethodTitleBuilder
     */
    private $payment_method_title_builder;

    /**
     * @return void
     */
    public function set_up() {
        parent::set_up();
        $this->payment_method_title_builder = new PaymentMethodTitleBuilder();
    }

    /**
     * @return void
     */
    public function test_build_combined_payment_method_title_returns_wallet_and_instrument() {
        $result = $this->payment_method_title_builder->build_combined_payment_method_title( 'Google Pay', 'Visa' );
        $this->assertEquals( 'Google Pay (Visa)', $result );
    }

    /**
     * @return void
     */
    public function test_get_underlying_payment_method_title_normalizes_amex_gateway_code() {
        $result = $this->payment_method_title_builder->get_underlying_payment_method_title( 'AMEX' );

        $this->assertEquals( 'American Express', $result );
    }

    /**
     * @return void
     */
    public function test_get_underlying_payment_method_title_normalizes_mastercard_gateway_code() {
        $result = $this->payment_method_title_builder->get_underlying_payment_method_title( 'MASTERCARD' );

        $this->assertEquals( 'Mastercard', $result );
    }

    /**
     * @return void
     */
    public function test_get_underlying_payment_method_title_returns_unmapped_gateway_code() {
        $result = $this->payment_method_title_builder->get_underlying_payment_method_title( 'MAESTRO' );

        $this->assertEquals( 'MAESTRO', $result );
    }

    /**
     * @return void
     */
    public function test_get_underlying_payment_method_title_trims_known_gateway_code() {
        $result = $this->payment_method_title_builder->get_underlying_payment_method_title( ' AMEX ' );

        $this->assertEquals( 'American Express', $result );
    }

    /**
     * @return void
     */
    public function test_get_underlying_payment_method_title_trims_unknown_gateway_code() {
        $result = $this->payment_method_title_builder->get_underlying_payment_method_title( '  Some Custom Method  ' );

        $this->assertEquals( 'Some Custom Method', $result );
    }

    /**
     * @return void
     */
    public function test_can_build_wallet_combined_payment_method_title_returns_true_for_supported_instrument() {
        $result = $this->payment_method_title_builder->can_build_wallet_combined_payment_method_title( 'AMEX' );

        $this->assertTrue( $result );
    }

    /**
     * @return void
     */
    public function test_can_build_wallet_combined_payment_method_title_returns_false_for_unsupported_instrument() {
        $result = $this->payment_method_title_builder->can_build_wallet_combined_payment_method_title( 'MAESTRO' );

        $this->assertFalse( $result );
    }

    /**
     * @return void
     */
    public function test_get_underlying_payment_method_title_returns_empty_for_empty_gateway_code() {
        $result = $this->payment_method_title_builder->get_underlying_payment_method_title( '' );

        $this->assertEquals( '', $result );
    }

    /**
     * @return void
     */
    public function test_can_build_wallet_combined_payment_method_title_trims_supported_instrument() {
        $result = $this->payment_method_title_builder->can_build_wallet_combined_payment_method_title( ' VISA ' );

        $this->assertTrue( $result );
    }
}
