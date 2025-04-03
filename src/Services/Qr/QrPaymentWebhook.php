<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services\Qr;

use Exception;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\Util\Notification;
use MultiSafepay\WooCommerce\Services\PaymentMethodService;
use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\WooCommerce\Utils\Hpos;
use MultiSafepay\WooCommerce\Utils\Logger;
use MultiSafepay\WooCommerce\Utils\Order;
use WC_Data_Exception;
use WC_Order;
use WP_REST_Request;

/**
 * Class QrPaymentWebhook
 *
 * @package MultiSafepay\WooCommerce\Services\Qr
 */
class QrPaymentWebhook {

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
     * Create a WooCommerce order based on QR transaction
     *
     * @param TransactionResponse $multisafepay_transaction
     * @return void
     */
    public function create_woocommerce_order( TransactionResponse $multisafepay_transaction ): void {
        $multisafepay_order_id = $multisafepay_transaction->getOrderId();

        $checkout_data = $this->get_checkout_data( $multisafepay_order_id );

        if ( empty( $checkout_data ) ) {
            $this->logger->log_error( 'Could not create order for order id ' . $multisafepay_order_id . ' because checkout data is empty' );
            return;
        }

        try {
            $order = wc_create_order();

            foreach ( $checkout_data['cart'] as $item ) {
                $arguments = $this->get_product_arguments( $item );
                $order->add_product(
                    wc_get_product( $item['product_id'] ),
                    $item['quantity'],
                    $arguments
                );
            }

            // Customer Address
            $order = $this->set_address( $order, $checkout_data['customer'] );

            // Shipping Method
            if ( ! empty( $checkout_data['shipping'] ) ) {
                $order->add_shipping( $checkout_data['shipping'] );
            }

            // Coupons
            if ( ! empty( $checkout_data['coupons'] ) ) {
                foreach ( $checkout_data['coupons'] as $coupon ) {
                    $order->apply_coupon( $coupon );
                }
            }

            // Fees
            if ( ! empty( $checkout_data['fees'] ) ) {
                foreach ( $checkout_data['fees'] as $fee ) {
                    $order->add_fee( $fee );
                }
            }

            // Order Notes
            if ( ! empty( $checkout_data['order']['order_comments'] ) ) {
                $order->set_customer_note( $checkout_data['order']['order_comments'] );
            }

            // Order attribution
            foreach ( $checkout_data['order'] as $checkout_order_item_key => $checkout_order_item_data ) {
                if ( strpos( $checkout_order_item_key, 'wc_order_attribution' ) === 0 ) {
                    Hpos::update_meta( $order, '_' . $checkout_order_item_key, $checkout_order_item_data );
                }
            }

            // IP Address
            if ( ! empty( $checkout_data['order']['ip_address'] ) ) {
                $order->set_customer_ip_address( $checkout_data['order']['ip_address'] );
            }

            // User Agent
            if ( ! empty( $checkout_data['order']['user_agent'] ) ) {
                $order->set_customer_user_agent( $checkout_data['order']['user_agent'] );
            }

            // Any other data
            if ( ! empty( $checkout_data['other'] ) ) {
                foreach ( $checkout_data['other'] as $other_key => $other_value ) {
                    Hpos::update_meta( $order, '_' . $other_key, $other_value );
                }
            }

            // Temporary created order ID as metadata using multisafepay_transaction_id as key
            Hpos::update_meta( $order, 'multisafepay_transaction_id', $multisafepay_order_id );

            $order->calculate_totals();

            // Setting up payment method
            $woocommerce_payment_gateway = ( new PaymentMethodService() )->get_woocommerce_payment_gateway_by_id( $checkout_data['order']['payment_method'] );
            if ( $woocommerce_payment_gateway ) {
                $order->set_payment_method( $woocommerce_payment_gateway );
            }

            // Set the order status as payment complete
            $payment_complete = $order->payment_complete( $multisafepay_transaction->getTransactionId() );

            if ( $payment_complete ) {
                Hpos::update_meta( $order, '_multisafepay_order_environment', get_option( 'multisafepay_testmode', false ) ? 'test' : 'live' );
            }

            header( 'Content-type: text/plain' );
            die( 'OK' );

        } catch ( Exception | WC_Data_Exception  $exception ) {
            $this->logger->log_error( 'Something went wrong when creating WooCommerce order for MultiSafepay order id ' . $multisafepay_order_id . ' with message: ' . $exception->getMessage() );

            header( 'Content-type: text/plain' );
            die( 'OK' );
        }
    }

    /**
     * Build arguments for adding a product to the order
     *
     * @param array $item
     * @return array
     */
    public function get_product_arguments( array $item ): array {
        $arguments = array();

        if ( ! empty( $item['variation_id'] ) ) {
            $arguments['variation_id'] = $item['variation_id'];
        }

        if ( ! empty( $item['variation'] ) ) {
            $arguments['variation'] = $item['variation'];
        }

        if ( ! empty( $item['line_subtotal'] ) ) {
            $arguments['subtotal'] = $item['line_subtotal'];
        }

        if ( ! empty( $item['line_total'] ) ) {
            $arguments['total'] = $item['line_total'];
        }

        if ( ! empty( $item['data'] ) ) {
            $arguments['name'] = $item['data']->name;
        }

        return $arguments;
    }

