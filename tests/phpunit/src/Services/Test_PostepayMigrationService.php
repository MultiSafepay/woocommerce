<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Services\PostepayMigrationService;

class Test_PostepayMigrationService extends WP_UnitTestCase {

    /**
     * @var PostepayMigrationService
     */
    public $postepay_migration_service;

    public function set_up() {
        $this->postepay_migration_service = new PostepayMigrationService();

        // Clean up any existing options
        delete_option( 'woocommerce_multisafepay_postepay_migration_done' );
        delete_option( 'woocommerce_multisafepay_postepay_settings' );
        delete_option( 'woocommerce_multisafepay_postepay_mastercard_settings' );
        delete_option( 'woocommerce_multisafepay_postepay_visa_settings' );
        delete_option( 'multisafepay_group_credit_cards' );
    }

    public function test_migration_skipped_when_already_complete() {
        // Set migration as complete
        update_option( 'woocommerce_multisafepay_postepay_migration_done', true );

        // Set up legacy settings (should be ignored)
        update_option( 'woocommerce_multisafepay_postepay_settings', array(
            'enabled' => 'yes',
            'title' => 'PostePay Legacy'
        ));

        $this->postepay_migration_service->postepay_migration();

        // Verify that new settings were NOT created (migration already done)
        $this->assertFalse( get_option( 'woocommerce_multisafepay_postepay_mastercard_settings' ) );
        $this->assertFalse( get_option( 'woocommerce_multisafepay_postepay_visa_settings' ) );
    }

    public function test_migration_skipped_when_credit_cards_grouped() {
        // Enable credit card grouping
        update_option( 'multisafepay_group_credit_cards', true );

        // Set up legacy settings
        update_option( 'woocommerce_multisafepay_postepay_settings', array(
            'enabled' => 'yes',
            'title' => 'PostePay Legacy'
        ));

        $this->postepay_migration_service->postepay_migration();

        $this->assertFalse( (bool) get_option( 'woocommerce_multisafepay_postepay_migration_done' ) );
        $this->assertFalse( get_option( 'woocommerce_multisafepay_postepay_mastercard_settings' ) );
        $this->assertFalse( get_option( 'woocommerce_multisafepay_postepay_visa_settings' ) );
    }

    public function test_migration_creates_variant_settings() {
        // Set up legacy settings
        $legacy_settings = array(
            'enabled' => 'yes',
            'title' => 'PostePay Legacy',
            'description' => 'Legacy description',
            'min_amount' => '10',
            'max_amount' => '1000'
        );
        update_option( 'woocommerce_multisafepay_postepay_settings', $legacy_settings );

        $this->postepay_migration_service->postepay_migration();

        // Verify that new settings were created
        $mastercard_settings = get_option( 'woocommerce_multisafepay_postepay_mastercard_settings' );
        $visa_settings = get_option( 'woocommerce_multisafepay_postepay_visa_settings' );

        $this->assertIsArray( $mastercard_settings );
        $this->assertIsArray( $visa_settings );

        // Verify that settings were copied correctly
        $this->assertEquals( 'yes', $mastercard_settings['enabled'] );
        $this->assertEquals( 'PostePay - Mastercard', $mastercard_settings['title'] );
        $this->assertEquals( 'Legacy description', $mastercard_settings['description'] );
        $this->assertEquals( '10', $mastercard_settings['min_amount'] );
        $this->assertEquals( '1000', $mastercard_settings['max_amount'] );

        $this->assertEquals( 'yes', $visa_settings['enabled'] );
        $this->assertEquals( 'PostePay - Visa', $visa_settings['title'] );
        $this->assertEquals( 'Legacy description', $visa_settings['description'] );

        // Verify that legacy PostePay was disabled
        $updated_legacy_settings = get_option( 'woocommerce_multisafepay_postepay_settings' );
        $this->assertEquals( 'no', $updated_legacy_settings['enabled'] );

        // Verify migration marked as complete
        $this->assertTrue( (bool) get_option( 'woocommerce_multisafepay_postepay_migration_done' ) );
    }

