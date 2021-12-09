<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Wijncadeau extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_wijncadeau';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'WIJNCADEAU';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Wijncadeau';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'wijncadeau.png';
    }

}
