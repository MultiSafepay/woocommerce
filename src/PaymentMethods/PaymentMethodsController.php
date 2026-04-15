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
use MultiSafepay\WooCommerce\Utils\RestResponseBuilder;
use Psr\Http\Client\ClientExceptionInterface;
use WC_Data_Exception;
use WC_Order;
use WP_REST_Request;
use WP_REST_Response;

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
     * @return WP_REST_Response
     * @throws WC_Data_Exception
     */
    public function process_post_notification( WP_REST_Request $request ): WP_REST_Response {
        $transactionid = $request->get_param( 'transactionid' );

        if ( ! $request->sanitize_params() ) {
            $this->logger->log_info( 'Notification for transactionid ' . $transactionid . ' has been received but could not be sanitized' );
            return RestResponseBuilder::build_response();
        }

        $payload_type = $request->get_param( 'payload_type' ) ?? '';
        if ( 'pretransaction' === $payload_type ) {
            $this->logger->log_info( 'Notification for transactionid ' . $transactionid . ' has been received but is going to be ignored, because is pretransaction type' );
            return RestResponseBuilder::build_response();
        }

        $auth    = (string) ( $request->get_header( 'auth' ) ?? '' );
        $body    = $request->get_body();
        $api_key = ( new SdkService() )->get_api_key();

        if ( '' === $auth ) {
            $this->logger->log_info( 'Notification for transactionid ' . $transactionid . ' has been received but auth header is missing' );
            return RestResponseBuilder::build_response();
        }

        $verify_notification = Notification::verifyNotification( $body, $auth, $api_key );

        if ( ! $verify_notification ) {
            $this->logger->log_info( 'Notification for transactionid ' . $transactionid . ' has been received but is not validated' );
            return RestResponseBuilder::build_response();
        }

        if ( get_option( 'multisafepay_debugmode', false ) ) {
            $this->logger->log_info( 'Notification has been received and validated for transaction id ' . $transactionid );

            if ( ! empty( $body ) ) {
                $this->logger->log_info( 'Body of the POST notification: ' . wc_print_r( $body, true ) );
            }
        }

        $multisafepay_transaction = new TransactionResponse( $request->get_json_params(), $body );
        ( new PaymentMethodCallback( (string) $transactionid, $multisafepay_transaction ) )->process_callback();

        return RestResponseBuilder::build_response();
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
            'permission_callback' => '__return_true',
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
