<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods;

use Exception;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\Api\Wallets\ApplePay\MerchantSessionRequest;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Util\Notification;
use MultiSafepay\WooCommerce\Services\OrderService;
use MultiSafepay\WooCommerce\Services\PaymentMethodService;
use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\WooCommerce\Utils\Hpos;
use MultiSafepay\WooCommerce\Utils\Logger;
use MultiSafepay\WooCommerce\Utils\Order as OrderUtil;
use Psr\Http\Client\ClientExceptionInterface;
use WC_Data_Exception;
use WC_Order;
use WP_REST_Request;

/**
 * Defines all the methods needed to register related with Payment Methods actions and filters
 */
class PaymentMethodsController {

    public const VALIDATION_URL_KEY = 'validation_url';
    public const ORIGIN_DOMAIN_KEY  = 'origin_domain';

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
    public function get_woocommerce_payment_gateways( array $gateways ): array {
        $multisafepay_woocommerce_payment_gateways = ( new PaymentMethodService() )->get_woocommerce_payment_gateways();
        return array_merge( $gateways, $multisafepay_woocommerce_payment_gateways );
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

        foreach ( $payment_gateways as $gateway_id => $gateway ) {
            if ( ! empty( $gateway->min_amount ) && $total_amount < $gateway->min_amount ) {
                unset( $payment_gateways[ $gateway_id ] );
            }
        }
        return $payment_gateways;
    }

    /**
     * Filter the payment methods by user role defined in payment gateway settings
     *
     * @param   array $payment_gateways
     * @return  array
     */
    public function filter_gateway_per_user_roles( array $payment_gateways ): array {
        $user_roles = is_user_logged_in() ? wp_get_current_user()->roles : array();

        foreach ( $payment_gateways as $gateway_id => $gateway ) {
            if ( ! empty( $gateway->settings['user_roles'] ) && ! array_intersect( $user_roles, $gateway->settings['user_roles'] ) ) {
                unset( $payment_gateways[ $gateway_id ] );
            }
        }
        return $payment_gateways;
    }

    /**
     * Set the MultiSafepay transaction as shipped when the order
     * status change to the one defined as shipped in the settings.
     *
     * @param int $order_id
     * @return void
     * @throws ClientExceptionInterface
     */
    public function set_multisafepay_transaction_as_shipped( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( OrderUtil::is_multisafepay_order( $order ) ) {
            $sdk                 = new SdkService();
            $transaction_manager = $sdk->get_transaction_manager();
            $update_order        = new UpdateRequest();
            $update_order->addStatus( 'shipped' );
            try {
                $transaction_manager->update( (string) $order->get_order_number(), $update_order );
            } catch ( ApiException $api_exception ) {
                $this->logger->log_error( $api_exception->getMessage() );
                return;
            }
        }
    }

    /**
     * Set the MultiSafepay transaction as invoiced when the order
     * status change to the one defined as invoiced in the settings.
     *
     * @param   int $order_id
     * @return  void
     * @throws  ClientExceptionInterface
     */
    public function set_multisafepay_transaction_as_invoiced( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( OrderUtil::is_multisafepay_order( $order ) ) {
            $sdk                 = new SdkService();
            $transaction_manager = $sdk->get_transaction_manager();
            $update_order        = new UpdateRequest();
            $update_order->addData( array( 'invoice_id' => $order->get_order_number() ) );
            try {
                $transaction_manager->update( (string) $order->get_order_number(), $update_order );
            } catch ( ApiException $api_exception ) {
                $this->logger->log_error( $api_exception->getMessage() );
                return;
            }
        }
    }

