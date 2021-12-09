<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseBillingSuitePaymentMethod;

class Klarna extends BaseBillingSuitePaymentMethod {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_klarna';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'KLARNA';
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
        return __( 'Klarna - Pay in 30 days', 'multisafepay' );
    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        $method_description = sprintf(
            /* translators: %2$: The payment method title */
            __( 'A popular post-payment solution available for webshops based in Austria, Germany and the Netherlands. <br />Read more about <a href="%1$s" target="_blank">%2$s</a> on MultiSafepay\'s Documentation Center.', 'multisafepay' ),
            'https://docs.multisafepay.com/payment-methods/billing-suite/klarna/?utm_source=woocommerce&utm_medium=woocommerce-cms&utm_campaign=woocommerce-cms',
            $this->get_payment_method_title()
        );
        return $method_description;
    }

    /**
     *
     * @return boolean
     */
    public function has_fields(): bool {
        return false;
    }

    /**
     * @return array
     */
    public function add_form_fields(): array {
        $form_fields                          = parent::add_form_fields();
        $form_fields['min_amount']['default'] = '15';
        $form_fields['max_amount']['default'] = '300';
        return $form_fields;
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'klarna.png';
    }

}
