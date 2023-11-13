<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Main;

class Test_Main extends WP_UnitTestCase {

    public const FILTER_HOOKS = array (
        'plugin_action_links_multisafepay/multisafepay.php',
        'option_multisafepay_testmode',
        'option_multisafepay_debugmode',
        'option_multisafepay_second_chance',
        'option_multisafepay_disable_shopping_cart',
        'option_multisafepay_time_active',
        'woocommerce_payment_gateways',
        'multisafepay_transaction_order_id',
        'woocommerce_available_payment_gateways',
        'woocommerce_available_payment_gateways',
        'woocommerce_get_checkout_payment_url',
        'woocommerce_valid_order_statuses_for_cancel'
    );

    public const ACTION_HOOKS = array (
        'plugins_loaded',
        'wp_enqueue_scripts',
        'woocommerce_order_status_completed',
        'woocommerce_new_order',
        'before_woocommerce_init',
        'woocommerce_api_multisafepay',
        'rest_api_init',
        'wp_ajax_ajax_get_payment_component_arguments',
        'wp_ajax_nopriv_get_payment_component_arguments'
    );

    public function test_filters() {
        $main = new Main();
        $loader = $main->loader;
        $this->assertCount( count(self::FILTER_HOOKS), $loader->filters);
    }

    public function test_actions() {
        $main = new Main();
        $loader = $main->loader;
        $this->assertCount( count(self::ACTION_HOOKS), $loader->actions);
    }

    public function test_filter_as_admin() {
        $user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        $user = wp_set_current_user( $user_id );
        set_current_screen( 'edit-post' );
        $main = new Main();
        $loader = $main->loader;
        $this->assertCount( count(
            array_merge(
            self::FILTER_HOOKS,
                [
                    'woocommerce_screen_ids',
                    'multisafepay_common_settings_fields'
                ]
            )
        ), $loader->filters);
    }

    public function test_actions_as_admin() {
        $user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        $user = wp_set_current_user( $user_id );
        set_current_screen( 'edit-post' );
        $main = new Main();
        $loader = $main->loader;
        $this->assertCount( count(
            array_merge(
                self::ACTION_HOOKS,
                [
                    'admin_enqueue_scripts',
                    'admin_menu',
                    'admin_init',
                    'woocommerce_new_order'
                ]
            )
        ), $loader->actions);
    }
}
