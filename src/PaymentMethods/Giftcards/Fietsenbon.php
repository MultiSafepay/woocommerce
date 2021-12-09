<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Fietsenbon extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_fietsenbon';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'FIETSENBON';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Fietsenbon';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'fietsenbon.png';
    }

}
