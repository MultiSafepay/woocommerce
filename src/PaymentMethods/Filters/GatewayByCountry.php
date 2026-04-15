<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Filters;

use MultiSafepay\WooCommerce\Utils\Logger;

/**
 * Filter payment gateways by customer country.
 */
class GatewayByCountry {

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger|null $logger
     */
    public function __construct( ?Logger $logger = null ) {
        $this->logger = $logger ?? new Logger();
    }

    /**
     * Filter the payment methods by the countries defined in their settings.
     *
     * @param array $payment_gateways
     * @return array
     */
    public function filter_gateway_per_country( array $payment_gateways ): array {
        $customer_country = ( WC()->customer ) ? WC()->customer->get_billing_country() : false;
        foreach ( $payment_gateways as $gateway_id => $gateway ) {
            if ( ! empty( $gateway->countries ) && $customer_country && ! in_array( $customer_country, $gateway->countries, true ) ) {
                $this->logger->log_info( 'Payment method ' . $gateway_id . ' is being unset because the customer country ' . $customer_country . ' is not allowed' );
                unset( $payment_gateways[ $gateway_id ] );
            }
        }

        return $payment_gateways;
    }
}
