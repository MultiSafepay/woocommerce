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


use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart;
use MultiSafepay\ValueObject\CartItem;
use MultiSafepay\WooCommerce\Utils\MoneyUtil;
use WC_Order;
use WC_Order_Item;
use WC_Tax;

/**
 * Class ShoppingCartService
 * @package MultiSafepay\WooCommerce\Services
 */
class ShoppingCartService
{
    public CONST MSP_SHIPPING_ITEM_CODE = 'msp-shipping';

    /**
     * @param WC_Order $order
     * @param string $currency
     * @return ShoppingCart
     */
    public function create_shopping_cart(WC_Order $order, string $currency): ShoppingCart
    {
        $cart_items = array();

        foreach ($order->get_items() as $item) {
            $cart_items[] = $this->create_cart_item($item, $currency);
        }

        if ($order->get_shipping_total() > 0) {
            $cart_items[] = $this->create_shipping_cart_item($order, $currency);
        }

        return new ShoppingCart($cart_items);
    }

    /**
     * @param WC_Order_Item $item
     * @param string $currency
     * @return CartItem
     */
    protected function create_cart_item(WC_Order_Item $item, string $currency): CartItem
    {
        $product = $item->get_product();

        $cartItem = new CartItem();
        return $cartItem->addName($item->get_name())
            ->addQuantity($item->get_quantity())
            ->addMerchantItemId((string)$item->get_id())
            ->addUnitPrice(MoneyUtil::createMoney((float)$product->get_price(), $currency))
            ->addTaxRate($this->getItemTaxRate($item));
    }

    /**
     * @param WC_Order $order
     * @param string $currency
     * @return CartItem
     */
    protected function create_shipping_cart_item(WC_Order $order, string $currency): CartItem
    {
        $shipping_total_price = (float)$order->get_shipping_total();
        $tax_percentage = ((float)$order->get_shipping_tax() * 100) / $shipping_total_price;

        $cartItem = new CartItem();
        return $cartItem->addName(__("Shipping", 'multisafepay'))
            ->addQuantity(1)
            ->addMerchantItemId(self::MSP_SHIPPING_ITEM_CODE)
            ->addUnitPrice(MoneyUtil::createMoney($shipping_total_price, $currency))
            ->addTaxRate($tax_percentage);
    }

    /**
     * Returns the tax rate value applied for a item in the cart.
     *
     * @param WC_Order_Item $item
     * @return float
     *
     */
    private function getItemTaxRate(WC_Order_Item $item) {
        $tax_rate = 0;
        $tax_rates = WC_Tax::get_rates($item->get_tax_class());
        foreach ($tax_rates as $rate) {
            $tax_rate = $tax_rate + $rate['rate'];
        }
        return $tax_rate;
    }
}