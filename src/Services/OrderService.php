<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services;

use MultiSafepay\Api\Transactions\Gateways as GatewaysSdk;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Wallet;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\SecondChance;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\TaxTable\TaxRate;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\TaxTable\TaxRule;
use MultiSafepay\WooCommerce\Utils\MoneyUtil;
use WC_Order;

/**
 * Class OrderService
 *
 * @package MultiSafepay\WooCommerce\Services
 */
class OrderService {

    /**
     * @var CustomerService
     */
    public $customer_service;

    /**
     * @var ShoppingCartService
     */
    public $shopping_cart_service;

    /**
     * @var PaymentMethodService
     */
    public $payment_method_service;

    /**
     * OrderService constructor.
     */
    public function __construct() {
        $this->customer_service       = new CustomerService();
        $this->shopping_cart_service  = new ShoppingCartService();
        $this->payment_method_service = new PaymentMethodService();
    }

    /**
     * @param WC_Order $order
     * @param string   $gateway_code
     * @param string   $type
     * @return OrderRequest
     */
    public function create_order_request( WC_Order $order, string $gateway_code, string $type ): OrderRequest {
        $order_request = new OrderRequest();
        $order_request
            ->addOrderId( $order->get_order_number() )
            ->addMoney( MoneyUtil::create_money( (float) ( $order->get_total() ), $order->get_currency() ) )
            ->addGatewayCode( $gateway_code )
            ->addType( $type )
            ->addPluginDetails( $this->create_plugin_details() )
            ->addDescriptionText( $this->get_order_description_text( $order->get_order_number() ) )
            ->addCustomer( $this->customer_service->create_customer_details( $order ) )
            ->addPaymentOptions( $this->create_payment_options( $order ) )
            ->addSecondsActive( $this->get_seconds_active() )
            ->addSecondChance( ( new SecondChance() )->addSendEmail( (bool) get_option( 'multisafepay_second_chance', false ) ) )
            ->addData( array( 'var2' => $order->get_id() ) );

        if ( $order->needs_shipping_address() && $order->has_shipping_address() ) {
            $order_request->addDelivery( $this->customer_service->create_delivery_details( $order ) );
        }

        if ( ! get_option( 'multisafepay_disable_shopping_cart', false ) || in_array( $gateway_code, GatewaysSdk::SHOPPING_CART_REQUIRED_GATEWAYS, true ) ) {
            $order_request->addShoppingCart( $this->shopping_cart_service->create_shopping_cart( $order, $order->get_currency(), $gateway_code ) );
        }

        if ( ! empty( $_POST[ $order->get_payment_method() . '_payment_component_payload' ] ) ) {
            $payment_method_id             = $order->get_payment_method();
            $payment_component_payload_key = $payment_method_id . '_payment_component_payload';
            $payment_component_payload     = sanitize_text_field( wp_unslash( $_POST[ $payment_component_payload_key ] ?? '' ) );
            if ( ! empty( $payment_component_payload ) ) {
                $order_request->addType( 'direct' );
                $order_request->addData(
                    array(
                        'payment_data' => array(
                            'payload' => $payment_component_payload,
                        ),
                    )
                );
            }
        }

        $payment_token = sanitize_text_field( wp_unslash( $_POST['payment_token'] ?? '' ) );
        if ( ! empty( $payment_token ) && ( ( 'APPLEPAY' === $gateway_code ) || ( 'GOOGLEPAY' === $gateway_code ) ) ) {
            $order_request->addType( 'direct' );
            $order_request->addGatewayInfo( ( new Wallet() )->addPaymentToken( $payment_token ) );
        }

        $order_request = $this->add_none_tax_rate( $order_request );

        return apply_filters( 'multisafepay_order_request', $order_request );
    }

    /**
     * @return PluginDetails
     */
    private function create_plugin_details(): PluginDetails {
        $plugin_details = new PluginDetails();
        global $wp_version;
        return $plugin_details
            ->addApplicationName( 'Wordpress-WooCommerce' )
            ->addApplicationVersion( 'WordPress version: ' . $wp_version . '. WooCommerce version: ' . WC_VERSION )
            ->addPluginVersion( MULTISAFEPAY_PLUGIN_VERSION )
            ->addShopRootUrl( get_bloginfo( 'url' ) );
    }

    /**
     * @param  WC_Order $order
     * @return PaymentOptions
     */
    private function create_payment_options( WC_Order $order ): PaymentOptions {
        $payment_options = new PaymentOptions();
        $payment_options->addNotificationUrl( get_rest_url( get_current_blog_id(), 'multisafepay/v1/notification' ) );

        $cancel_endpoint = ( get_option( 'multisafepay_redirect_after_cancel', 'cart' ) === 'cart' ? '' : wc_get_checkout_url() );
        $cancel_url      = wp_specialchars_decode( $order->get_cancel_order_url( $cancel_endpoint ) );

        if ( is_wc_endpoint_url( 'order-pay' ) ) {
            $cancel_url = wp_specialchars_decode( $order->get_checkout_payment_url() );
        }

        $payment_options->addCancelUrl( $cancel_url );
        $payment_options->addRedirectUrl( $order->get_checkout_order_received_url() );
        if ( ! apply_filters( 'multisafepay_post_notification', true ) ) {
            $payment_options->addNotificationUrl( add_query_arg( 'wc-api', 'multisafepay', home_url( '/' ) ) );
            $payment_options->addNotificationMethod( 'GET' );
        }
        return $payment_options;
    }

    /**
     * Return the order description.
     *
     * @param string $order_number
     * @return string $order_description
     */
    private function get_order_description_text( $order_number ): string {
        /* translators: %s: order id */
        $order_description = sprintf( __( 'Payment for order: %s', 'multisafepay' ), $order_number );
        if ( get_option( 'multisafepay_order_request_description', false ) ) {
            $order_description = str_replace( '{order_number}', $order_number, get_option( 'multisafepay_order_request_description', false ) );
        }
        return $order_description;
    }

    /**
     * Return the time active in seconds defined in the plugin settings page
     *
     * @return int
     */
    private function get_seconds_active(): int {
        $time_active      = get_option( 'multisafepay_time_active', '30' );
        $time_active_unit = get_option( 'multisafepay_time_unit', 'days' );
        if ( 'days' === $time_active_unit ) {
            $time_active = $time_active * 24 * 60 * 60;
        }
        if ( 'hours' === $time_active_unit ) {
            $time_active = $time_active * 60 * 60;
        }
        return $time_active;
    }

    /**
     * This method add a tax rate of 0, in case is not being created automatically by the shopping cart.
     * This is required to process refunds, based on shopping cart items
     *
     * @param OrderRequest $order_request
     * @return OrderRequest
     */
    public function add_none_tax_rate( OrderRequest $order_request ): OrderRequest {
        if ( $order_request->getShoppingCart() === null ) {
            return $order_request;
        }
        if ( $order_request->getCheckoutOptions()->getTaxTable() === null ) {
            return $order_request;
        }
        $shopping_cart = $order_request->getShoppingCart()->getData();
        if ( isset( $shopping_cart['items'] ) ) {
            foreach ( $shopping_cart['items'] as $item ) {
                if ( '0' === $item['tax_table_selector'] ) {
                    return $order_request;
                }
            }
        }
        $tax_rate = ( new TaxRate() )->addRate( 0 );
        $tax_rule = ( new TaxRule() )->addTaxRate( $tax_rate )->addName( '0' );
        $order_request->getCheckoutOptions()->getTaxTable()->addTaxRule( $tax_rule );
        return $order_request;
    }
}
