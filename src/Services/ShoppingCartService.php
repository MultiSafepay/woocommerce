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
use WC_Order_Item_Fee;
use WC_Order_Item_Product;


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

        foreach ($order->get_items( 'fee' ) as $item) {
            $cart_items[] = $this->create_fee_cart_item($item, $currency);
        }

        foreach ($order->get_items('coupon') as $item ) {
            $cart_items[] = $this->create_coupon_cart_item($item, $currency);
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
     * @param WC_Order_Item_Fee $item
     * @param string $currency
     * @return CartItem
     */
    protected function create_fee_cart_item(WC_Order_Item_Fee $item, string $currency): CartItem
    {
        $cartItem = new CartItem();
        return $cartItem->addName($item->get_name())
            ->addQuantity($item->get_quantity())
            ->addMerchantItemId((string)$item->get_id())
            ->addUnitPrice(MoneyUtil::createMoney((float)$item->get_total(), $currency))
            ->addTaxRate($this->getFeeTaxRate($item));
    }

    /**
     * @param WC_Order_Item $item
     * @param string $currency
     * @return CartItem
     */
    protected function create_coupon_cart_item(WC_Order_Item $item, string $currency): CartItem
    {
        return $cartItem->addName( $item->get_name() )
            ->addQuantity( 1 )
            ->addMerchantItemId( (string)$item->get_id() )
            ->addUnitPrice(MoneyUtil::createMoney((float)$item->get_discount(), $currency)->negative())
            ->addTaxRate($this->getCouponTaxRate($item));
    }

    /**
     * @param WC_Order $order
     * @param string $currency
     * @return CartItem
     */
    protected function create_shipping_cart_item(WC_Order $order, string $currency): CartItem
    {
        $cartItem = new CartItem();
        return $cartItem->addName(__("Shipping", 'multisafepay'))
            ->addQuantity(1)
            ->addMerchantItemId(self::MSP_SHIPPING_ITEM_CODE)
            ->addUnitPrice(MoneyUtil::createMoney((float)$order->get_shipping_total(), $currency))
            ->addTaxRate($this->getShippingTaxRate($order));
    }

    /**
     * Returns the tax rate value applied for an order item.
     *
     * @param WC_Order_Item $item
     * @return float
     */
    private function getItemTaxRate(WC_Order_Item $item): float {
        $order_item_product = new WC_Order_Item_Product( $item->get_id() );
        $tax_rate     = ( (float)$order_item_product->get_total_tax() * 100 ) / (float)$order_item_product->get_subtotal();
        return $tax_rate;
    }

    /**
     * Returns the tax rate value applied for a fee item.
     *
     * @param WC_Order_Item_Fee $item
     * @return float
     */
    private function getFeeTaxRate(WC_Order_Item_Fee $item): float {
        $tax_rate = ( (float)$item->get_total_tax() * 100 ) / (float)$item->get_total();
        return $tax_rate;
    }

    /**
     * Returns the tax rate value applied for a coupon item.
     *
     * @param WC_Order_Item $item
     * @return float
     */
    private function getCouponTaxRate(WC_Order_Item $item): float {
        $tax_rate = ((float)$item->get_discount_tax() * 100) / $item->get_discount();
        return $tax_rate;
    }

    /**
     * Returns the tax rate value applied for the shipping item.
     *
     * @param WC_Order $order
     * @return float
     */
    private function getShippingTaxRate(WC_Order $order): float {
        $tax_rate = ((float)$order->get_shipping_tax() * 100) / (float)$order->get_shipping_total();
        return $tax_rate;
    }

}