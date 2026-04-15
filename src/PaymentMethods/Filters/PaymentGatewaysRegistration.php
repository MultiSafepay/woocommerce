<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Filters;

use MultiSafepay\WooCommerce\Services\PaymentMethodService;

/**
 * Register MultiSafepay gateways in WooCommerce.
 */
class PaymentGatewaysRegistration {

    /**
     * @var PaymentMethodService|null
     */
    private $payment_method_service;

    /**
     * @param PaymentMethodService|null $payment_method_service
     */
    public function __construct( ?PaymentMethodService $payment_method_service = null ) {
        $this->payment_method_service = $payment_method_service;
    }

    /**
     * Merge existing gateways and MultiSafepay gateways.
     *
     * @param array $gateways
     * @return array
     */
    public function get_woocommerce_payment_gateways( array $gateways ): array {
        $multisafepay_woocommerce_payment_gateways = $this->get_payment_method_service()->get_woocommerce_payment_gateways();

        return array_merge( $gateways, $multisafepay_woocommerce_payment_gateways );
    }

    /**
     * Lazy-load the service to avoid SDK setup during plugin initialization.
     *
     * @return PaymentMethodService
     */
    private function get_payment_method_service(): PaymentMethodService {
        if ( null === $this->payment_method_service ) {
            $this->payment_method_service = new PaymentMethodService();
        }

        return $this->payment_method_service;
    }
}
