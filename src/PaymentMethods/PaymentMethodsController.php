<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods;

use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\Util\Notification;
use MultiSafepay\WooCommerce\Services\OrderService;
use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\WooCommerce\Utils\Logger;
use WC_Order;
use WP_REST_Request;
use WP_REST_Response;
use MultiSafepay\WooCommerce\Services\CustomerService;

/**
 * The payment methods controller.
 *
 * Defines all the functionalities needed to register the Payment Methods actions and filters
 *
 * @since   4.0.0
 */
class PaymentMethodsController {

	/**
	 * Register the stylesheets related with the payment methods
	 *
     * @see https://developer.wordpress.org/reference/functions/wp_enqueue_style/
     *
     * @return void
	 */
	public function enqueue_styles(): void {
	    if ( is_checkout() ) {
            wp_enqueue_style( 'multisafepay-public-css', MULTISAFEPAY_PLUGIN_URL . '/assets/public/css/multisafepay-public.css', array(), MULTISAFEPAY_PLUGIN_VERSION, 'all' );
        }
	}

    /**
     * Merge existing gateways and MultiSafepay Gateways
     *
     * @param array $gateways
     * @return array
     */
    public static function get_gateways( array $gateways ): array {
        return array_merge( $gateways, Gateways::GATEWAYS );
    }

    /**
     * Filter the payment methods by the countries defined in their settings
     *
     * @param   array $payment_gateways
     * @return  array
     */
    public function filter_gateway_per_country( array $payment_gateways ): array {
        $customer_country = ( WC()->customer ) ? WC()->customer->get_billing_country() : false;
        foreach ( $payment_gateways as $gateway_id => $gateway ) {
            if ( ! empty( $gateway->countries ) && $customer_country && ! in_array( $customer_country, $gateway->countries, true ) ) {
                unset( $payment_gateways[ $gateway_id ] );
            }
        }
        return $payment_gateways;
    }

    /**
     * Filter the payment methods by min amount defined in their settings
     *
     * @param   array $payment_gateways
     * @return  array
     */
    public function filter_gateway_per_min_amount( array $payment_gateways ): array {
        $total_amount = ( WC()->cart ) ? WC()->cart->total : false;
        foreach ( $payment_gateways as $gateway_id => $gateway ) {
            if ( ! empty( $gateway->min_amount ) && $total_amount < $gateway->min_amount ) {
                unset( $payment_gateways[ $gateway_id ] );
            }
        }
        return $payment_gateways;
    }

    /**
     * Set the MultiSafepay transaction as shipped when the order
     * status change to the one defined as shipped in the settings.
     *
     * @param   int $order_id
     * @return  void
     */
    public function set_multisafepay_transaction_as_shipped( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( strpos( $order->get_payment_method(), 'multisafepay_' ) !== false ) {
            $sdk                 = new SdkService();
            $transaction_manager = $sdk->get_transaction_manager();
            $update_order        = new UpdateRequest();
            $update_order->addStatus( 'shipped' );
            $transaction_manager->update( (string) $order->get_order_number(), $update_order );
        }
    }

    /**
     * Set the MultiSafepay transaction as invoiced when the order
     * status change to the one defined as invoiced in the settings.
     *
     * @param   int $order_id
     * @return  void
     */
    public function set_multisafepay_transaction_as_invoiced( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( strpos( $order->get_payment_method(), 'multisafepay_' ) !== false ) {
            $sdk                 = new SdkService();
            $transaction_manager = $sdk->get_transaction_manager();
            $update_order        = new UpdateRequest();
            $update_order->addData( array( 'invoice_id' => $order->get_order_number() ) );
            $transaction_manager->update( (string) $order->get_order_number(), $update_order );
        }
    }

