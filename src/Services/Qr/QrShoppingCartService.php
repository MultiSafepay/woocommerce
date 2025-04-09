<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services\Qr;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item as CartItem;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\ShippingItem;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\WooCommerce\Utils\Logger;
use MultiSafepay\WooCommerce\Utils\MoneyUtil;
use WC_Cart;
use WC_Coupon;
use WC_Tax;
use stdClass;

/**
 * Class QrShoppingCartService
 *
 * @package MultiSafepay\WooCommerce\Services\Qr
 */
class QrShoppingCartService {

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
     * Create a shopping cart from a WooCommerce cart
     *
     * @param WC_Cart $cart
     * @param string  $currency
     * @return ShoppingCart
     * @throws InvalidArgumentException
     */
    public function create_shopping_cart( WC_Cart $cart, string $currency ): ShoppingCart {
        // Recalculate totals is needed, to ensure all discounts, fees, etc are applied
        $cart->calculate_totals();

        // Is vat exempt
        $is_vat_exempt = $this->is_order_vat_exempt( $cart );

        $cart_items = array();

        foreach ( $cart->get_cart() as $cart_item ) {
            $cart_items[] = $this->create_cart_item( $cart_item, $currency, $is_vat_exempt );
        }

        // Add shipping as an item if applicable
        if ( $cart->needs_shipping() ) {
            $cart_items[] = $this->create_shipping_cart_item( $cart, $currency, $is_vat_exempt );
        }

        // Add fees as items if applicable
        foreach ( $cart->get_fees() as $fee ) {
            $cart_items[] = $this->create_fee_cart_item( $fee, $currency, $is_vat_exempt );
        }

        // Add coupons as items if applicable
        foreach ( $cart->get_coupons() as $coupon ) {
            if (
                    ( get_option( 'woocommerce_smart_coupon_apply_before_tax', 'no' ) !== 'yes' ) &&
                    in_array( $coupon->get_discount_type(), $this->get_types_of_coupons_not_applied_at_item_level(), true )
                ) {
                    $cart_items[] = $this->create_coupon_cart_item( $coupon, $currency );
            }
        }

        $shopping_cart = new ShoppingCart( $cart_items );

        $this->logger->log_info( wp_json_encode( $shopping_cart->getData() ) );

        return $shopping_cart;
    }

    /**
     * Create a cart item from the WooCommerce shopping cart item
     *
     * @param array  $cart_item
     * @param string $currency
     * @param bool   $is_vat_exempt
     * @return CartItem
     * @throws InvalidArgumentException
     */
    private function create_cart_item( array $cart_item, string $currency, bool $is_vat_exempt = false ): CartItem {
        $product       = $cart_item['data'];
        $item          = new CartItem();
        $product_name  = $product->get_name();
        $product_price = (float) $cart_item['line_subtotal'] / $cart_item['quantity'];

        // If product price without discount get_subtotal() is not the same as product price with discount
        // Then a percentage coupon has been applied to this item
        if ( (float) $cart_item['line_subtotal'] !== (float) $cart_item['line_total'] ) {
            $discount = (float) $cart_item['line_subtotal'] - (float) $cart_item['line_total'];
            // translators: %1$s is the discount amount, %2$s is the currency
            $product_name .= sprintf( __( ' - Coupon applied: - %1$s %2$s', 'multisafepay' ), number_format( $discount, 2, '.', '' ), $currency );
            $product_price = (float) $cart_item['line_total'] / (int) $cart_item['quantity'];
        }

        return $item->addName( $product_name )
            ->addQuantity( (int) $cart_item['quantity'] )
            ->addMerchantItemId( $product->get_sku() ? (string) $product->get_sku() : (string) $product->get_id() )
            ->addUnitPrice( MoneyUtil::create_money( $product_price, $currency ) )
            ->addTaxRate( $this->get_item_tax_rate_from_cart( $cart_item, $is_vat_exempt ) );
    }

    /**
     * Get the tax rate for a cart item
     *
     * @param array $cart_item
     * @param bool  $is_vat_exempt
     * @return float
     */
    private function get_item_tax_rate_from_cart( array $cart_item, bool $is_vat_exempt = false ): float {
        if ( ! wc_tax_enabled() ) {
            return 0;
        }

        if ( $is_vat_exempt ) {
            return 0.00;
        }

        $product = $cart_item['data'];

        if ( 'taxable' !== $product->get_tax_status() ) {
            return 0;
        }

        $tax_class = $product->get_tax_class();
        $tax_rates = WC_Tax::get_rates( $tax_class );

        switch ( count( $tax_rates ) ) {
            case 0:
                $tax_rate = 0;
                break;
            case 1:
                $tax      = reset( $tax_rates );
                $tax_rate = $tax['rate'];
                break;
            default:
                $price_including_tax = wc_get_price_including_tax( $product );
                $price_excluding_tax = wc_get_price_excluding_tax( $product );
                $tax_rate            = ( ( $price_including_tax / $price_excluding_tax ) - 1 ) * 100;
                break;
        }

        return $tax_rate;
    }

