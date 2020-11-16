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
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use MultiSafepay\ValueObject\Money;
use WC_Order;

/**
 * Class OrderService
 * @package MultiSafepay\WooCommerce\Services
 */
class OrderService {

    /**
     * @var CustomerService
     */
    protected $customer_service;

    /**
     * OrderService constructor.
     */
    public function __construct()
    {
        $this->customer_service = new CustomerService();
    }

    /**
     * @param int $order_id
     * @param string $gateway_code
     * @param string $type
     * @return OrderRequest
     */
    public function create_order_request(int $order_id, string $gateway_code, string $type): OrderRequest
    {
        $order = wc_get_order($order_id);

        $payment_options = $this->create_payment_options($order);
        $plugin_details = $this->create_plugin_details();
        $customer_details = $this->customer_service->create_customer_details($order->get_customer_id());

        $order_request = new OrderRequest();
        return $order_request->addOrderId($order->get_order_number())
            ->addMoney(new Money((float)($order->get_total() * 100)))
            ->addGatewayCode($gateway_code)
            ->addType($type)
            ->addPluginDetails($plugin_details)
            ->addDescriptionText('Payment for order: ' . $order->get_id())
            ->addCustomer($customer_details)
            ->addPaymentOptions($payment_options);
    }

    /**
     * @return PluginDetails
     */
    protected function create_plugin_details()
    {
        $plugin_details = new PluginDetails();
        return $plugin_details
            ->addApplicationName(plugin_basename(__FILE__))
            ->addApplicationVersion('1.0.0')
            ->addPluginVersion('1.0.0');
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