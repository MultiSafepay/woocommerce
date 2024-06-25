<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

use WC_Order;

/**
 * This class defines methods to handle easily different actions related with the WC_Order object
 */
class Order {

    /**
     * Check if the order is a MultiSafepay order
     *
     * @param WC_Order $order
     * @return bool
     */
    public static function is_multisafepay_order( WC_Order $order ): bool {
        if ( strpos( $order->get_payment_method(), 'multisafepay_' ) !== false ) {
            return true;
        }

        return false;
    }

}
