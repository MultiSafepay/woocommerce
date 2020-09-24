<?php declare(strict_types=1);


namespace MultiSafepay\WooCommerce;

use MultiSafepay\WooCommerce\PaymentMethods\MultiSafepay;

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
}
