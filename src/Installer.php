<?php


namespace MultiSafepay\WooCommerce;

use MultiSafepay\WooCommerce\PaymentMethods\MultiSafepay;

class Installer
{
    const gateways = [
        MultiSafepay::class
    ];

    public static function register(): void
    {
        add_filter('woocommerce_payment_gateways', [self::class, 'getGateways']);
        add_action( 'plugins_loaded', [self::class, 'initMultiSafepayPaymentMethods'] );

        new Tabs();
    }

    public static function getGateways($gateways)
    {
        return array_merge($gateways, self::gateways);
    }

    public function initMultiSafepayPaymentMethods()
    {
        foreach (self::gateways as $gateway) {
            new $gateway();
        }
    }
}
