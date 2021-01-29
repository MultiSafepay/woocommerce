<?php declare(strict_types=1);

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MultiSafepay\WooCommerce\PaymentMethods;

use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\WooCommerce\Settings\SettingsFields;
use WC_Order;

/**
 * The payment method callback handle the notification process.
 * *
 *
 * @since   4.0.0
 */
class PaymentMethodCallback {

    /**
     * The transaction id.
     *
     * @var      int    The order ID.
     */
    private $order_id;

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
    private $transaction;

    /**
     * PaymentMethodCallback constructor.
     *
     * @param string $order_id
     */
    public function __construct( string $order_id ) {
        $this->order_id    = $order_id;
        $this->time_stamp  = date( 'd/m/Y H:i:s' );
        $this->order       = wc_get_order( $order_id );
        $this->transaction = $this->get_transaction();
    }

    /**
     * Return the MultiSafepay Transaction
     *
     * @return TransactionResponse
     */
    private function get_transaction(): TransactionResponse {
        $transaction_manager = ( new SdkService() )->get_transaction_manager();
        try {
            $transaction = $transaction_manager->get( $this->order_id );
            return $transaction;
        } catch ( ApiException $api_exception ) {
            if ( get_option( 'multisafepay_debugmode', false ) ) {
                $logger = wc_get_logger();
                $logger->log( 'error', $api_exception->getMessage() );
            }
            wp_die( esc_html__( 'Invalid request', 'multisafepay' ), esc_html__( 'Invalid request', 'multisafepay' ), 400 );
        }
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
        return $this->transaction->getPaymentDetails()->getType();
    }

    /**
     * Return the status of the transaction in MultiSafepay
     *
     * @return string
     */
    private function get_multisafepay_transaction_status(): string {
        return $this->transaction->getStatus();
    }

    /**
     * Return the PSP ID
     *
     * @return string
     */
    private function get_multisafepay_transaction_id(): string {
        return $this->transaction->getTransactionId();
    }

    /**
     * Process the callback.
     *
     * @return void
     */
    public function process_callback(): void {

        if ( strpos( $this->order->get_payment_method(), 'multisafepay_' ) === false ) {
            header( 'Content-type: text/plain' );
            die( 'OK' );
        }

        $payment_method_id_registered_by_msp    = Gateways::get_payment_method_id_by_gateway_code( $this->get_multisafepay_transaction_gateway_code() );
        $payment_method_id_registered_by_wc     = $this->order->get_payment_method();
        $payment_method_title_registered_by_msp = Gateways::get_payment_method_name_by_gateway_code( $this->get_multisafepay_transaction_gateway_code() );
        $payment_method_title_registered_by_wc  = $this->order->get_payment_method_title();
        $default_order_status                   = SettingsFields::get_msp_order_statuses();

        if ( $payment_method_id_registered_by_wc !== $payment_method_id_registered_by_msp ) {
            if ( get_option( 'multisafepay_debugmode', false ) ) {
                $logger  = wc_get_logger();
                $message = 'Callback received with a different payment method for Order ID: ' . $this->order_id . ' on ' . $this->time_stamp . '. Payment method pass from ' . $payment_method_title_registered_by_wc . ' to ' . $payment_method_title_registered_by_msp . '.';
                $logger->log( 'info', $message );
                $this->order->add_order_note( $message );
            }
            update_post_meta( $this->order_id, '_payment_method', $payment_method_id_registered_by_msp );
            update_post_meta( $this->order_id, '_payment_method_title', $payment_method_title_registered_by_msp );
        }

        if ( $this->get_wc_order_status() !== str_replace( 'wc-', '', get_option( 'multisafepay_' . $this->get_multisafepay_transaction_status() . '_status', $default_order_status[ $this->get_multisafepay_transaction_status() . '_status' ]['default'] ) ) ) {
            $this->order->update_status( str_replace( 'wc-', '', get_option( 'multisafepay_' . $this->get_multisafepay_transaction_status() . '_status', $default_order_status[ $this->get_multisafepay_transaction_status() . '_status' ]['default'] ) ) );
            if ( get_option( 'multisafepay_debugmode', false ) ) {
                $logger  = wc_get_logger();
                $message = 'Callback received for Order ID: ' . $this->order_id . ' on ' . $this->time_stamp . ' with status: ' . $this->get_multisafepay_transaction_status() . ' and PSP ID' . $this->get_multisafepay_transaction_id() . '.';
                $logger->log( 'info', $message );
                $this->order->add_order_note( $message );
            }
        }

        header( 'Content-type: text/plain' );
        die( 'OK' );

    }

}
