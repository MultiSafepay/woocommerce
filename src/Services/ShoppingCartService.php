<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item as CartItem;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\ShippingItem;
use MultiSafepay\WooCommerce\Utils\Hpos;
use MultiSafepay\WooCommerce\Utils\Logger;
use MultiSafepay\WooCommerce\Utils\MoneyUtil;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Order_Item_Coupon;
use WC_Tax;
use WC_Coupon;

/**
 * Class ShoppingCartService
 *
 * @package MultiSafepay\WooCommerce\Services
 */
class ShoppingCartService {

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
     * @param WC_Order    $order
     * @param string      $currency
     * @param string|null $gateway_code
     * @return ShoppingCart
     */
    public function create_shopping_cart( WC_Order $order, string $currency, ?string $gateway_code = '' ): ShoppingCart {

        // If coupon type is percentage, fixed_product type, or fixed_cart, which comes by default in WooCommerce,
        // then discounted amount is being included at product item level to avoid miscalculations in the tax rates, since WooCommerce is rounding the
        // taxes related to the discount items according with decimal values defined in WooCommerce settings.
        $types_of_coupons_not_applied_at_item_level = apply_filters( 'multisafepay_types_of_coupons_not_applied_at_item_level', array( 'smart_coupon' ) );

        $cart_items = array();

        if ( get_option( 'multisafepay_debugmode', false ) ) {
            $this->logger->log_info( wc_print_r( $order->get_items(), true ) );
        }

        /** @var WC_Order_Item_Product $item */
        foreach ( $order->get_items() as $item ) {
            $cart_items[] = $this->create_cart_item( $item, $currency, $gateway_code );
        }

        /** @var WC_Order_Item_Shipping $item */
        foreach ( $order->get_items( 'shipping' ) as $item ) {
            $cart_items[] = $this->create_shipping_cart_item( $item, $currency, $gateway_code );
        }

        /** @var WC_Order_Item_Fee $item */
        foreach ( $order->get_items( 'fee' ) as $item ) {
            $cart_items[] = $this->create_fee_cart_item( $item, $currency, $gateway_code );
        }

        /** @var WC_Order_Item_Coupon $item */
        foreach ( $order->get_items( 'coupon' ) as $item ) {
            // Only for coupons with discount type not applied at item level
            // And in specific case of smart_coupons, only when the smart coupon is not being applied before tax calculations
            if (
                in_array( ( new WC_Coupon( $item->get_code() ) )->get_discount_type(), $types_of_coupons_not_applied_at_item_level, true ) &&
                ( get_option( 'woocommerce_smart_coupon_apply_before_tax', 'no' ) !== 'yes' )
            ) {
                $cart_items[] = $this->create_coupon_cart_item( $item, $currency );
            }
        }

        $shopping_cart = new ShoppingCart( $cart_items );

        if ( get_option( 'multisafepay_debugmode', false ) ) {
            $this->logger->log_info( wp_json_encode( $shopping_cart->getData() ) );
        }

        return $shopping_cart;

    }

    /**
     * @param WC_Order_Item_Product $item
     * @param string                $currency
     * @param string                $gateway_code
     * @return CartItem
     */
    private function create_cart_item( WC_Order_Item_Product $item, string $currency, string $gateway_code ): CartItem {
        $merchant_item_id = apply_filters( 'multisafepay_merchant_item_id', (string) $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id(), $item );
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
            ->addTaxRate( $this->get_item_tax_rate( $item, $gateway_code ) );
    }

