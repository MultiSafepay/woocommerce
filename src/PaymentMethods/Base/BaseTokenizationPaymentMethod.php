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

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\Services\CustomerService;
use MultiSafepay\WooCommerce\Services\OrderService;
use MultiSafepay\WooCommerce\Services\SdkService;
use WC_Payment_Tokens;

abstract class BaseTokenizationPaymentMethod extends BasePaymentMethod {

    /**
     * TokenizationPaymentMethod constructor.
     */
    public function __construct() {
        parent::__construct();
        if ( is_user_logged_in() && (bool) get_option( 'multisafepay_tokenization', false ) ) {
            $this->supports[] = 'tokenization';
        }
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * @param int $order_id
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function process_payment( $order_id ): array {
        if ( $this->canSubmitToken() === false ) {
            return parent::process_payment( $order_id );
        }
        $sdk                 = new SdkService();
        $transaction_manager = $sdk->get_transaction_manager();
        $order               = wc_get_order( $order_id );
        $customer            = ( new CustomerService() )->create_customer_details( $order )->addReference( (string) get_current_user_id() );
        $order_service       = new OrderService();
        $order_request       = $order_service->create_order_request( $order_id, $this->gateway_code, $this->type, $this->id, $this->get_gateway_info() );
        $order_request->addRecurringModel( 'cardOnFile' );
        $order_request->addCustomer( $customer );
        if (
            isset( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) && 'new' !== $_POST[ 'wc-' . $this->id . '-payment-token' ] ||
            ! isset( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) && 'true' !== $_POST[ 'wc-' . $this->id . '-new-payment-method' ]
        ) {
            $wc_token = WC_Payment_Tokens::get( $_POST[ 'wc-' . $this->id . '-payment-token' ] );
            $order_request->addType( 'direct' );
            $order_request->addRecurringId( $wc_token->get_token() );
        }
        $transaction = $transaction_manager->create( $order_request );
        if ( $this->initial_order_status && 'wc-default' !== $this->initial_order_status && $transaction->getPaymentUrl() ) {
            $order->update_status( str_replace( 'wc-', '', $this->initial_order_status ), __( 'Transaction has been initialized.', 'multisafepay' ) );
        }
        if ( ( ! $this->initial_order_status || 'wc-default' === $this->initial_order_status ) && $transaction->getPaymentUrl() ) {
            $order->update_status( str_replace( 'wc-', '', get_option( 'multisafepay_initialized_status', 'wc-pending' ) ), __( 'Transaction has been initialized.', 'multisafepay' ) );
        }
        return array(
            'result'   => 'success',
            'redirect' => esc_url_raw( $transaction->getPaymentUrl() ),
        );
    }

    /**
     * @return boolean
     */
    public function has_fields(): bool {
        return is_user_logged_in() && (bool) get_option( 'multisafepay_tokenization', false );
    }

    /**
     *
     * @return mixed
     */
    public function payment_fields() {

        if ( ! $this->has_fields() ) {
            return parent::payment_fields();
        }

        if ( $this->description ) {
            echo '<p>' . esc_html( $this->description ) . '</p>';
        }

        if ( is_wc_endpoint_url( 'add-payment-method' ) ) {
            $message = __( 'To save a credit card you must process an order with products and pass by checkout page, selecting  the checkbox "Save your credit card for the next purchase"', 'multisafepay' );
            echo '<p>' . esc_html( $message ) . '</p>';
        }

        if ( is_checkout() ) {
            $this->save_payment_method_checkbox();
        }

        if ( wc_get_customer_saved_methods_list( get_current_user_id() ) && is_checkout() ) {
            $this->saved_payment_methods();
        }

    }

    /**
     * Enqueue Javascript to customize the behavior of the checkout fields related with Tokenization.
     *
     * @return void
     */
    public function enqueue_scripts() {
        if ( is_checkout() || is_wc_endpoint_url( 'order-pay' ) ) {
            $multisafepay_vars = array(
                'id' => $this->id,
            );
            $route             = plugins_url( 'multisafepay/assets/public/js/multisafepay-tokenization.js' );
            wp_enqueue_script( 'multisafepay-' . $this->id . '-tokenization-js', $route, array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
            wp_localize_script( 'multisafepay-' . $this->id . '-tokenization-js', 'multisafepay', $multisafepay_vars );
            wp_enqueue_script( 'multisafepay-' . $this->id . '-tokenization-js' );
        }
    }

    /**
     * @return bool
     */
    private function canSubmitToken(): bool {

        if ( ! empty( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) && 'new' !== $_POST[ 'wc-' . $this->id . '-payment-token' ] ) {
            return true;
        }

        if ( ! empty( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) && 'new' === $_POST[ 'wc-' . $this->id . '-payment-token' ] && isset( $_POST[ 'wc-' . $this->id . '-new-payment-method' ] ) ) {
            return true;
        }

        if ( ! isset( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) && isset( $_POST[ 'wc-' . $this->id . '-new-payment-method' ] ) && 'true' === $_POST[ 'wc-' . $this->id . '-new-payment-method' ] ) {
            return true;
        }

        return false;
    }

    /**
     * Allows users add a new credit card from account - payment methods section
     * Currently, this is not allowed by MultiSafepay, therefore return a notice message.
     *
     * @return array
     */
    public function add_payment_method() {
        wc_add_notice( 'To save a credit card you must process an order with products and pass by checkout page, selecting  the checkbox "Save your credit card for the next purchase"', 'error' );
        return array(
            'result'   => 'error',
            'redirect' => wc_get_endpoint_url( 'add-payment-method' ),
        );
    }

}
