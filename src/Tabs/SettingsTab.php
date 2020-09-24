<?php declare(strict_types=1);


namespace MultiSafepay\WooCommerce\Tabs;


class SettingsTab
{
    /**
     * SettingsTab constructor.
     */
    public function __construct()
    {
        add_action('woocommerce_settings_tabs_multisafepay_settings', [self::class, 'fillTab']);
        add_action('woocommerce_update_options_multisafepay_settings', [self::class, 'updateSettings']);
    }


    /**
     * Add content to settings page
     *
     * @return void
     */
    public static function fillTab(): void
    {
        woocommerce_admin_fields(self::getSettings());
    }

    /**
     * Update the values of the settings page
     *
     * @return void
     */
    public static function updateSettings(): void
    {
        woocommerce_update_options(self::getSettings());
    }

    /**
     * List of all setting
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return apply_filters('wc_multisafepay_settings', [
            'section_title' => [
                'name' => 'MultiSafepay Settings',
                'type' => 'title',
                'desc' => '',
                'id' => 'wc_multisafepay_settings_section_title'
            ],
            'test_mode' => [
                'name' => 'Test mode',
                'type' => 'checkbox',
                'desc' => '',
                'id' => 'wc_multisafepay_settings_test_mode',
                'default' => 'off'
            ],
            'api_key' => [
                'name' => 'API key',
                'type' => 'text',
                'desc' => '',
                'id' => 'wc_multisafepay_settings_api_key',
            ],
            'section_end' => [
                'type' => 'sectionend',
                'id' => 'wc_multisafepay_settings_section_end'
            ]
        ]);
    }
}
