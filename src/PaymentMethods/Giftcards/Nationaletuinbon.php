<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Nationaletuinbon extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_nationaletuinbon';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'NATNLETUIN';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Nationale Tuinbon';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'nationaletuinbon.png';
    }

}
