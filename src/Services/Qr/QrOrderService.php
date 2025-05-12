<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services\Qr;

use MultiSafepay\Api\Transactions\Gateways as GatewaysSdk;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\SecondChance;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\Services\OrderService;
use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\WooCommerce\Utils\Logger;
use MultiSafepay\WooCommerce\Utils\MoneyUtil;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Class QrOrderService
 *
 * @package MultiSafepay\WooCommerce\Services\Qr
 */
class QrOrderService extends OrderService {

    /**
     * Expiration time for payment methods API request
     */
    public const EXPIRATION_TIME_CHECKOUT_QR_DATA = 86400;

    /**
     * @var QrCustomerService
     */
    public $qr_customer_service;

    /**
     * @var QrShoppingCartService
     */
    public $qr_shopping_cart_service;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ShoppingCartOrderService constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->qr_customer_service      = new QrCustomerService();
        $this->qr_shopping_cart_service = new QrShoppingCartService();
        $this->logger                   = new Logger();
    }

    /**
     * @param string $order_id
     * @param string $gateway_code
     * @param string $payload
     * @param array  $checkout_fields
     * @return OrderRequest
     * @throws InvalidArgumentException
     */
    public function get_order_request(
        string $order_id,
        string $gateway_code,
        string $payload,
        array $checkout_fields
    ): OrderRequest {
        $cart          = WC()->cart;
        $order_request = new OrderRequest();
        $order_request
            ->addOrderId( $order_id )
            ->addMoney( MoneyUtil::create_money( (float) $cart->get_total( 'edit' ), get_woocommerce_currency() ) )
            ->addGatewayCode( $gateway_code )
            ->addType( OrderRequest::DIRECT_TYPE )
            ->addPluginDetails( $this->create_plugin_details() )
            ->addDescriptionText( $this->get_order_description_text( $order_id ) )
            ->addCustomer( $this->qr_customer_service->create_customer_details_from_cart( $checkout_fields['customer'] ) )
            ->addPaymentOptions( $this->create_payment_options( $this->generate_token( $order_id ) ) )
            ->addSecondsActive( $this->get_seconds_active() )
            ->addSecondChance( ( new SecondChance() )->addSendEmail( (bool) get_option( 'multisafepay_second_chance', false ) ) )
            ->addData( array( 'var2' => $order_id ) );

        if ( $this->is_filled_shipping_address( $checkout_fields ) && WC()->cart->needs_shipping() ) {
            $order_request->addDelivery( $this->qr_customer_service->create_customer_details_from_cart( $checkout_fields['customer'], 'shipping' ) );
        }

        if ( ! get_option( 'multisafepay_disable_shopping_cart', false ) || in_array( $gateway_code, GatewaysSdk::SHOPPING_CART_REQUIRED_GATEWAYS, true ) ) {
            $order_request->addShoppingCart( $this->qr_shopping_cart_service->create_shopping_cart( $cart, get_woocommerce_currency() ) );
        }

        if ( ! empty( $payload ) ) {
            $order_request->addData(
                array(
                    'payment_data' => array(
                        'gateway' => $gateway_code,
                        'payload' => $payload,
                    ),
                )
            );
        }

        $order_request = $this->add_none_tax_rate( $order_request );

        return apply_filters( 'multisafepay_order_request', $order_request );
    }

    /**
     * Check if the shipping address is filled
     *
     * @param array $checkout_fields
     * @return bool
     */
    public function is_filled_shipping_address( array $checkout_fields ): bool {
        $filled_fields = $checkout_fields['customer']['shipping'] ?? array();
        return ! empty( $filled_fields['address_1'] ) || ! empty( $filled_fields['address_2'] );
    }

    /**
     * @param string $token
     * @return PaymentOptions
     */
    public function create_payment_options( string $token ): PaymentOptions {
        $redirect_cancel_url = add_query_arg( 'token', $token, get_rest_url( get_current_blog_id(), 'multisafepay/v1/qr-balancer' ) );
        $payment_options     = new PaymentOptions();
        $payment_options->addNotificationUrl( get_rest_url( get_current_blog_id(), 'multisafepay/v1/qr-notification' ) );
        $payment_options->addCancelUrl( $redirect_cancel_url );
        $payment_options->addRedirectUrl( $redirect_cancel_url );
        $payment_options->addSettings( array( 'qr' => array( 'enabled' => true ) ) );

        return $payment_options;
    }

    /**
     * Generate a token that will be used on validation of QR related endpoints
     *
     * @param string $order_id
     * @return string
     */
    public function generate_token( string $order_id ) {
        $token = wp_generate_password( 32, false );
        set_transient( 'multisafepay_token_' . $order_id, $token, 86400 );
        return $token;
    }

    /**
     * Check if the QR data is valid
     *
     * @param array  $qr_data
     * @param string $order_id
     * @return bool
     */
    public function is_valid_qr_data( array $qr_data, string $order_id ): bool {
        $required_fields = array(
            'image'    => $qr_data['qr']['image'] ?? null,
            'token'    => $qr_data['qr']['params']['token'] ?? null,
            'order_id' => $qr_data['order_id'] ?? null,
        );

        foreach ( $required_fields as $field => $value ) {
            if ( empty( $value ) || ( ( 'order_id' === $field ) && ( (string) $value !== $order_id ) ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate a unique order ID for cart-based transactions
     *
     * @return string
     */
    public function generate_unique_order_id(): string {
        return 'QR-' . uniqid( '', true );
    }

    /**
     * Activate the QR code for the order
     *
     * @param BasePaymentMethod $payment_gateway
     * @param string            $payload
     * @param array             $checkout_fields
     * @return array
     * @throws InvalidArgumentException
     */
    public function place_order( BasePaymentMethod $payment_gateway, string $payload, array $checkout_fields ): array {
        $order_id = $this->generate_unique_order_id();

        // Create a transient using order ID and the checkout fields
        set_transient( 'multisafepay_qr_order_' . $order_id, $checkout_fields, self::EXPIRATION_TIME_CHECKOUT_QR_DATA );

        $order_request = $this->get_order_request(
            $order_id,
            $payment_gateway->get_payment_method_gateway_code(),
            $payload,
            $checkout_fields
        );

        $sdk                 = new SdkService();
        $transaction_manager = $sdk->get_transaction_manager();

        try {
            $transaction = $transaction_manager->create( $order_request );
        } catch ( ApiException | ClientExceptionInterface $exception ) {
            $this->logger->log_error( $exception->getMessage() );
            wc_add_notice( __( 'There was a problem processing your payment using a QR code. Please try again later or contact with us.', 'multisafepay' ), 'error' );
            return array();
        }

        $payment_data = $transaction->getData();

        $this->logger->log_info( 'Start MultiSafepay transaction for the order ID ' . $order_id . ' on ' . date( 'd/m/Y H:i:s' ) . ' with payment data ' . wp_json_encode( $payment_data ) );

        if ( $this->is_valid_qr_data( $payment_data, $order_id ) ) {
            return $payment_data;
        }

        $this->logger->log_error( 'QR code was not correct' );

        return array();
    }
}
