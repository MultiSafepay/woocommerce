<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services\Qr;

use Exception;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\WooCommerce\Services\PaymentMethodService;
use MultiSafepay\WooCommerce\Utils\Logger;
use MultiSafepay\WooCommerce\Utils\Order;
use MultiSafepay\WooCommerce\Utils\QrCheckoutManager;

/**
 * Class QrPaymentComponentService
 *
 * @package MultiSafepay\WooCommerce\Services\Qr
 */
class QrPaymentComponentService {

    /**
     * @var PaymentMethodService
     */
    public $payment_method_service;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ApiTokenService constructor.
     */
    public function __construct() {
        $this->payment_method_service = new PaymentMethodService();
        $this->logger                 = new Logger();
    }

    /**
     * Place a MultiSafepay transaction with QR code
     *
     * @return void
     */
    public function set_multisafepay_qr_code_transaction(): void {
        $payment_component_arguments_nonce = sanitize_key( $_POST['nonce'] ?? '' );
        if ( ! wp_verify_nonce( wp_unslash( $payment_component_arguments_nonce ), 'payment_component_arguments_nonce' ) ) {
            wp_send_json( array() );
        }
        $gateway_id = sanitize_key( $_POST['gateway_id'] ?? '' );
        $payload    = sanitize_text_field( wp_unslash( $_POST['payload'] ?? '' ) );

        if ( empty( $gateway_id ) || empty( $payload ) ) {
            return;
        }
        $woocommerce_payment_gateway = $this->payment_method_service->get_woocommerce_payment_gateway_by_id( $gateway_id );
        if ( ! is_null( $woocommerce_payment_gateway ) &&
            ( $woocommerce_payment_gateway->is_qr_enabled() || $woocommerce_payment_gateway->is_qr_only_enabled() )
        ) {
            try {
                $qr_checkout_manager                   = new QrCheckoutManager();
                $checkout_fields                       = $qr_checkout_manager->get_checkout_data();
                $multisafepay_qr_code_transaction_data = array();

                if ( $qr_checkout_manager->validate_checkout_fields() ) {
                    $shopping_cart_order_service           = new QrOrderService();
                    $multisafepay_qr_code_transaction_data = $shopping_cart_order_service->place_order( $woocommerce_payment_gateway, $payload, $checkout_fields );
                }

                wp_send_json( $multisafepay_qr_code_transaction_data );

            } catch ( Exception | InvalidArgumentException $exception ) {
                $this->logger->log_error( 'Arguments for QR could not be collected: ' . $exception->getMessage() );
            }
        }
    }


    /**
     * Return redirect URL where meta value key is 'multisafepay_transaction_id' and value is $order_id
     *
     * @return void
     */
    public function get_qr_order_redirect_url(): void {
        $payment_component_arguments_nonce = sanitize_key( $_POST['nonce'] ?? '' );
        if ( ! wp_verify_nonce( wp_unslash( $payment_component_arguments_nonce ), 'payment_component_arguments_nonce' ) ) {
            wp_send_json( array() );
        }

        $order_id      = sanitize_text_field( wp_unslash( $_POST['order_id'] ?? '' ) );
        $raw_qr_status = sanitize_text_field( wp_unslash( $_POST['qr_status'] ?? '' ) );
        $qr_status     = sanitize_key( $raw_qr_status );

        // Declined/cancelled QR outcomes do not create an order, so skip polling and redirect immediately.
        if ( in_array( $qr_status, array( 'declined', 'cancelled' ), true ) ) {
            $this->maybe_add_qr_status_notice( $qr_status );

            wp_send_json(
                array(
                    'success'      => true,
                    'redirect_url' => $this->get_qr_fallback_redirect_url(),
                )
            );
        }

        for ( $count = 0; $count < 5; $count++ ) {
            // Get WooCommerce Order ID where meta value key is 'multisafepay_transaction_id' and value is $order_id
            $woocommerce_order_id = Order::get_order_id_by_multisafepay_transaction_id_key( $order_id );

            if ( ! $woocommerce_order_id ) {
                sleep( 2 );
                continue;
            }

            $woocommerce_order = wc_get_order( $woocommerce_order_id );

            wp_send_json(
                array(
                    'success'      => true,
                    'redirect_url' => $woocommerce_order->get_checkout_order_received_url(),
                )
            );
        }

        wp_send_json(
            array(
                'success'      => true,
                'redirect_url' => $this->get_qr_fallback_redirect_url(),
            )
        );

        exit();
    }

    /**
     * Returns the fallback redirect URL for cancelled/failed QR outcomes.
     *
     * Based on multisafepay_redirect_after_cancel, this can point to cart or checkout.
     *
     * @return string
     */
    private function get_qr_fallback_redirect_url(): string {
        return ( get_option( 'multisafepay_redirect_after_cancel', 'cart' ) === 'cart' )
            ? wc_get_cart_url()
            : wc_get_checkout_url();
    }

    /**
     * Add a user-facing WooCommerce notice for QR failure outcomes.
     *
     * @param string $qr_status
     * @return void
     */
    private function maybe_add_qr_status_notice( string $qr_status ): void {
        if (
            ! function_exists( 'wc_add_notice' ) ||
            ! did_action( 'woocommerce_init' ) ||
            ! function_exists( 'WC' ) ||
            ! WC() ||
            ! WC()->session
        ) {
            return;
        }

        switch ( $qr_status ) {
            case 'declined':
                $notice_message = __( 'Your payment was declined. Please choose another payment method or try again.', 'multisafepay' );
                break;
            case 'cancelled':
                $notice_message = __( 'Your payment was cancelled. Please try again to complete your order.', 'multisafepay' );
                break;
            default:
                return;
        }

        if ( function_exists( 'wc_has_notice' ) && wc_has_notice( $notice_message, 'error' ) ) {
            return;
        }

        wc_add_notice( $notice_message, 'error' );
    }
}
