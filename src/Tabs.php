<?php


namespace MultiSafepay\WooCommerce;


use MultiSafepay\WooCommerce\Tabs\SettingsTab;
use MultiSafepay\WooCommerce\Tabs\SupportTab;

class Tabs
{
    public function __construct()
    {
        add_filter('woocommerce_settings_tabs_array', [self::class ,'addSettingsTab'], 50);
        new SupportTab();
        new SettingsTab();
    }

    public static function addSettingsTab($tabs)
    {
        $tabs['multisafepay_settings'] = __('MultiSafepay settings', 'multisafepay');
        $tabs['multisafepay_support'] = __('MultiSafepay Support', 'multisafepay');
        return $tabs;
    }
}
