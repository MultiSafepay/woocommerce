<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Givacard extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_givacard';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'GIVACARD';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Givacard';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'givacard.png';
    }

}
