<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Filters;

use MultiSafepay\WooCommerce\Utils\Hpos;
use WC_Order;

/**
 * Replace the checkout payment URL when an admin payment link exists.
 */
class CheckoutPaymentUrl {

    /**
     * @param string   $default_payment_link
     * @param WC_Order $order
     * @return string
     */
    public function replace_checkout_payment_url( string $default_payment_link, WC_Order $order ): string {
        $send_payment_link = Hpos::get_meta( $order, 'send_payment_link' );
        if ( $send_payment_link ) {
            $payment_url = Hpos::get_meta( $order, 'payment_url' );

            if ( is_string( $payment_url ) && '' !== $payment_url ) {
                return $payment_url;
            }
        }

        return $default_payment_link;
    }
}