    /**
     * Catch the notification request.
     *
     * @return  void
     * @throws  WC_Data_Exception
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
        if ( isset( $_GET['payload_type'] ) && 'pretransaction' === $_GET['payload_type'] ) {
            wp_die( esc_html__( 'Invalid request', 'multisafepay' ), esc_html__( 'Invalid request', 'multisafepay' ), 400 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
        $transactionid = sanitize_text_field( (string) wp_unslash( $_GET['transactionid'] ) );
        ( new PaymentMethodCallback( sanitize_text_field( (string) wp_unslash( $transactionid ) ) ) )->process_callback();
    }

    /**
     * Process the POST notification
     *
     * @param WP_REST_Request $request
     * @return void
     * @throws WC_Data_Exception
     */
    public function process_post_notification( WP_REST_Request $request ): void {
        $transactionid = $request->get_param( 'transactionid' );

        if ( ! $request->sanitize_params() ) {
            $this->logger->log_info( 'Notification for transactionid . ' . $transactionid . ' has been received but could not be sanitized' );
            header( 'Content-type: text/plain' );
            die( 'OK' );
        }

        $payload_type = $request->get_param( 'payload_type' ) ?? '';
        if ( 'pretransaction' === $payload_type ) {
            $this->logger->log_info( 'Notification for transactionid . ' . $transactionid . ' has been received but is going to be ignored, because is pretransaction type' );
            header( 'Content-type: text/plain' );
            die( 'OK' );
        }

        $auth                = $request->get_header( 'auth' );
        $body                = $request->get_body();
        $api_key             = ( new SdkService() )->get_api_key();
        $verify_notification = Notification::verifyNotification( $body, $auth, $api_key );

        if ( ! $verify_notification ) {
            $this->logger->log_info( 'Notification for transactionid . ' . $transactionid . ' has been received but is not validated' );
            header( 'Content-type: text/plain' );
            die( 'OK' );
        }

        if ( get_option( 'multisafepay_debugmode', false ) ) {
            $this->logger->log_info( 'Notification has been received and validated for transaction id ' . $transactionid );

            if ( ! empty( $body ) ) {
                $this->logger->log_info( 'Body of the POST notification: ' . wc_print_r( $body, true ) );
            }
        }

        $multisafepay_transaction = new TransactionResponse( $request->get_json_params(), $body );
        ( new PaymentMethodCallback( (string) $transactionid, $multisafepay_transaction ) )->process_callback();
    }

    /**
     * Register the endpoint to handle the POST notification
     *
     * @return void
     */
    public function multisafepay_register_rest_route() {
        $arguments = array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'process_post_notification' ),
            'permission_callback' => function() {
                return '';
            },
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
     * @param  int $order_id
     *
     * @return void
     */
    public function generate_orders_from_backend( int $order_id ): void {
        $order = wc_get_order( $order_id );

        // Check if the order is created in admin
        if ( ! $order || ! $order->is_created_via( 'admin' ) ) {
            return;
        }

        // Check if the payment method belongs to MultiSafepay
        if ( ! OrderUtil::is_multisafepay_order( $order ) ) {
            return;
        }

        // Create the order request and process the transaction
        $sdk                 = new SdkService();
        $transaction_manager = $sdk->get_transaction_manager();
        $order_service       = new OrderService();
        $gateway_object      = ( new PaymentMethodService() )->get_woocommerce_payment_gateway_by_id( $order->get_payment_method() );
        if ( ! $gateway_object ) {
            $this->logger->log_error( ' Gateway object is null ' );
            return;
        }
        $gateway_code  = $gateway_object->get_payment_method_gateway_code();
        $order_request = $order_service->create_order_request( $order, $gateway_code, 'paymentlink' );

        try {
            $transaction = $transaction_manager->create( $order_request );
            if ( $transaction->getPaymentUrl() ) {
                // Update order metadata with the payment link
                Hpos::update_meta( $order, 'payment_url', $transaction->getPaymentUrl() );
                Hpos::update_meta( $order, 'send_payment_link', '1' );

                if ( get_option( 'multisafepay_debugmode', false ) ) {
                    $message = 'Order details has been registered in MultiSafepay and a payment link has been generated: ' . esc_url( $transaction->getPaymentUrl() );
                    $this->logger->log_info( $message );
                    $order->add_order_note( $message );
                }
            }
        } catch ( Exception | ApiException | ClientExceptionInterface $exception ) {
            $this->logger->log_error( $exception->getMessage() );
        }
    }

    /**
     * @param string   $default_payment_link
     * @param WC_Order $order
     *
     * @return mixed|string
     */
    public function replace_checkout_payment_url( string $default_payment_link, WC_Order $order ) {
        $send_payment_link = Hpos::get_meta( $order, 'send_payment_link' );
        if ( $send_payment_link ) {
            return Hpos::get_meta( $order, 'payment_url' );
        }
        return $default_payment_link;
    }

