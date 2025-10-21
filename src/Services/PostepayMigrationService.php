<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\Services;

/**
 * Class PostepayMigrationService
 *
 * Handles the one-time migration of PostePay payment method settings
 * from the legacy single PostePay to the new Mastercard/Visa variants.
 *
 * @package MultiSafepay\WooCommerce\Services
 */
class PostepayMigrationService {

    /**
     * Option name that marks the migration as complete
     */
    private const MIGRATION_COMPLETE_OPTION = 'woocommerce_multisafepay_postepay_migration_done';

    /**
     * Legacy PostePay settings option
     */
    private const LEGACY_POSTEPAY_OPTION = 'woocommerce_multisafepay_postepay_settings';

    /**
     * New PostePay Mastercard settings option
     */
    private const MASTERCARD_POSTEPAY_OPTION = 'woocommerce_multisafepay_postepay_mastercard_settings';

    /**
     * New PostePay Visa settings option
     */
    private const VISA_POSTEPAY_OPTION = 'woocommerce_multisafepay_postepay_visa_settings';

    /**
     * New PostePay - Mastercard title pattern
     */
    private const POSTEPAY_MASTERCARD_TITLE_PATTERN = 'PostePay - Mastercard';

    /**
     * New PostePay - Visa title pattern
     */
    private const POSTEPAY_VISA_TITLE_PATTERN = 'PostePay - Visa';

    /**
     * Performs the complete migration process if not already done
     *
     * @return void
     */
    public function postepay_migration(): void {
        // Check if migration is already complete
        if ( $this->is_migration_complete() ) {
            return;
        }

        // Only proceed if credit cards are not grouped
        if ( get_option( 'multisafepay_group_credit_cards', false ) ) {
            return;
        }

        // Get the legacy PostePay settings
        $legacy_settings = get_option( self::LEGACY_POSTEPAY_OPTION, false );
        if ( ! is_array( $legacy_settings ) ) {
            // No legacy settings exist, just mark as complete
            $this->mark_migration_complete();
            return;
        }

        // Check for the special case: legacy is enabled, but variants exist and are disabled
        if ( $this->should_enable_existing_disabled_variants( $legacy_settings ) ) {
            $this->enable_existing_disabled_variants_and_disable_legacy( $legacy_settings );
            $this->mark_migration_complete();
            return;
        }

        // Perform the migration
        $this->migrate_postepay_variants( $legacy_settings );

        // Mark migration as complete
        $this->mark_migration_complete();
    }

    /**
     * Checks if the migration is already complete
     *
     * @return bool
     */
    private function is_migration_complete(): bool {
        return (bool) get_option( self::MIGRATION_COMPLETE_OPTION, false );
    }

    /**
     * Migrates settings for both PostePay variants
     *
     * @param array $legacy_settings
     * @return void
     */
    private function migrate_postepay_variants( array $legacy_settings ): void {
        // Create settings for Mastercard variant
        $this->create_variant_settings(
            self::MASTERCARD_POSTEPAY_OPTION,
            $legacy_settings,
            self::POSTEPAY_MASTERCARD_TITLE_PATTERN
        );

        // Create settings for Visa variant
        $this->create_variant_settings(
            self::VISA_POSTEPAY_OPTION,
            $legacy_settings,
            self::POSTEPAY_VISA_TITLE_PATTERN
        );

        // Disable the legacy PostePay method
        $legacy_settings['enabled'] = 'no';
        update_option( self::LEGACY_POSTEPAY_OPTION, $legacy_settings );
    }

    /**
     * Creates settings for a PostePay variant
     *
     * @param string $option_name
     * @param array  $base_settings
     * @param string $title
     * @return void
     */
    private function create_variant_settings( string $option_name, array $base_settings, string $title ): void {
        $existing_settings = get_option( $option_name, false );

        if ( ! $existing_settings ) {
            // Create new settings if they don't exist
            $variant_settings          = $base_settings;
            $variant_settings['title'] = $title;
            update_option( $option_name, $variant_settings );
            return;
        }

        if ( ! is_array( $existing_settings ) ) {
            return;
        }

        if ( ! isset( $existing_settings['enabled'] ) ) {
            return;
        }

        if ( 'no' !== $existing_settings['enabled'] ) {
            return;
        }

        if ( ! isset( $base_settings['enabled'] ) ) {
            return;
        }

        if ( 'yes' !== $base_settings['enabled'] ) {
            return;
        }

        // Enable existing disabled settings if legacy is enabled
        $existing_settings['enabled'] = 'yes';
        update_option( $option_name, $existing_settings );
    }

    /**
     * Marks the migration process as complete
     *
     * @return void
     */
    private function mark_migration_complete(): void {
        update_option( self::MIGRATION_COMPLETE_OPTION, true );
    }

    /**
     * Checks if the legacy PostePay settings should enable existing disabled variants
     *
     * @param array $legacy_settings
     * @return bool
     */
    private function should_enable_existing_disabled_variants( array $legacy_settings ): bool {
        return 'yes' === $legacy_settings['enabled'] &&
            ( get_option( self::MASTERCARD_POSTEPAY_OPTION, false ) || get_option( self::VISA_POSTEPAY_OPTION, false ) );
    }

    /**
     * Enables existing disabled variants and disables the legacy PostePay method
     *
     * @param array $legacy_settings
     * @return void
     */
    private function enable_existing_disabled_variants_and_disable_legacy( array $legacy_settings ): void {
        // Enable existing disabled variants
        $this->create_variant_settings(
            self::MASTERCARD_POSTEPAY_OPTION,
            $legacy_settings,
            self::POSTEPAY_MASTERCARD_TITLE_PATTERN
        );

        $this->create_variant_settings(
            self::VISA_POSTEPAY_OPTION,
            $legacy_settings,
            self::POSTEPAY_VISA_TITLE_PATTERN
        );

        // Disable the legacy PostePay method
        $legacy_settings['enabled'] = 'no';
        update_option( self::LEGACY_POSTEPAY_OPTION, $legacy_settings );
    }
}
