<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Goodcard extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_goodcard';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'GOODCARD';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Goodcard';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'goodcard.png';
    }

}
