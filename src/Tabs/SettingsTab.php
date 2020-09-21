<?php


namespace MultiSafepay\WooCommerce\Tabs;


class SettingsTab
{
    public function __construct()
    {
        add_action('woocommerce_settings_tabs_multisafepay_settings', [self::class, 'fillTab']);
        add_action('woocommerce_update_options_multisafepay_settings', [self::class, 'updateSettings']);
    }


    public static function fillTab()
    {
        woocommerce_admin_fields(self::getSettings());
    }

    public static function updateSettings()
    {
        woocommerce_update_options(self::getSettings());
    }

    public static function getSettings()
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
