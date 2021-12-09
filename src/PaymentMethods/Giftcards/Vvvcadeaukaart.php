<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Vvvcadeaukaart extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_vvvcadeaukaart';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'VVVGIFTCRD';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'VVV Cadeaukaart';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'vvv.png';
    }

}
