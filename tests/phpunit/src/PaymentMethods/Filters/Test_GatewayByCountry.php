<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\PaymentMethods\Filters\GatewayByCountry;
use MultiSafepay\WooCommerce\Utils\Logger;

class Test_GatewayByCountry extends WP_UnitTestCase {

    /**
     * @return void
     */
    public function test_unsets_gateway_when_customer_country_is_not_allowed() {
        $logger = $this->getMockBuilder( Logger::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'log_info' ) )
            ->getMock();

        $filter = new GatewayByCountry( $logger );

        $original_wc_customer = null;

        if ( function_exists( 'WC' ) ) {
            $original_wc_customer = WC()->customer;

            WC()->customer = $this->getMockBuilder( 'WC_Customer' )
                ->disableOriginalConstructor()
                ->setMethods( array( 'get_billing_country' ) )
                ->getMock();

            WC()->customer->method( 'get_billing_country' )->willReturn( 'NL' );
        }

        try {
            $allowed_gateway            = new stdClass();
            $allowed_gateway->countries = array( 'NL', 'BE' );

            $blocked_gateway            = new stdClass();
            $blocked_gateway->countries = array( 'DE' );

            $result = $filter->filter_gateway_per_country(
                array(
                    'allowed' => $allowed_gateway,
                    'blocked' => $blocked_gateway,
                )
            );

            $this->assertArrayHasKey( 'allowed', $result );
            $this->assertArrayNotHasKey( 'blocked', $result );
        } finally {
            if ( function_exists( 'WC' ) ) {
                WC()->customer = $original_wc_customer;
            }
        }
    }
}