    /**
     * Filter used to get WooCommerce order id  from order number returned in notification URL
     * since this one is the value pass in the Order Request
     *
     * @param string $transactionid The order number id received in callback notification function
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
     * @return array
     */
    public function allow_cancel_multisafepay_orders_with_on_hold_status( array $order_status, WC_Order $order ): array {
        if ( OrderUtil::is_multisafepay_order( $order ) ) {
            $gateway = ( new PaymentMethodService() )->get_woocommerce_payment_gateway_by_id( $order->get_payment_method() );
            if ( ! $gateway ) {
                $this->logger->log_error( ' Gateway object is null ' );
                return $order_status;
            }
            $initial_order_status = $gateway->initial_order_status;
            // If the MultiSafepay gateway initial order status is wc-on-hold
            if ( 'wc-on-hold' === $initial_order_status ) {
                $order_status[] = 'on-hold';
            }
        }

        return $order_status;
    }

    /**
     * Get the Apple Pay session arguments
     *
     * @return void
     */
    public function applepay_direct_validation(): void {
        $apple_session_arguments = $this->get_apple_pay_session_arguments();

        try {
            $waller_manager                     = ( new SdkService() )->get_sdk()->getWalletManager();
            $apple_pay_merchant_session_request = ( new MerchantSessionRequest() )
                ->addValidationUrl( $apple_session_arguments[ self::VALIDATION_URL_KEY ] )
                ->addOriginDomain( $apple_session_arguments[ self::ORIGIN_DOMAIN_KEY ] );

            wp_send_json(
                $waller_manager->createApplePayMerchantSession(
                    $apple_pay_merchant_session_request
                )->getMerchantSession()
            );
        } catch ( ApiException | Exception | ClientExceptionInterface $exception ) {
            $error_message = 'Error when trying to get the ApplePay session via MultiSafepay SDK';
            $this->logger->log_error( $error_message . ': ' . $exception->getMessage() );
            wp_send_json( array( 'message' => $error_message ) );
        }
    }

    /**
     * Get the updated total price to be used
     * by Google Pay, and Apple Pay direct
     *
     * @return void
     */
    public function get_updated_total_price(): void {
        $total_price_nonce = sanitize_key( $_POST['nonce'] ?? '' );
        if ( ! wp_verify_nonce( wp_unslash( $total_price_nonce ), 'total_price_nonce' ) ) {
            wp_send_json( array() );
        }
        wp_send_json(
            array(
                'totalPrice' => ( WC()->cart ) ? ( WC()->cart->get_total( '' ) * 100 ) : null,
            )
        );
    }

    /**
     * Validate the required input and return the values
     *
     * @return array
     */
    private function get_apple_pay_session_arguments(): array {
        $validation_url      = esc_url_raw( wp_unslash( $_POST['validation_url'] ?? '' ) );
        $origin_domain_parse = wp_parse_url( esc_url_raw( wp_unslash( $_POST['origin_domain'] ?? '' ) ) );
        $origin_domain       = $origin_domain_parse['host'];

        if ( empty( $validation_url ) ) {
            $this->logger->log_error( 'Error when trying to get the ApplePay session. Validation URL empty' );
            exit;
        }

        if ( empty( $origin_domain ) ) {
            $this->logger->log_error( 'Error when trying to get the ApplePay session. Origin domain empty' );
            exit;
        }

        return array(
            self::VALIDATION_URL_KEY => $validation_url,
            self::ORIGIN_DOMAIN_KEY  => $origin_domain,
        );
    }

    /**
     * Add a link to the MultiSafepay transaction ID in the order details page
     *
     * @param WC_Order $order
     * @return void
     */
    public function add_multisafepay_transaction_link( WC_Order $order ): void {
        $transaction_id = $order->get_transaction_id();
        $environment    = $order->get_meta( '_multisafepay_order_environment' );

        if ( empty( $transaction_id ) || ! is_numeric( $transaction_id ) || empty( $environment ) ) {
            return;
        }

        $test_mode = 'test' === $environment;
        $url       = 'https://' . ( $test_mode ? 'testmerchant' : 'merchant' ) . '.multisafepay.com/transaction/' . $transaction_id;

        wp_enqueue_script(
            'multisafepay-admin',
            MULTISAFEPAY_PLUGIN_URL . '/assets/admin/js/multisafepay-admin.js',
            array( 'jquery' ),
            MULTISAFEPAY_PLUGIN_VERSION,
            true
        );

        wp_localize_script(
            'multisafepay-admin',
            'multisafepayAdminData',
            array(
                'transactionUrl'       => esc_url( $url ),
                'transactionLinkTitle' => __( 'View transaction in the MultiSafepay dashboard', 'multisafepay' ),
            )
        );
    }
}
