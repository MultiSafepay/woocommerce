<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Settings\SettingsController;

class Test_SettingsController extends WP_UnitTestCase {

    private $settings_controller;

    public function set_up() {
        parent::set_up();
        $this->settings_controller = new SettingsController();
        set_current_screen( 'woocommerce_page_multisafepay-settings' );
        wp_dequeue_script( 'multisafepay-admin-js' );
        wp_deregister_script( 'multisafepay-admin-js' );
    }

    public function tear_down() {
        wp_dequeue_script( 'multisafepay-admin-js' );
        wp_deregister_script( 'multisafepay-admin-js' );
        unset( $_GET['section'] );
        parent::tear_down();
    }

    public function test_enqueue_styles_and_scripts_adds_localized_direct_payment_confirmation_message() {
        $_GET['section'] = 'multisafepay_googlepay';

        $this->settings_controller->enqueue_styles_and_scripts();

        $this->assertTrue( wp_script_is( 'multisafepay-admin-js', 'enqueued' ) );

        $wp_scripts = wp_scripts();
        $data       = $wp_scripts->get_data( 'multisafepay-admin-js', 'data' );

        $this->assertIsString( $data );
        $this->assertStringContainsString( 'multisafepayAdminData', $data );
        $this->assertStringContainsString( 'title', $data );
        $this->assertStringContainsString( 'messageTemplate', $data );
        $this->assertStringContainsString( '%payment_method%', $data );
    }

    public function test_enqueue_styles_and_scripts_does_not_enqueue_admin_script_for_non_target_section() {
        $_GET['section'] = 'multisafepay_ideal';

        $this->settings_controller->enqueue_styles_and_scripts();

        $this->assertFalse( wp_script_is( 'multisafepay-admin-js', 'enqueued' ) );
    }

    public function test_enqueue_styles_and_scripts_enqueues_admin_script_for_supported_direct_sections() {
        foreach ( array( 'multisafepay_applepay', 'multisafepay_googlepay', 'multisafepay_bancontact' ) as $section ) {
            wp_dequeue_script( 'multisafepay-admin-js' );
            wp_deregister_script( 'multisafepay-admin-js' );
            $_GET['section'] = $section;

            $this->settings_controller->enqueue_styles_and_scripts();

            $this->assertTrue( wp_script_is( 'multisafepay-admin-js', 'enqueued' ) );
        }
    }

    public function test_direct_activation_selector_targets_only_use_direct_button() {
        $admin_script_path = dirname( __DIR__, 4 ) . '/assets/admin/js/multisafepay-admin.js';
        $admin_script      = file_get_contents( $admin_script_path );

        $this->assertIsString( $admin_script );
        $this->assertStringContainsString( '[id$="_use_direct_button"]', $admin_script );
    }

    public function test_direct_activation_safeguard_keeps_revert_on_cancel_behavior() {
        $admin_script_path = dirname( __DIR__, 4 ) . '/assets/admin/js/multisafepay-admin.js';
        $admin_script      = file_get_contents( $admin_script_path );

        $this->assertIsString( $admin_script );
        $this->assertStringContainsString( 'if (!confirmation)', $admin_script );
        $this->assertStringContainsString( "field.val(previousValue || '0');", $admin_script );
        $this->assertStringContainsString( "field.trigger('change');", $admin_script );
    }
}
