<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use WC_Order;

/**
 * Class BaseGiftCardPaymentMethod
 *
 * @package MultiSafepay\WooCommerce\PaymentMethods\Base
 */
class BaseGiftCardPaymentMethod extends BasePaymentMethod {

    /**
     * @param WC_Order $order
     * @return bool
     */
    public function can_refund_order( $order ) {
        return false;
    }

    /**
     * Return if payment component is enabled.
     *
     * @return bool
     */
    public function is_payment_component_enabled(): bool {
        return false;
    }
}
