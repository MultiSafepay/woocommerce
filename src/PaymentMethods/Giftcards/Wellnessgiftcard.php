<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Wellnessgiftcard extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_wellnessgiftcard';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'WELLNESSGIFTCARD';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Wellness gift card';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'wellnessgiftcard.png';
    }

}
