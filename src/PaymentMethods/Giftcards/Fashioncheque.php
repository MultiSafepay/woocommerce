<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Fashioncheque extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_fashioncheque';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'FASHIONCHQ';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Fashioncheque';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'fashioncheque.png';
    }

}
