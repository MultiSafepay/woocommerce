<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use MultiSafepay\Exception\ApiException;
use MultiSafepay\WooCommerce\Services\CustomerService;
use MultiSafepay\WooCommerce\Services\OrderService;
use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\WooCommerce\Utils\Logger;
use WC_Payment_Tokens;

abstract class BaseTokenizationPaymentMethod extends BasePaymentMethod {


    /**
     * TokenizationPaymentMethod constructor.
     */
    public function __construct() {
        parent::__construct();
        if ( is_user_logged_in() && (bool) ( $this->get_option( 'tokenization', 'no' ) === 'yes' ) ) {
            $this->supports[] = 'tokenization';
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        }
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     *
     * @return array|mixed|void
     */
    public function process_payment( $order_id ) {
        if ( $this->canSubmitToken() === false ) {
            return parent::process_payment( $order_id );
        }

        $sdk                 = new SdkService();
        $transaction_manager = $sdk->get_transaction_manager();
        $order               = wc_get_order( $order_id );
        $customer            = ( new CustomerService() )->create_customer_details( $order )->addReference( (string) get_current_user_id() );
        $order_service       = new OrderService();
        $order_request       = $order_service->create_order_request( $order, $this->gateway_code, $this->type, $this->get_gateway_info() );
        $order_request->addRecurringModel( 'cardOnFile' );
        $order_request->addCustomer( $customer );

        if ( $this->is_order_using_token() ) {
            $wc_token = WC_Payment_Tokens::get( $_POST[ 'wc-' . $this->id . '-payment-token' ] );
            if ( $wc_token->get_user_id() !== get_current_user_id() ) {
                wc_add_notice( __( 'Error processing the order using a registered payment method', 'multisafepay' ), 'error' );
                return;
            }
            $order_request->addType( 'direct' );
            $order_request->addRecurringId( $wc_token->get_token() );
        }

        try {
            $transaction = $transaction_manager->create( $order_request );
        } catch ( ApiException $api_exception ) {
            Logger::log_error( $api_exception->getMessage() );
            wc_add_notice( $api_exception->getMessage(), 'error' );
            return;
        }

        return array(
            'result'   => 'success',
            'redirect' => esc_url_raw( $transaction->getPaymentUrl() ),
        );
    }

    /**
     * Return true if the order submit a token id
     *
     * @return boolean
     */
    private function is_order_using_token(): bool {
        // If isset a payment token in the request and this one is not the string "new"
        if ( isset( $_POST[ 'wc-' . $this->id . '-payment-token' ] ) && 'new' !== $_POST[ 'wc-' . $this->id . '-payment-token' ] ) {
            return true;
        }
        return false;
    }

    /**
     * @return boolean
     */
    public function has_fields(): bool {

        return is_user_logged_in() && (bool) ( $this->get_option( 'tokenization', 'no' ) === 'yes' );
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

        if ( ! empty( $this->get_tokens() ) && is_checkout() ) {
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
            $route             = MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-tokenization.js';
            wp_enqueue_script( 'multisafepay-tokenization-js', $route, array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
            wp_localize_script( 'multisafepay-tokenization-js', 'multisafepay', $multisafepay_vars );
            wp_enqueue_script( 'multisafepay-tokenization-js' );
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
