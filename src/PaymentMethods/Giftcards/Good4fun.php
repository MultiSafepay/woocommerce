<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Good4fun extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_good4fun';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'GOOD4FUN';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Good4fun';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'good4fun.png';
    }

}
