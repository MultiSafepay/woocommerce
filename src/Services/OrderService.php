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

namespace MultiSafepay\WooCommerce\Services;

use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GoogleAnalytics;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\SecondChance;
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
    private $customer_service;

    /**
     * @var ShoppingCartService
     */
    private $shopping_cart_service;

    /**
     * OrderService constructor.
     */
    public function __construct() {
        $this->customer_service      = new CustomerService();
        $this->shopping_cart_service = new ShoppingCartService();
    }

    /**
     * @param WC_Order             $order
     * @param string               $gateway_code
     * @param string               $type
     * @param GatewayInfoInterface $gateway_info
     * @return OrderRequest
     */
    public function create_order_request( WC_Order $order, string $gateway_code, string $type, GatewayInfoInterface $gateway_info = null ): OrderRequest {

        $time_active      = get_option( 'multisafepay_time_active', '30' );
        $time_active_unit = get_option( 'multisafepay_time_unit', 'days' );

        if ( 'days' === $time_active_unit ) {
            $time_active = $time_active * 24 * 60 * 60;
        }
        if ( 'hours' === $time_active_unit ) {
            $time_active = $time_active * 60 * 60;
        }

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
            ->addShoppingCart( $this->shopping_cart_service->create_shopping_cart( $order, $order->get_currency() ) )
            ->addSecondsActive( $time_active )
            ->addSecondChance( ( new SecondChance() )->addSendEmail( (bool) get_option( 'multisafepay_second_chance', false ) ) )
            ->addData( array( 'var2' => $order->get_id() ) );

        if ( $order->needs_shipping_address() ) {
            $order_request->addDelivery( $this->customer_service->create_delivery_details( $order ) );
        }

        $ga_code = get_option( 'multisafepay_ga', false );
        if ( $ga_code ) {
            $order_request->addGoogleAnalytics( ( new GoogleAnalytics() )->addAccountId( $ga_code ) );
        }

        if ( $gateway_info ) {
            $order_request->addGatewayInfo( $gateway_info );
        }

        return apply_filters( 'multisafepay_order_request', $order_request );

    }

    /**
     * @return PluginDetails
     */
    private function create_plugin_details() {
        $plugin_details = new PluginDetails();
        global $wp_version;
        return $plugin_details
            ->addApplicationName( 'Wordpress-WooCommerce' )
            ->addApplicationVersion( 'WordPress version: ' . $wp_version . '. WooCommerce version: ' . WC_VERSION )
            ->addPluginVersion( MULTISAFEPAY_PLUGIN_VERSION )
            ->addShopRootUrl( get_bloginfo( 'url' ) );
    }

    /**
     * @param   WC_Order $order
     * @return  PaymentOptions
     */
    private function create_payment_options( WC_Order $order ): PaymentOptions {
        $url_redirect_on_cancel = ( get_option( 'multisafepay_redirect_after_cancel', 'cart' ) === 'cart' ? '' : wc_get_checkout_url() );
        $payment_options        = new PaymentOptions();
        return $payment_options
            ->addNotificationUrl( get_rest_url( get_current_blog_id(), 'multisafepay/v1/notification' ) )
            ->addCancelUrl( wp_specialchars_decode( $order->get_cancel_order_url( $url_redirect_on_cancel ) ) )
            ->addRedirectUrl( $order->get_checkout_order_received_url() );
    }

    /**
     * Return the order description.
     *
     * @param   string $order_number
     * @return  string   $order_description
     */
    private function get_order_description_text( $order_number ):string {
        /* translators: %s: order id */
        $order_description = sprintf( __( 'Payment for order: %s', 'multisafepay' ), $order_number );
        if ( get_option( 'multisafepay_order_request_description', false ) ) {
            $order_description = str_replace( '{order_number}', $order_number, get_option( 'multisafepay_order_request_description', false ) );
        }
        return $order_description;
    }

}
