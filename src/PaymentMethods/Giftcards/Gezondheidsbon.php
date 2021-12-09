<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Gezondheidsbon extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_gezondheidsbon';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'GEZONDHEID';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Gezondheidsbon';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'gezondheidsbon.png';
    }

}
