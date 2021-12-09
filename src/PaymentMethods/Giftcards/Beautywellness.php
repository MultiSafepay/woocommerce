<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Beautywellness extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_beautyandwellness';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'BEAUTYWELL';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Beauty & Wellness';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'beautywellness.png';
    }

}