    /**
     * Set address for the order
     *
     * @param WC_Order $order
     * @param array    $customer
     * @return WC_Order
     */
    private function set_address( WC_Order $order, array $customer ): WC_Order {
        $order->set_address(
            array(
                'first_name' => $customer['billing']['first_name'],
                'last_name'  => $customer['billing']['last_name'],
                'email'      => $customer['billing']['email'],
                'phone'      => $customer['billing']['phone'],
                'address_1'  => $customer['billing']['address_1'],
                'address_2'  => $customer['billing']['address_2'],
                'city'       => $customer['billing']['city'],
                'postcode'   => $customer['billing']['postcode'],
                'country'    => $customer['billing']['country'],
                'state'      => $customer['billing']['state'],
            )
        );

        $order->set_address(
            array(
                'first_name' => ! empty( $customer['shipping']['first_name'] ) ? $customer['shipping']['first_name'] : $customer['billing']['first_name'],
                'last_name'  => ! empty( $customer['shipping']['last_name'] ) ? $customer['shipping']['last_name'] : $customer['billing']['last_name'],
                'address_1'  => ! empty( $customer['shipping']['address_1'] ) ? $customer['shipping']['address_1'] : $customer['billing']['address_1'],
                'address_2'  => ! empty( $customer['shipping']['address_2'] ) ? $customer['shipping']['address_2'] : $customer['billing']['address_2'],
                'city'       => ! empty( $customer['shipping']['city'] ) ? $customer['shipping']['city'] : $customer['billing']['city'],
                'postcode'   => ! empty( $customer['shipping']['postcode'] ) ? $customer['shipping']['postcode'] : $customer['billing']['postcode'],
                'country'    => ! empty( $customer['shipping']['country'] ) ? $customer['shipping']['country'] : $customer['billing']['country'],
                'state'      => ! empty( $customer['shipping']['state'] ) ? $customer['shipping']['state'] : $customer['billing']['state'],
            ),
            'shipping'
        );

        return $order;
    }

    /**
     * @param string $multisafepay_order_id
     * @return array
     */
    private function get_checkout_data( string $multisafepay_order_id ): array {
        $transient_key = 'multisafepay_qr_order_' . $multisafepay_order_id;
        $checkout_data = get_transient( $transient_key );

        if ( $checkout_data ) {
            return $checkout_data;
        }

        return array();
    }

    /**
     * @param WP_REST_Request $request
     * @return void
     */
    public function process_webhook( WP_REST_Request $request ): void {
        try {
            $multisafepay_transaction = $this->validate_webhook_request( $request );
            $this->create_woocommerce_order( $multisafepay_transaction );
        } catch ( Exception | InvalidArgumentException $exception ) {
            $this->logger->log_error( 'Something went wrong when processing webhook for transaction id ' . ( $request->get_param( 'transactionid' ) ?? 'unknown' ) . ' with message: ' . $exception->getMessage() );
            header( 'Content-type: text/plain' );
            die( 'OK' );
        }
    }

    /**
     * Validate webhook request
     *
     * @param WP_REST_Request $request
     * @return ?TransactionResponse
     * @throws InvalidArgumentException
     */
    private function validate_webhook_request( WP_REST_Request $request ): ?TransactionResponse {
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

        if ( Transaction::COMPLETED !== $multisafepay_transaction->getStatus() ) {
            header( 'Content-type: text/plain' );
            die( 'OK' );
        }

        return $multisafepay_transaction;
    }

    /**
     * Process balancer
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function process_balancer( WP_REST_Request $request ): void {
        $order_id = sanitize_text_field( wp_unslash( $request->get_param( 'transactionid' ) ?? '' ) );

        if ( ! $request->sanitize_params() ) {
            $this->logger->log_info( 'Notification for transactionid . ' . $order_id . ' has been received but could not be sanitized' );
            header( 'Content-type: text/plain' );
            die( 'OK' );
        }

        for ( $count = 0; $count < 5; $count++ ) {
            // Get WooCommerce Order ID where meta value key is 'multisafepay_transaction_id' and vale is $order_id
            $woocommerce_order_id = Order::get_order_id_by_multisafepay_transaction_id_key( $order_id );

            if ( ! $woocommerce_order_id ) {
                sleep( 2 );
                continue;
            }

            $woocommerce_order = wc_get_order( $woocommerce_order_id );

            wp_safe_redirect( $woocommerce_order->get_checkout_order_received_url(), 302 );
            exit;
        }

        $redirect_url = ( get_option( 'multisafepay_redirect_after_cancel', 'cart' ) === 'cart' ) ?
            wc_get_cart_url() :
            wc_get_checkout_url();

        wp_safe_redirect( $redirect_url, 302 );
        exit;
    }

    /**
     * Process webhook
     *
     * @return void
     */
    public function multisafepay_register_rest_route_qr_balancer(): void {
        $arguments = array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'process_balancer' ),
            'permission_callback' => function() {
                return '';
            },
        );
        register_rest_route(
            'multisafepay/v1',
            'qr-balancer',
            $arguments
        );
    }

    /**
     * Process webhook
     *
     * @return void
     */
    public function multisafepay_register_rest_route_qr_notification(): void {
        $arguments = array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'process_webhook' ),
            'permission_callback' => function() {
                return '';
            },
        );
        register_rest_route(
            'multisafepay/v1',
            'qr-notification',
            $arguments
        );
    }
}
