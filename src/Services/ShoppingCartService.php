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

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart;
use MultiSafepay\ValueObject\CartItem;
use MultiSafepay\WooCommerce\Utils\MoneyUtil;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Tax;


/**
 * Class ShoppingCartService
 *
 * @package MultiSafepay\WooCommerce\Services
 */
class ShoppingCartService {

    public const MULTISAFEPAY_SHIPPING_ITEM_CODE = 'msp-shipping';

    /**
     * @param WC_Order $order
     * @param string   $currency
     * @return ShoppingCart
     */
    public function create_shopping_cart( WC_Order $order, string $currency ): ShoppingCart {
        $cart_items = array();

        foreach ( $order->get_items() as $item ) {
            $cart_items[] = $this->create_cart_item( $item, $currency );
        }

        foreach ( $order->get_items( 'shipping' ) as $item ) {
            $cart_items[] = $this->create_shipping_cart_item( $item, $currency );
        }

        foreach ( $order->get_items( 'fee' ) as $item ) {
            $cart_items[] = $this->create_fee_cart_item( $item, $currency );
        }

        return new ShoppingCart( $cart_items );

    }

    /**
     * @param WC_Order_Item_Product $item
     * @param string                $currency
     * @return CartItem
     */
    private function create_cart_item( WC_Order_Item_Product $item, string $currency ): CartItem {
        $merchant_item_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
        $product_name     = $item->get_name();
        $product_price    = (float) $item->get_subtotal() / (int) $item->get_quantity();

        // If product price without discount get_subtotal() is not the same than product price with discount
        // Then a percentage coupon has been applied to this item
        if ( (float) $item->get_subtotal() !== (float) $item->get_total() ) {
            $discount = (float) $item->get_subtotal() - (float) $item->get_total();
            // translators: %1$ The currency. %2$ The total amount of the discount per line item
            $product_name .= sprintf( __( ' - Coupon applied: - %1$s %2$s', 'multisafepay' ), number_format( $discount, 2, '.', '' ), $currency );
            $product_price = (float) $item->get_total() / (int) $item->get_quantity();
        }

        $cart_item = new CartItem();
        return $cart_item->addName( $product_name )
            ->addQuantity( (int) $item->get_quantity() )
            ->addMerchantItemId( (string) $merchant_item_id )
            ->addUnitPrice( MoneyUtil::create_money( $product_price, $currency ) )
            ->addTaxRate( $this->get_item_tax_rate( $item ) );
    }

    /**
     * Returns the tax rate value applied for an order item.
     *
     * @param WC_Order_Item_Product $item
     * @return float
     */
    private function get_item_tax_rate( WC_Order_Item_Product $item ): float {
        if ( 'taxable' !== $item->get_tax_status() ) {
            return 0;
        }
        if ( $this->is_order_vat_exempt( $item->get_order_id() ) ) {
            return 0;
        }
        $tax_rates = WC_Tax::get_rates( $item->get_tax_class() );
        switch ( count( $tax_rates ) ) {
            case 0:
                $tax_rate = 0;
                break;
            case 1:
                $tax      = reset( $tax_rates );
                $tax_rate = $tax['rate'];
                break;
            default:
                $tax_rate = ( ( wc_get_price_including_tax( $item->get_product() ) / wc_get_price_excluding_tax( $item->get_product() ) ) - 1 ) * 100;
                break;
        }
        return $tax_rate;
    }

    /**
     * @param WC_Order_Item_Shipping $item
     * @param string                 $currency
     * @return CartItem
     */
    private function create_shipping_cart_item( WC_Order_Item_Shipping $item, string $currency ): CartItem {
        $cart_item = new CartItem();
        return $cart_item->addName( __( 'Shipping', 'multisafepay' ) )
            ->addQuantity( 1 )
            ->addMerchantItemId( self::MULTISAFEPAY_SHIPPING_ITEM_CODE )
            ->addUnitPrice( MoneyUtil::create_money( (float) $item->get_total(), $currency ) )
            ->addTaxRate( $this->get_shipping_tax_rate( $item ) );
    }

    /**
     * Returns the tax rate value applied for the shipping item.
     *
     * @param WC_Order_Item_Shipping $item
     * @return float
     */
    private function get_shipping_tax_rate( WC_Order_Item_Shipping $item ): float {
        if ( $this->is_order_vat_exempt( $item->get_order_id() ) ) {
            return 0;
        }

        if ( (float) $item->get_total() === 0.00 ) {
            return 0;
        }

        $taxes = $item->get_taxes();
        if ( empty( $taxes['total'] ) ) {
            return 0;
        }

        $total_tax = array_sum( $taxes['total'] );
        $tax_rate  = ( (float) $total_tax * 100 ) / (float) $item->get_total();
        return $tax_rate;
    }

    /**
     * @param WC_Order_Item_Fee $item
     * @param string            $currency
     * @return CartItem
     */
    private function create_fee_cart_item( WC_Order_Item_Fee $item, string $currency ): CartItem {
        $cart_item = new CartItem();
        return $cart_item->addName( $item->get_name() )
            ->addQuantity( $item->get_quantity() )
            ->addMerchantItemId( (string) $item->get_id() )
            ->addUnitPrice( MoneyUtil::create_money( (float) $item->get_total(), $currency ) )
            ->addTaxRate( $this->get_fee_tax_rate( $item ) );
    }

    /**
     * Returns the tax rate value applied for a fee item.
     *
     * @param WC_Order_Item_Fee $item
     * @return float
     */
    private function get_fee_tax_rate( WC_Order_Item_Fee $item ): float {

        if ( $this->is_order_vat_exempt( $item->get_order_id() ) ) {
            return 0;
        }

        if ( (float) $item->get_total() === 0.00 ) {
            return 0;
        }

        $taxes = $item->get_taxes();

        if ( empty( $taxes['total'] ) ) {
            return 0;
        }

        $total_tax = array_sum( $taxes['total'] );
        $tax_rate  = ( (float) $total_tax * 100 ) / (float) $item->get_total();
        return $tax_rate;
    }

    /**
     * Returns if order is VAT exempt via WC->Customer->is_vat_exempt
     *
     * @param int $order_id
     * @return boolean
     */
    private function is_order_vat_exempt( int $order_id ): bool {
        if ( get_post_meta( $order_id, 'is_vat_exempt', true ) === 'yes' ) {
            return true;
        }
        return false;
    }

}