    public function test_migration_enables_existing_variant_settings_when_legacy_enabled() {
        // Set up legacy settings
        update_option( 'woocommerce_multisafepay_postepay_settings', array(
            'enabled' => 'yes',
            'title' => 'PostePay Legacy'
        ));

        // Pre-create one variant setting
        update_option( 'woocommerce_multisafepay_postepay_mastercard_settings', array(
            'enabled' => 'no',
            'title' => 'Existing Mastercard'
        ));

        $this->postepay_migration_service->postepay_migration();

        // Verify that existing setting was enabled (new behavior: when legacy is enabled, variants get enabled)
        $mastercard_settings = get_option( 'woocommerce_multisafepay_postepay_mastercard_settings' );
        $this->assertEquals( 'yes', $mastercard_settings['enabled'] );
        $this->assertEquals( 'Existing Mastercard', $mastercard_settings['title'] );

        // Verify that the other variant was created
        $visa_settings = get_option( 'woocommerce_multisafepay_postepay_visa_settings' );
        $this->assertIsArray( $visa_settings );
        $this->assertEquals( 'PostePay - Visa', $visa_settings['title'] );
        $this->assertEquals( 'yes', $visa_settings['enabled'] );

        // Verify that legacy PostePay was disabled
        $updated_legacy_settings = get_option( 'woocommerce_multisafepay_postepay_settings' );
        $this->assertEquals( 'no', $updated_legacy_settings['enabled'] );

        // Verify migration marked as complete
        $this->assertTrue( (bool) get_option( 'woocommerce_multisafepay_postepay_migration_done' ) );
    }

    public function test_migration_without_legacy_settings() {
        // Don't create any legacy settings
        $this->postepay_migration_service->postepay_migration();

        // Verify that migration was markedly complete
        $this->assertTrue( (bool) get_option( 'woocommerce_multisafepay_postepay_migration_done' ) );

        // Verify that no new settings were created
        $this->assertFalse( get_option( 'woocommerce_multisafepay_postepay_mastercard_settings' ) );
        $this->assertFalse( get_option( 'woocommerce_multisafepay_postepay_visa_settings' ) );
    }

    public function test_final_wp_options_state() {
        // Set up legacy settings
        update_option( 'woocommerce_multisafepay_postepay_settings', array(
            'enabled' => 'yes',
            'title' => 'PostePay Legacy'
        ));

        $this->postepay_migration_service->postepay_migration();

        // Verify final state matches the requirements
        $expected_options = array(
            'woocommerce_multisafepay_postepay_settings',
            'woocommerce_multisafepay_postepay_mastercard_settings',
            'woocommerce_multisafepay_postepay_visa_settings',
            'woocommerce_multisafepay_postepay_migration_done'
        );

        foreach ($expected_options as $option) {
            $this->assertNotFalse( get_option( $option ), 'Option ' . $option . 'should exist' );
        }

        // Verify the cleanup flag is set to true (not 1 string)
        $this->assertTrue( (bool) get_option( 'woocommerce_multisafepay_postepay_migration_done' ) );
    }

    public function test_enable_existing_disabled_variants_when_legacy_enabled() {
        // Set up legacy settings with enabled = 'yes'
        update_option( 'woocommerce_multisafepay_postepay_settings', array(
            'enabled' => 'yes',
            'title' => 'PostePay Legacy',
            'description' => 'Legacy description'
        ));

        // Pre-create disabled variant settings
        update_option( 'woocommerce_multisafepay_postepay_mastercard_settings', array(
            'enabled' => 'no',
            'title' => 'Existing Mastercard',
            'description' => 'Existing Mastercard description'
        ));

        update_option( 'woocommerce_multisafepay_postepay_visa_settings', array(
            'enabled' => 'no', 
            'title' => 'Existing Visa',
            'description' => 'Existing Visa description'
        ));

        $this->postepay_migration_service->postepay_migration();

        // Verify that existing settings were enabled
        $mastercard_settings = get_option( 'woocommerce_multisafepay_postepay_mastercard_settings' );
        $visa_settings = get_option( 'woocommerce_multisafepay_postepay_visa_settings' );

        $this->assertEquals( 'yes', $mastercard_settings['enabled'] );
        $this->assertEquals( 'Existing Mastercard', $mastercard_settings['title'] );
        $this->assertEquals( 'Existing Mastercard description', $mastercard_settings['description'] );

        $this->assertEquals( 'yes', $visa_settings['enabled'] );
        $this->assertEquals( 'Existing Visa', $visa_settings['title'] );
        $this->assertEquals( 'Existing Visa description', $visa_settings['description'] );

        // Verify that legacy PostePay was disabled
        $updated_legacy_settings = get_option( 'woocommerce_multisafepay_postepay_settings' );
        $this->assertEquals( 'no', $updated_legacy_settings['enabled'] );

        // Verify migration marked as complete
        $this->assertTrue( (bool) get_option( 'woocommerce_multisafepay_postepay_migration_done' ) );
    }

