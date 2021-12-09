<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Podium extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_podium';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'PODIUM';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Podium';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'podium.png';
    }

}
