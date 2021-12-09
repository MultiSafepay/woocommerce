<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Winkelcheque extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_winkelcheque';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'WINKELCHEQUE';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Winkelcheque';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'winkelcheque.png';
    }

}
