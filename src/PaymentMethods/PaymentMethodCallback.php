<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods;

use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\WooCommerce\Services\PaymentMethodService;
use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\WooCommerce\Settings\SettingsFields;
use MultiSafepay\WooCommerce\Utils\Logger;
use Psr\Http\Client\ClientExceptionInterface;
use WC_Data_Exception;
use WC_Order;

/**
 * The payment method callback handle the notification process.
 */
class PaymentMethodCallback {

    public const CREDIT_CARD_GATEWAYS = array( 'VISA', 'MASTERCARD', 'AMEX', 'MAESTRO' );

    /**
     * The WooCommerce Order Id.
     *
     * @var      int    The WooCommerce Order Id.
     */
    private $woocommerce_order_id;

    /**
     * The MultiSafepay Order Id.
     *
     * @var      string    The MultiSafepay Order Id.
     */
    private $multisafepay_order_id;

    /**
     * The time stamp of the callback
     *
     * @var     string    The timestamp
     */
    private $time_stamp;

    /**
     * The WooCommerce order object
     *
     * @var     WC_Order    The WooCommerce order object
     */
    private $order;

    /**
     * The MultiSafepay transaction
     *
     * @var     TransactionResponse    The MultiSafepay transaction
     */
    private $multisafepay_transaction;

    /**
     * PaymentMethodCallback constructor
     *
     * @param  string               $multisafepay_order_id
     * @param  ?TransactionResponse $multisafepay_transaction
     */
    public function __construct( string $multisafepay_order_id, $multisafepay_transaction = null ) {
        $this->multisafepay_order_id = $multisafepay_order_id;

        if ( ! isset( $multisafepay_transaction ) ) {
            $multisafepay_transaction = $this->get_transaction();
        }

        $this->multisafepay_transaction = $multisafepay_transaction;

        // For most transactions, var2 contains the order id since the order request is being register using order number
        if ( ! empty( $this->multisafepay_transaction->getVar2() ) ) {
            $this->woocommerce_order_id = (int) $this->multisafepay_transaction->getVar2();
        }

        // In case we need it, a filter to set the right order id, based on order number
        if ( empty( $this->multisafepay_transaction->getVar2() ) ) {
            $this->woocommerce_order_id = apply_filters( 'multisafepay_transaction_order_id', $this->multisafepay_order_id );
        }

        $this->time_stamp = date( 'd/m/Y H:i:s' );
        $this->order      = wc_get_order( $this->woocommerce_order_id );
    }

    /**
     * Return the MultiSafepay Transaction
     *
     * @return TransactionResponse
     */
    private function get_transaction(): TransactionResponse {
        $transaction_manager = ( new SdkService() )->get_transaction_manager();
        try {
            $transaction = $transaction_manager->get( $this->multisafepay_order_id );
        } catch ( ClientExceptionInterface $client_exception ) {
            Logger::log_error( $client_exception->getMessage() );
            wp_die( esc_html__( 'Invalid request', 'multisafepay' ), esc_html__( 'Invalid request', 'multisafepay' ), 400 );
        } catch ( ApiException $api_exception ) {
            Logger::log_error( $api_exception->getMessage() );
            wp_die( esc_html__( 'Invalid request', 'multisafepay' ), esc_html__( 'Invalid request', 'multisafepay' ), 400 );
        }
        return $transaction;
    }

    /**
     * Return the WooCommerce order status
     *
     * @return string
     */
    private function get_wc_order_status(): string {
        return $this->order->get_status();
    }

    /**
     * Return the gateway code registered in MultiSafepay for this transaction
     *
     * @return string
     */
    private function get_multisafepay_transaction_gateway_code(): string {
        $code = $this->multisafepay_transaction->getPaymentDetails()->getType();
        if (
            in_array( $code, self::CREDIT_CARD_GATEWAYS, true ) &&
            get_option( 'multisafepay_group_credit_cards', false )
        ) {
            $code = 'CREDITCARD';
        }
        if ( strpos( $code, 'Coupon::' ) !== false ) {
            $data = $this->multisafepay_transaction->getPaymentDetails()->getData();
            return $data['coupon_brand'];
        }
        return $code;
    }

    /**
     * Return the status of the transaction in MultiSafepay
     *
     * @return string
     */
    private function get_multisafepay_transaction_status(): string {
        return $this->multisafepay_transaction->getStatus();
    }

