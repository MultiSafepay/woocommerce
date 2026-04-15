<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Filters;

use MultiSafepay\WooCommerce\Utils\Logger;

/**
 * Filter payment gateways by the minimum amount.
 */
class GatewayByMinAmount {

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
     * Filter the payment methods by min amount defined in their settings.
     *
     * @param array $payment_gateways
     * @return array
     */
    public function filter_gateway_per_min_amount( array $payment_gateways ): array {
        $total_amount = ( WC()->cart ) ? WC()->cart->get_total( '' ) : false;

        if ( is_wc_endpoint_url( 'order-pay' ) ) {
            $order_id = absint( get_query_var( 'order-pay' ) );
            if ( 0 < $order_id ) {
                $order = wc_get_order( $order_id );
                if ( $order ) {
                    $total_amount = (float) $order->get_total();
                }
            }
        }

        if ( false === $total_amount ) {
            return $payment_gateways;
        }

        $total_amount = (float) $total_amount;

        foreach ( $payment_gateways as $gateway_id => $gateway ) {
            if ( ! empty( $gateway->min_amount ) && $total_amount < $gateway->min_amount ) {
                $this->logger->log_info( 'Payment method ' . $gateway_id . ' is being unset because the total amount ' . $total_amount . ' is less than the min amount ' . $gateway->min_amount );
                unset( $payment_gateways[ $gateway_id ] );
            }
        }

        return $payment_gateways;
    }
}