    /**
     * Create a shipping item from the WooCommerce shopping cart
     *
     * @param WC_Cart $cart
     * @param string  $currency
     * @param bool    $is_vat_exempt
     * @return ShippingItem
     */
    public function create_shipping_cart_item( WC_Cart $cart, string $currency, bool $is_vat_exempt = false ): ShippingItem {
        $shipping_item = new ShippingItem();
        return $shipping_item->addName( __( 'Shipping', 'multisafepay' ) )
            ->addQuantity( 1 )
            ->addUnitPrice( MoneyUtil::create_money( (float) $cart->get_shipping_total(), $currency ) )
            ->addMerchantItemId( 'msp-shipping' )
            ->addTaxRate( $this->get_shipping_tax_rate( $cart, $is_vat_exempt ) );
    }

    /**
     * Get the tax rate for shipping
     *
     * @param WC_Cart $cart
     * @param bool    $is_vat_exempt
     * @return float
     */
    private function get_shipping_tax_rate( WC_Cart $cart, bool $is_vat_exempt = false ): float {
        if ( ! wc_tax_enabled() ) {
            return 0;
        }

        if ( $is_vat_exempt ) {
            return 0.00;
        }

        $shipping_total = $cart->get_shipping_total();
        $shipping_taxes = $cart->get_shipping_taxes();

        if ( empty( $shipping_taxes ) || ( 0.00 === $shipping_total ) ) {
            return 0;
        }

        $total_tax = array_sum( $shipping_taxes );

        return ( (float) $total_tax * 100 ) / (float) $shipping_total;
    }

    /**
     * Create a fee item from the WooCommerce shopping cart
     *
     * @param stdClass $fee
     * @param string   $currency
     * @param bool     $is_vat_exempt
     * @return CartItem
     */
    private function create_fee_cart_item( stdClass $fee, string $currency, bool $is_vat_exempt = false ): CartItem {
        $fee_item = new CartItem();
        return $fee_item->addName( $fee->name )
            ->addQuantity( 1 )
            ->addMerchantItemId( (string) $fee->id )
            ->addUnitPrice( MoneyUtil::create_money( (float) $fee->amount, $currency ) )
            ->addTaxRate( $this->get_fee_tax_rate( $fee, $is_vat_exempt ) );
    }

    /**
     * Get the tax rate for a fee item from the cart
     *
     * @param stdClass $fee
     * @param bool     $is_vat_exempt
     * @return float
     */
    private function get_fee_tax_rate( stdClass $fee, bool $is_vat_exempt = false ): float {
        if ( ! wc_tax_enabled() ) {
            return 0.00;
        }

        if ( $is_vat_exempt ) {
            return 0.00;
        }

        if ( 0.00 === (float) $fee->amount ) {
            return 0.00;
        }

        if ( false === $fee->taxable ) {
            return 0.00;
        }

        if ( empty( $fee->total ) ) {
            return 0.00;
        }

        $total_tax = array_sum( $fee->tax_data );

        return ( (float) $total_tax * 100 ) / (float) $fee->total;
    }

    /**
     * Create a coupon item from the WooCommerce shopping cart
     *
     * @param WC_Coupon $coupon
     * @param string    $currency
     * @return CartItem
     * @throws InvalidArgumentException
     */
    private function create_coupon_cart_item( WC_Coupon $coupon, string $currency ): CartItem {
        $coupon_item = new CartItem();
        return $coupon_item->addName( $coupon->get_code() )
            ->addQuantity( 1 )
            ->addMerchantItemId( (string) $coupon->get_id() )
            ->addUnitPrice( MoneyUtil::create_money( (float) -$coupon->get_amount(), $currency ) )
            ->addTaxRate( 0 );
    }

    /**
     * Retrieve the types of coupons that are not applied at item level
     *
     * @return array
     */
    public function get_types_of_coupons_not_applied_at_item_level(): array {
        return apply_filters( 'multisafepay_types_of_coupons_not_applied_at_item_level', array( 'smart_coupon' ) );
    }

    /**
     * Returns if order is VAT exempt via WC_Cart->get_customer()->is_vat_exempt
     *
     * @param WC_Cart $cart
     * @return bool
     */
    public function is_order_vat_exempt( WC_Cart $cart ): bool {
        $customer = $cart->get_customer();

        if ( null === $customer ) {
            return false;
        }

        return $customer->is_vat_exempt();
    }
}
