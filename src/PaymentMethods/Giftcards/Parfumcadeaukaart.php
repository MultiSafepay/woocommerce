<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Parfumcadeaukaart extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_parfumcadeaukaart';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'PARFUMCADE';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Parfumcadeaukaart';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'parfumcadeaukaart.png';
    }

}