    /**
     * Action added to wp_loaded hook.
     * Handles notifications from transactions created before 4.X.X plugin version
     *
     * @return void
     */
    public static function deprecated_callback() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['page'] ) && 'multisafepaynotify' === $_GET['page'] ) {
            $required_args = array( 'transactionid', 'timestamp' );
            foreach ( $required_args as $arg ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                if ( ! isset( $_GET[ $arg ] ) || empty( $_GET[ $arg ] ) ) {
                    wp_die( esc_html__( 'Invalid request', 'multisafepay' ), esc_html__( 'Invalid request', 'multisafepay' ), 400 );
                }
            }
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ( new PaymentMethodCallback( (string) $_GET['transactionid'] ) )->process_callback();
        }
    }

    /**
     * Catch the notification request.
     *
     * @return  void
     */
    public function callback(): void {
        $required_args = array( 'transactionid', 'timestamp' );
        foreach ( $required_args as $arg ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( ! isset( $_GET[ $arg ] ) || empty( $_GET[ $arg ] ) ) {
                wp_die( esc_html__( 'Invalid request', 'multisafepay' ), esc_html__( 'Invalid request', 'multisafepay' ), 400 );
            }
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ( new PaymentMethodCallback( (string) $_GET['transactionid'] ) )->process_callback();
    }


    /**
     * Process the POST notification
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function process_post_notification( WP_REST_Request $request ): void {
        $timestamp           = $request->get_param( 'timestamp' );
        $transactionid       = $request->get_param( 'transactionid' );
        $auth                = $request->get_header( 'auth' );
        $body                = $request->get_body();
        $api_key             = ( new SdkService() )->get_api_key();
        $verify_notification = Notification::verifyNotification( $body, $auth, $api_key );

        if ( ! $verify_notification ) {
            Logger::log_info( 'Notification for transactionid . ' . $transactionid . ' has been received but is not validated' );
            header( 'Content-type: text/plain' );
            die( 'OK' );
        }

        if ( get_option( 'multisafepay_debugmode', false ) ) {
            Logger::log_info( 'Notification has been received and validated for transaction id ' . $transactionid );

            if ( ! empty( $body ) ) {
                Logger::log_info( 'Body of the POST notification: ' . wc_print_r( $body, true ) );
            }
        }

        $multisafepay_transaction = new TransactionResponse( $request->get_json_params(), $body );
        ( new PaymentMethodCallback( (string) $transactionid, $multisafepay_transaction ) )->process_callback();

    }

    /**
     * Register the endpoint to handle the POST notification
     */
    public function multisafepay_register_rest_route() {
        $arguments = array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'process_post_notification' ),
            'permission_callback' => function() {
				return ''; },
        );
        register_rest_route(
            'multisafepay/v1',
            'notification',
            $arguments
        );
    }


    /**
     * Action added to woocommerce_new_order hook.
     * Takes an order generated in admin and pass the data to MultiSafepay to process the order request.
     *
     * @param   int $order_id
     * @return  void
     */
    public function generate_orders_from_backend( int $order_id ): void {

        $order = wc_get_order( $order_id );

        // Check if the order is created in admin
        if ( ! $order || ! $order->is_created_via( 'admin' ) ) {
            return;
        }

        // Check if the payment method belongs to MultiSafepay
        if ( strpos( $order->get_payment_method(), 'multisafepay_' ) === false ) {
            return;
        }

        // Create the order request and process the transaction
        $sdk                 = new SdkService();
        $transaction_manager = $sdk->get_transaction_manager();
        $order_service       = new OrderService();
        $gateway_object      = Gateways::get_payment_method_object_by_payment_method_id( $order->get_payment_method() );
        $gateway_code        = $gateway_object->get_payment_method_code();
        $gateway_info        = $gateway_object->get_gateway_info();
        $order_request       = $order_service->create_order_request( $order, $gateway_code, 'paymentlink', $gateway_info );
        $transaction         = $transaction_manager->create( $order_request );

        if ( $transaction->getPaymentUrl() ) {
            // Update order meta data with the payment link
            update_post_meta( $order_id, 'payment_url', $transaction->getPaymentUrl() );
            update_post_meta( $order_id, 'send_payment_link', '1' );

            if ( get_option( 'multisafepay_debugmode', false ) ) {
                $message = 'Order details has been registered in MultiSafepay and a payment link has been generated: ' . esc_url( $transaction->getPaymentUrl() );
                Logger::log_info( $message );
                $order->add_order_note( $message );
            }
        }
    }

    /**
     * @param string   $default_payment_link
     * @param WC_Order $order
     */
    public function replace_checkout_payment_url( string $default_payment_link, WC_Order $order ) {
        $send_payment_link = get_post_meta( $order->get_id(), 'send_payment_link', true );
        if ( $send_payment_link ) {
            return get_post_meta( $order->get_id(), 'payment_url', true );
        }
        return $default_payment_link;
    }

    /**
     * Filter used to get WooCommerce order id  from order number returned in notification URL
     * since this one is the value pass in the Order Request
     *
     * @param string $transactionid The order number id received in callback notification function
     *
     * @return int
     */
    public function multisafepay_transaction_order_id( string $transactionid ): int {
        if ( function_exists( 'wc_seq_order_number_pro' ) ) {
            return (int) wc_seq_order_number_pro()->find_order_by_order_number( $transactionid );
        }
        if ( function_exists( 'wc_sequential_order_numbers' ) ) {
            return (int) wc_sequential_order_numbers()->find_order_by_order_number( $transactionid );
        }
        return (int) $transactionid;
    }

    /**
     * Filter used to introduce the on-hold status as valid order status to cancel
     * an order via cancel_url
     *
     * @param array    $order_status
     * @param WC_Order $order
     *
     * @return array
     */
    public function allow_cancel_multisafepay_orders_with_on_hold_status( array $order_status, WC_Order $order ): array {
        if ( strpos( $order->get_payment_method(), 'multisafepay_' ) !== false ) {
            $gateway              = Gateways::GATEWAYS[ $order->get_payment_method() ];
            $initial_order_status = ( new $gateway() )->initial_order_status;
            // If the MultiSafepay gateway initial order status is wc-on-hold
            if ( 'wc-on-hold' === $initial_order_status ) {
                array_push( $order_status, 'on-hold' );
            }
        }
        return $order_status;
    }

    /**
     * Return the credit card component arguments require to process a pre-order
     *
     * @return void
     */
    public function get_credit_card_payment_component_arguments(): void {
        if ( wp_verify_nonce( $_POST['nonce'], 'credit_card_payment_component_arguments_nonce' ) ) {
            $locale      = strtoupper( substr( ( new CustomerService() )->get_locale(), 0, 2 ) );
            $sdk_service = new SdkService();

            $credit_card_payment_component_arguments = array(
                'debug'      => (bool) get_option( 'multisafepay_debugmode', false ),
                'env'        => $sdk_service->get_test_mode() ? 'test' : 'live',
                'api_token'  => $sdk_service->get_api_token(),
                'orderData'  => array(
                    'currency'  => get_woocommerce_currency(),
                    'amount'    => ( WC()->cart ) ? ( WC()->cart->total * 100 ) : null,
                    'customer'  => array(
                        'locale'    => ( new CustomerService() )->get_locale(),
                        'country'   => ( WC()->customer )->get_billing_country(),
                        'reference' => null,
                    ),
                    'template'  => array(
                        'settings' => array(
                            'embed_mode' => true,
                        ),
                    ),
                    'recurring' => array(
                        'model' => null,
                    ),
                ),
                'ajax_url'   => admin_url( 'admin-ajax.php' ),
                'nonce'      => wp_create_nonce( 'credit_card_payment_component_arguments_nonce' ),
                'gateway_id' => $_POST['gateway_id'],
                'gateway'    => $_POST['gateway'],

            );
            wp_send_json( $credit_card_payment_component_arguments );
        }
    }

}
