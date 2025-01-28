<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\WooCommerce\Utils\Logger;
use WC_Order;

/**
 * Class BaseGiftCardPaymentMethod
 *
 * @package MultiSafepay\WooCommerce\PaymentMethods\Base
 */
class BaseGiftCardPaymentMethod extends BasePaymentMethod {

    /**
     * BaseGiftCardPaymentMethod constructor.
     *
     * @param PaymentMethod $payment_method
     * @param Logger|null   $logger
     */
    public function __construct( PaymentMethod $payment_method, ?Logger $logger = null ) {
        parent::__construct( $payment_method, $logger );
        if ( ! empty( $this->get_option( 'max_amount' ) ) ) {
            $this->update_option( 'max_amount', '' );
            $this->max_amount = $this->get_option( 'max_amount' );
        }
    }

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
