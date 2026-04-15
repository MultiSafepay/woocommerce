<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\PaymentMethods\Filters\GatewayByUserRole;
use MultiSafepay\WooCommerce\Utils\Logger;

class Test_GatewayByUserRole extends WP_UnitTestCase {

    /**
     * @return void
     */
    public function test_unsets_gateway_when_current_user_role_is_not_allowed() {
        $logger = $this->getMockBuilder( Logger::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'log_info' ) )
            ->getMock();

        $filter = new GatewayByUserRole( $logger );

        $user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
        wp_set_current_user( $user_id );

        $allowed_gateway                      = new stdClass();
        $allowed_gateway->settings            = array();
        $allowed_gateway->settings['user_roles'] = array( 'customer' );

        $blocked_gateway                      = new stdClass();
        $blocked_gateway->settings            = array();
        $blocked_gateway->settings['user_roles'] = array( 'administrator' );

        $result = $filter->filter_gateway_per_user_roles(
            array(
                'allowed' => $allowed_gateway,
                'blocked' => $blocked_gateway,
            )
        );

        $this->assertArrayHasKey( 'allowed', $result );
        $this->assertArrayNotHasKey( 'blocked', $result );
    }

    /**
     * @return void
     */
    public function test_unsets_gateway_when_allowed_roles_setting_is_string() {
        $logger = $this->getMockBuilder( Logger::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'log_info' ) )
            ->getMock();

        $filter = new GatewayByUserRole( $logger );

        $user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
        wp_set_current_user( $user_id );

        $blocked_gateway               = new stdClass();
        $blocked_gateway->settings     = array();
        $blocked_gateway->settings['user_roles'] = 'administrator';

        $result = $filter->filter_gateway_per_user_roles(
            array(
                'blocked' => $blocked_gateway,
            )
        );

        $this->assertArrayNotHasKey( 'blocked', $result );
    }
}