    /**
     * Return the PSP ID
     *
     * @return string
     */
    private function get_multisafepay_transaction_id(): string {
        return $this->multisafepay_transaction->getTransactionId();
    }

    /**
     * Return if the order status is the same as the final
     * order status configured in the plugin settings
     *
     * @param  string $order_status
     * @return bool
     */
    private function is_completed_the_final_status( string $order_status ): bool {
        $final_order_status = get_option( 'multisafepay_final_order_status', false );
        return $final_order_status && ( 'completed' === $order_status );
    }

    /**
     * Check if the order status should be updated or not
     *
     * @return bool
     */
    private function should_status_be_updated(): bool {
        // Check if the WooCommerce completed order status is considered as the final one
        if ( $this->is_completed_the_final_status( $this->get_wc_order_status() ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $message = 'It seems a notification is trying to process an order which already has defined completed as the final order status. For this reason notification is being ignored. Transaction ID received is ' . sanitize_text_field( (string) wp_unslash( $this->get_multisafepay_transaction_id() ) ) . ' with status ' . $this->get_multisafepay_transaction_status();
            Logger::log_warning( $message );
            $this->order->add_order_note( $message );
            return false;
        }

        // The order status can be updated
        return true;
    }

    /**
     * Process the callback.
     *
     * @return void
     * @throws WC_Data_Exception
     */
    public function process_callback(): void {
        // On pre-transactions notification, and using sequential order numbers plugins, var 2 is not received in the notification, then order doesn't exist
        if ( ! $this->order ) {
            if ( get_option( 'multisafepay_debugmode', false ) ) {
                Logger::log_info( 'Notification has been received for the transaction ID ' . $this->multisafepay_order_id . ' but WooCommerce order object has not been found' );
            }
            header( 'Content-type: text/plain' );
            die( 'OK' );
        }

        // If payment method of the order does not belong to MultiSafepay
        if ( strpos( $this->order->get_payment_method(), 'multisafepay_' ) === false ) {
            if ( get_option( 'multisafepay_debugmode', false ) ) {
                $message = 'It seems a notification is trying to process an order processed by another payment method. Transaction ID received is ' . $this->order->get_id();
                Logger::log_info( $message );
            }
            header( 'Content-type: text/plain' );
            die( 'OK' );
        }

        if ( $this->get_wc_order_status() === 'trash' ) {
            if ( get_option( 'multisafepay_debugmode', false ) ) {
                $message = 'It seems a notification is trying to change the order status, but the order has been moved to the trash. Transaction ID received is ' . $this->order->get_id() . ' and transaction status is ' . $this->get_multisafepay_transaction_status();
                Logger::log_info( $message );
                $this->order->add_order_note( $message );
            }
            header( 'Content-type: text/plain' );
            die( 'OK' );
        }

        // If the transaction status is partial_refunded, we just register a new order note.
        if ( $this->get_multisafepay_transaction_status() === Transaction::PARTIAL_REFUNDED ) {
            $message = 'A partial refund has been registered within MultiSafepay Control for Order ID: ' . $this->woocommerce_order_id . ' and Order Number: ' . $this->multisafepay_order_id;
            $this->order->add_order_note( $message );
            header( 'Content-type: text/plain' );
            die( 'OK' );
        }

        $registered_by_multisafepay_payment_method_object = ( new PaymentMethodService() )->get_woocommerce_payment_gateway_by_multisafepay_gateway_code( $this->get_multisafepay_transaction_gateway_code() );
        $payment_method_id_registered_by_multisafepay     = $registered_by_multisafepay_payment_method_object ? $registered_by_multisafepay_payment_method_object->get_payment_method_id() : false;
        $payment_method_title_registered_by_multisafepay  = $registered_by_multisafepay_payment_method_object ? $registered_by_multisafepay_payment_method_object->get_payment_method_title() : false;
        $payment_method_id_registered_by_wc               = $this->order->get_payment_method();
        $payment_method_title_registered_by_wc            = $this->order->get_payment_method_title();
        $registered_by_woocommerce_payment_method_object  = ( new PaymentMethodService() )->get_woocommerce_payment_gateway_by_id( $payment_method_id_registered_by_wc );
        $initial_order_status                             = $registered_by_woocommerce_payment_method_object ? $registered_by_woocommerce_payment_method_object->initial_order_status : false;
        $default_order_status                             = SettingsFields::get_multisafepay_order_statuses();

        // Check if the WooCommerce Order status do not match with the order status received in notification, to avoid to process repeated of notification.
        // Or if the custom initial order status of the gateway is different than the general one, and the MultiSafepay transaction status is initialized, and custom initial order status is different than the current WooCommerce order status
        if (
            $this->get_wc_order_status() !== str_replace( 'wc-', '', get_option( 'multisafepay_' . $this->get_multisafepay_transaction_status() . '_status', $default_order_status[ $this->get_multisafepay_transaction_status() . '_status' ]['default'] ) ) ||
            get_option( 'multisafepay_' . $this->get_multisafepay_transaction_status() . '_status', $default_order_status[ $this->get_multisafepay_transaction_status() . '_status' ]['default'] ) !== $initial_order_status && $this->get_multisafepay_transaction_status() === Transaction::INITIALIZED && $this->get_wc_order_status() !== $initial_order_status
        ) {

            // If MultiSafepay transaction status is initialized, check if there is a custom initial order status for this payment method.
            if ( $this->get_multisafepay_transaction_status() === Transaction::INITIALIZED ) {
                if ( $initial_order_status && 'wc-default' !== $initial_order_status && $this->get_wc_order_status() !== $initial_order_status ) {
                    $this->order->update_status( str_replace( 'wc-', '', $initial_order_status ), __( 'Transaction has been initialized.', 'multisafepay' ) );
                }
                if ( ! $initial_order_status || 'wc-default' === $initial_order_status ) {
                    $this->order->update_status( str_replace( 'wc-', '', get_option( 'multisafepay_' . $this->get_multisafepay_transaction_status() . '_status', $default_order_status[ $this->get_multisafepay_transaction_status() . '_status' ]['default'] ) ) );
                }
            }

            // If MultiSafepay transaction status is completed, payment_complete function will handle the order status change
            if ( $this->get_multisafepay_transaction_status() === Transaction::COMPLETED ) {
                $this->order->payment_complete( $this->get_multisafepay_transaction_id() );
            }

            // If MultiSafepay transaction status is not completed and not initialized, process the notification according order status settings
            if (
                $this->get_multisafepay_transaction_status() !== 'completed' &&
                $this->get_multisafepay_transaction_status() !== 'initialized' &&
                $this->should_status_be_updated()
            ) {
                $this->order->update_status( str_replace( 'wc-', '', get_option( 'multisafepay_' . $this->get_multisafepay_transaction_status() . '_status', $default_order_status[ $this->get_multisafepay_transaction_status() . '_status' ]['default'] ) ) );
            }

            if ( get_option( 'multisafepay_debugmode', false ) ) {
                $message = 'Callback received for Order ID: ' . $this->woocommerce_order_id . ' and Order Number: ' . $this->multisafepay_order_id . ' on ' . $this->time_stamp . ' with status: ' . $this->get_multisafepay_transaction_status() . ' and PSP ID: ' . $this->get_multisafepay_transaction_id() . '.';
                Logger::log_info( $message );
                $this->order->add_order_note( $message );
            }
        }

        // If the payment method changed in MultiSafepay payment page, after leave WooCommerce checkout page
        if ( $payment_method_id_registered_by_multisafepay && $payment_method_id_registered_by_wc !== $payment_method_id_registered_by_multisafepay ) {
            if ( get_option( 'multisafepay_debugmode', false ) ) {
                $message = 'Callback received with a different payment method for Order ID: ' . $this->woocommerce_order_id . ' and Order Number: ' . $this->multisafepay_order_id . ' on ' . $this->time_stamp . '. Payment method changed from ' . $payment_method_title_registered_by_wc . ' to ' . $payment_method_title_registered_by_multisafepay . '.';
                Logger::log_info( $message );
                $this->order->add_order_note( $message );
            }

            $this->order = wc_get_order( $this->woocommerce_order_id );
            $this->order->set_payment_method( $registered_by_multisafepay_payment_method_object );
            $this->order->save();
        }

        header( 'Content-type: text/plain' );
        die( 'OK' );
    }
}
