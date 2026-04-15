<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Filters;

use MultiSafepay\WooCommerce\Services\PaymentMethodService;
use MultiSafepay\WooCommerce\Utils\Logger;
use MultiSafepay\WooCommerce\Utils\Order as OrderUtil;
use WC_Order;

/**
 * Allow order cancellation for MultiSafepay orders configured with on-hold initial status.
 */
class CancelOrderStatuses {

    /**
     * @var PaymentMethodService|null
     */
    private $payment_method_service;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param PaymentMethodService|null $payment_method_service
     * @param Logger|null               $logger
     */
    public function __construct( ?PaymentMethodService $payment_method_service = null, ?Logger $logger = null ) {
        $this->payment_method_service = $payment_method_service;
        $this->logger                 = $logger ?? new Logger();
    }

    /**
     * Filter used to introduce on-hold as a valid order status to cancel an order via cancel_url.
     *
     * @param array    $order_status
     * @param WC_Order $order
     * @return array
     */
    public function allow_cancel_multisafepay_orders_with_on_hold_status( array $order_status, WC_Order $order ): array {
        if ( OrderUtil::is_multisafepay_order( $order ) ) {
            $payment_method = $order->get_payment_method();
            $gateway        = $this->get_payment_method_service()->get_woocommerce_payment_gateway_by_id( $payment_method );
            if ( ! $gateway ) {
                $this->logger->log_error(
                    sprintf(
                        'CancelOrderStatuses: gateway not found for order_id=%d payment_method=%s',
                        $order->get_id(),
                        $payment_method
                    )
                );
                return $order_status;
            }
            $initial_order_status = $gateway->initial_order_status;
            if ( 'wc-on-hold' === $initial_order_status && ! in_array( 'on-hold', $order_status, true ) ) {
                $order_status[] = 'on-hold';
            }
        }

        return $order_status;
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
