<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Filters;

use WC_Order;

/**
 * Filter payment gateway title in the admin order context using the title stored in the order.
 */
class PaymentMethodTitle {

    /**
     * @param string $title
     * @param string $gateway_id
     * @return string
     */
    public function filter_gateway_title_by_order_payment_method_title( string $title, string $gateway_id ): string {
        if ( ! is_admin() || wp_doing_ajax() ) {
            return $title;
        }

        // In this WooCommerce admin hook, the current order is provided through the global $theorder.
        // This is the standard WooCommerce pattern for admin order screens.
        global $theorder;

        if ( ! isset( $theorder ) || ! ( $theorder instanceof WC_Order ) ) {
            return $title;
        }

        if ( $theorder->get_payment_method() !== $gateway_id ) {
            return $title;
        }

        $order_payment_method_title = $theorder->get_payment_method_title();
        if ( ! empty( $order_payment_method_title ) ) {
            return $order_payment_method_title;
        }

        return $title;
    }
}
