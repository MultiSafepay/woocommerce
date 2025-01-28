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
        if ( ! $this->get_option( $this->get_payment_method_id() . '_gift_card_max_amount_updated', false ) ) {
            $this->update_option( 'max_amount', '' );
            $this->update_option( $this->get_payment_method_id() . '_gift_card_max_amount_updated', '1' );
            $this->max_amount = '';
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
