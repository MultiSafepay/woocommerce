<?php declare(strict_types=1);


namespace MultiSafepay\WooCommerce;


use MultiSafepay\WooCommerce\Tabs\SettingsTab;
use MultiSafepay\WooCommerce\Tabs\SupportTab;

class Tabs
{
    /**
     * Tabs constructor.
     */
    public function __construct()
    {
        add_filter('woocommerce_settings_tabs_array', [self::class , 'addSettingsTab'], 50);
        new SettingsTab();
    }

    /**
     * Add the tabs
     *
     * @param array $tabs
     * @return array
     */
    public static function addSettingsTab(array $tabs): array
    {
        $tabs['multisafepay_settings'] = 'MultiSafepay settings';
        return $tabs;
    }
}
