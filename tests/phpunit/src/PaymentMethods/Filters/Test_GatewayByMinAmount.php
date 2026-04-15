<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\PaymentMethods\Filters\GatewayByMinAmount;
use MultiSafepay\WooCommerce\Utils\Logger;

class Test_GatewayByMinAmount extends WP_UnitTestCase {

    /**
     * @return void
     */
    public function test_unsets_gateway_when_total_amount_is_below_minimum_amount() {
        $logger = $this->getMockBuilder( Logger::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'log_info' ) )
            ->getMock();

        $filter = new GatewayByMinAmount( $logger );

        $original_wc_cart = null;

        if ( function_exists( 'WC' ) ) {
            $original_wc_cart = WC()->cart;

            WC()->cart = $this->getMockBuilder( 'WC_Cart' )
                ->disableOriginalConstructor()
                ->setMethods( array( 'get_total' ) )
                ->getMock();

            WC()->cart->method( 'get_total' )->willReturn( 10.0 );
        }

        try {
            $allowed_gateway             = new stdClass();
            $allowed_gateway->min_amount = 5.0;

            $blocked_gateway             = new stdClass();
            $blocked_gateway->min_amount = 20.0;

            $result = $filter->filter_gateway_per_min_amount(
                array(
                    'allowed' => $allowed_gateway,
                    'blocked' => $blocked_gateway,
                )
            );

            $this->assertArrayHasKey( 'allowed', $result );
            $this->assertArrayNotHasKey( 'blocked', $result );
        } finally {
            if ( function_exists( 'WC' ) ) {
                WC()->cart = $original_wc_cart;
            }
        }
    }
}