    /**
     * Returns the tax rate value applied for an order item.
     *
     * @param WC_Order_Item_Product $item
     * @param string                $gateway_code
     * @return float
     */
    private function get_item_tax_rate( WC_Order_Item_Product $item, string $gateway_code ): float {
        if ( ! wc_tax_enabled() ) {
            return 0;
        }

        if ( 'taxable' !== $item->get_tax_status() ) {
            return 0;
        }

        if ( $this->is_order_vat_exempt( $item->get_order() ) ) {
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

        return $this->rounding_filter( $tax_rate, $gateway_code );
    }

    /**
     * @param WC_Order_Item_Shipping $item
     * @param string                 $currency
     * @param string                 $gateway_code
     * @return ShippingItem
     */
    private function create_shipping_cart_item( WC_Order_Item_Shipping $item, string $currency, string $gateway_code ): ShippingItem {
        $cart_item = new ShippingItem();
        return $cart_item->addName( __( 'Shipping', 'multisafepay' ) )
            ->addQuantity( 1 )
            ->addUnitPrice( MoneyUtil::create_money( (float) $item->get_total(), $currency ) )
            ->addTaxRate( $this->get_shipping_tax_rate( $item, $gateway_code ) );
    }

    /**
     * Returns the tax rate value applied for the shipping item.
     *
     * @param WC_Order_Item_Shipping $item
     * @param string                 $gateway_code
     * @return float
     */
    private function get_shipping_tax_rate( WC_Order_Item_Shipping $item, string $gateway_code ): float {
        if ( ! wc_tax_enabled() ) {
            return 0;
        }

        if ( $this->is_order_vat_exempt( $item->get_order() ) ) {
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

        return $this->rounding_filter( $tax_rate, $gateway_code );
    }

    /**
     * @param WC_Order_Item_Fee $item
     * @param string            $currency
     * @param string            $gateway_code
     * @return CartItem
     */
    private function create_fee_cart_item( WC_Order_Item_Fee $item, string $currency, string $gateway_code ): CartItem {
        $cart_item = new CartItem();
        return $cart_item->addName( $item->get_name() )
            ->addQuantity( $item->get_quantity() )
            ->addMerchantItemId( (string) $item->get_id() )
            ->addUnitPrice( MoneyUtil::create_money( (float) $item->get_total(), $currency ) )
            ->addTaxRate( $this->get_fee_tax_rate( $item, $gateway_code ) );
    }

    /**
     * Returns the tax rate value applied for a fee item.
     *
     * @param WC_Order_Item_Fee $item
     * @param string            $gateway_code
     * @return float
     */
    private function get_fee_tax_rate( WC_Order_Item_Fee $item, string $gateway_code ): float {
        if ( ! wc_tax_enabled() ) {
            return 0;
        }

        if ( $this->is_order_vat_exempt( $item->get_order() ) ) {
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

        return $this->rounding_filter( $tax_rate, $gateway_code );
    }

    /**
     * Returns if order is VAT exempt via WC->Customer->is_vat_exempt
     *
     * @param WC_Order $order
     * @return boolean
     */
    public function is_order_vat_exempt( WC_Order $order ): bool {
        return Hpos::get_meta( $order, 'is_vat_exempt' ) === 'yes';
    }

    /**
     * @param WC_Order_Item_Coupon $item
     * @param string               $currency
     *
     * @return CartItem
     */
    public function create_coupon_cart_item( WC_Order_Item_Coupon $item, string $currency ): CartItem {
        $cart_item = new CartItem();
        return $cart_item->addName( $item->get_name() )
            ->addQuantity( $item->get_quantity() )
            ->addMerchantItemId( (string) $item->get_id() )
            ->addUnitPrice( MoneyUtil::create_money( (float) -$item->get_discount(), $currency ) )
            ->addTaxRate( 0 );
    }

    /**
     * @param float  $tax_rate
     * @param string $gateway_code
     * @return float
     */
    private function rounding_filter( float $tax_rate, string $gateway_code ): float {
        if ( 'BILLINK' !== $gateway_code ) {
            return $tax_rate;
        }

        $allowed_rates = array( 0, 5, 6, 7, 9, 16, 19, 20, 21 );

        foreach ( $allowed_rates as $rate ) {
            if ( abs( $tax_rate - $rate ) <= 0.15 ) {
                return round( $tax_rate );
            }
        }

        return $tax_rate;
    }
}