    public function test_enable_existing_disabled_variants_with_one_variant_only() {
        // Set up legacy settings with enabled = 'yes'
        update_option( 'woocommerce_multisafepay_postepay_settings', array(
            'enabled' => 'yes',
            'title' => 'PostePay Legacy',
            'description' => 'Legacy description'
        ));

        // Pre-create only Mastercard variant (disabled)
        update_option( 'woocommerce_multisafepay_postepay_mastercard_settings', array(
            'enabled' => 'no',
            'title' => 'Existing Mastercard'
        ));

        $this->postepay_migration_service->postepay_migration();

        // Verify that existing Mastercard was enabled
        $mastercard_settings = get_option( 'woocommerce_multisafepay_postepay_mastercard_settings' );
        $this->assertEquals( 'yes', $mastercard_settings['enabled'] );
        $this->assertEquals( 'Existing Mastercard', $mastercard_settings['title'] );

        // Verify that Visa was created with legacy settings
        $visa_settings = get_option( 'woocommerce_multisafepay_postepay_visa_settings' );
        $this->assertIsArray( $visa_settings );
        $this->assertEquals( 'yes', $visa_settings['enabled'] );
        $this->assertEquals( 'PostePay - Visa', $visa_settings['title'] );
        $this->assertEquals( 'Legacy description', $visa_settings['description'] );

        // Verify that legacy PostePay was disabled
        $updated_legacy_settings = get_option( 'woocommerce_multisafepay_postepay_settings' );
        $this->assertEquals( 'no', $updated_legacy_settings['enabled'] );

        // Verify migration marked as complete
        $this->assertTrue( (bool) get_option( 'woocommerce_multisafepay_postepay_migration_done' ) );
    }

    public function test_no_action_when_legacy_disabled_and_variants_exist() {
        // Set up legacy settings with enabled = 'no'
        update_option( 'woocommerce_multisafepay_postepay_settings', array(
            'enabled' => 'no',
            'title' => 'PostePay Legacy'
        ));

        // Pre-create disabled variant settings
        update_option( 'woocommerce_multisafepay_postepay_mastercard_settings', array(
            'enabled' => 'no',
            'title' => 'Existing Mastercard'
        ));

        update_option( 'woocommerce_multisafepay_postepay_visa_settings', array(
            'enabled' => 'no',
            'title' => 'Existing Visa'
        ));

        $this->postepay_migration_service->postepay_migration();

        // Verify that variants remain disabled when legacy is also disabled
        $mastercard_settings = get_option( 'woocommerce_multisafepay_postepay_mastercard_settings' );
        $visa_settings = get_option( 'woocommerce_multisafepay_postepay_visa_settings' );

        $this->assertEquals( 'no', $mastercard_settings['enabled'] );
        $this->assertEquals( 'no', $visa_settings['enabled'] );

        // Verify that legacy PostePay remains disabled
        $updated_legacy_settings = get_option( 'woocommerce_multisafepay_postepay_settings' );
        $this->assertEquals( 'no', $updated_legacy_settings['enabled'] );

        // Verify migration marked as complete
        $this->assertTrue( (bool) get_option( 'woocommerce_multisafepay_postepay_migration_done' ) );
    }
}
