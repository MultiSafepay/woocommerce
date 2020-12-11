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
 *
 */

namespace MultiSafepay\WooCommerce\Services;


use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GoogleAnalytics;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use MultiSafepay\WooCommerce\Utils\MoneyUtil;
use WC_Order;

/**
 * Class OrderService
 * @package MultiSafepay\WooCommerce\Services
 */
class OrderService
{

    /**
     * @var CustomerService
     */
    protected $customer_service;

    /**
     * @var ShoppingCartService
     */
    protected $shopping_cart_service;

    /**
     * OrderService constructor.
     */
    public function __construct()
    {
        $this->customer_service = new CustomerService();
        $this->shopping_cart_service = new ShoppingCartService();
    }

    /**
     * @param int $order_id
     * @param string $gateway_code
     * @param string $type
     * @param GatewayInfoInterface $gateway_info
     * @return OrderRequest
     */
    public function create_order_request(int $order_id, string $gateway_code, string $type, GatewayInfoInterface $gateway_info = null): OrderRequest
    {
        $order = wc_get_order($order_id);
        $time_active = get_option('multisafepay_time_active');
        $time_active_unit = get_option('multisafepay_time_unit');

        if ($time_active_unit === 'days') {
            $time_active = $time_active * 24 * 60 * 60;
        } elseif ($time_active_unit === 'hours') {
            $time_active = $time_active * 60 * 60;
        }

        $order_request = new OrderRequest();
        $order_request
            ->addOrderId($order->get_order_number())
            ->addMoney(MoneyUtil::createMoney((float)($order->get_total()), $order->get_currency()))
            ->addGatewayCode($gateway_code)
            ->addType($type)
            ->addPluginDetails($this->create_plugin_details())
            ->addDescriptionText('Payment for order: ' . $order->get_id())
            ->addCustomer($this->customer_service->create_customer_details($order))
            ->addPaymentOptions($this->create_payment_options($order))
            ->addShoppingCart($this->shopping_cart_service->create_shopping_cart($order, $order->get_currency()))
            ->addSecondsActive($time_active);

        if ($order->get_shipping_total() > 0) {
            $order_request->addDelivery($this->customer_service->create_delivery_details($order));
        }

        $ga_code = get_option('multisafepay_ga', false);
        if ($ga_code) {
            $order_request->addGoogleAnalytics((new GoogleAnalytics())->addAccountId($ga_code));
        }

        if ($gateway_info) {
            $order_request->addGatewayInfo($gateway_info);
        }

        return $order_request;
    }

    /**
     * @return PluginDetails
     */
    protected function create_plugin_details()
    {
        $plugin_details = new PluginDetails();
        global $wp_version;
        return $plugin_details
            ->addApplicationName('Wordpress-WooCommerce')
            ->addApplicationVersion('Wordpress version: ' . $wp_version . '. WooCommerce version: ' . WC_VERSION)
            ->addPluginVersion(MULTISAFEPAY_PLUGIN_VERSION)
            ->addShopRootUrl( get_bloginfo('url') );
    }

    /**
     * @param WC_Order $order
     * @return PaymentOptions
     */
    protected function create_payment_options(WC_Order $order): PaymentOptions
    {
        $payment_options = new PaymentOptions();
        return $payment_options
            ->addCancelUrl($order->get_cancel_order_url())
            ->addRedirectUrl($order->get_checkout_order_received_url());
    }
}