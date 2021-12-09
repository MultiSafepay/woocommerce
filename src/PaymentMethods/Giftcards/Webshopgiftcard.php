<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Giftcards;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod as MultiSafepayPaymentMethod;

class Webshopgiftcard extends MultiSafepayPaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_webshopgiftcard';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'WEBSHOPGIFTCARD';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'Webshop gift card';
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'webshopgiftcard.png';
    }

}
