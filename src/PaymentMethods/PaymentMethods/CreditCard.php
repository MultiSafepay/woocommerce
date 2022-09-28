<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;

class CreditCard extends BasePaymentMethod {

    /**
     * @var bool
     */
    protected $has_configurable_tokenization = true;

    /**
     * @var bool
     */
    protected $has_configurable_payment_component = true;

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_creditcard';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'CREDITCARD';
    }

    /**
     * @return string
     */
    public function get_payment_method_type(): string {
        return 'redirect';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return __( 'Credit Card', 'multisafepay' );
    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        $method_description = sprintf(
            /* translators: %2$: The payment method title */
            __( 'Read more about <a href="%1$s" target="_blank">%2$s</a> on MultiSafepay\'s Documentation Center.', 'multisafepay' ),
            'https://docs.multisafepay.com',
            $this->get_payment_method_title()
        );
        return $method_description;
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'creditcard.png';
    }

}
