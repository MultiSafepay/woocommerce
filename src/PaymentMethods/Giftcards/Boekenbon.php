<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Boekenbon extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_boekenbon';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'BOEKENBON';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Boekenbon';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'boekenbon.png';
    }

}
