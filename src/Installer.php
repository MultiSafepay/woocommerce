<?php declare(strict_types=1);


namespace MultiSafepay\WooCommerce;

use MultiSafepay\WooCommerce\PaymentMethods\MultiSafepay;
use MultiSafepay\WooCommerce\Tabs\SettingsTab;

class Installer
{
    const gateways = [MultiSafepay::class];

    /**
     * Register the required filters and actions
     *
     * @return void
     */
    public static function register(): void
    {
        add_filter('woocommerce_payment_gateways', [self::class, 'getGateways']);
        add_action('plugins_loaded', [self::class, 'initMultiSafepayPaymentMethods']);
        add_filter('plugin_action_links_multisafepay/multisafepay.php', [self::class, 'addPluginLinks']);

        new Tabs();
    }

    /**
     * Merge existing gateways and MultiSafepay Gateways
     *
     * @param array $gateways
     * @return array
     */
    public static function getGateways(array $gateways): array
    {
        return array_merge($gateways, self::gateways);
    }

    /**
     * Initialize all MultiSafepay payment method instances with all specific settings for that gateway
     *
     * @return void
     */
    public function initMultiSafepayPaymentMethods(): void
    {
        foreach (self::gateways as $gateway) {
            new $gateway();
        }
    }

    public static function addPluginLinks($links)
    {
        return array_merge([
            '<a href="' . SettingsTab::getTabUrl() . '">' . __('Settings', 'multisafepay') . '</a>',
            '<a target="_blank" href="https://docs.multisafepay.com/integrations/plugins/woocommerce/">' . __('Docs', 'multisafepay') . '</a>',
            '<a target="_blank" href="https://docs.multisafepay.com/integrations/plugins/woocommerce/#introduction">' . __('Support', 'multisafepay') . '</a>',
        ], $links);
    }
}
