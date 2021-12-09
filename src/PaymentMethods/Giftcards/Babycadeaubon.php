<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Babycadeaubon extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_babycadeaubon';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'BABYCAD';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Baby Cadeaubon';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'babycad.png';
    }

}
