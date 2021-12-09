<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Fashiongiftcard extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_fashiongiftcard';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'FASHIONGFT';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Fashiongiftcard';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'fashiongiftcard.png';
    }

}
