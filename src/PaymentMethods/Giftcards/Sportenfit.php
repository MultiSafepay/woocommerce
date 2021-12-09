<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Sportenfit extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_sportenfit';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'SPORTENFIT';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Sport & Fit';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'sportenfit.png';
    }

}
