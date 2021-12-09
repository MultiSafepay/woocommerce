<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Yourgift extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_yourgift';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'YOURGIFT';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Yourgift';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'yourgift.png';
    }

}
