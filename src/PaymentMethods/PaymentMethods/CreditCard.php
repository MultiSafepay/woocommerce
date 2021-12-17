<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseTokenizationPaymentMethod;

class CreditCard extends BaseTokenizationPaymentMethod {

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
            'https://docs.multisafepay.com/payments/methods/credit-and-debit-cards/?utm_source=woocommerce&utm_medium=woocommerce-cms&utm_campaign=woocommerce-cms',
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

    /**
     * @return mixed
     */
    public function add_form_fields(): array {
        $form_fields        = parent::add_form_fields();
        $tokenization_field = array(
            'tokenization' => array(
                'title'       => __( 'Tokenization', 'multisafepay' ),
                'label'       => 'Enable Tokenization in ' . $this->get_method_title() . ' Gateway',
                'type'        => 'checkbox',
                'description' => __( 'More information about Tokenization on <a href="https://docs.multisafepay.com/features/recurring-payments/" target="_blank">MultiSafepay\'s Documentation Center</a>.', 'multisafepay' ),
                'default'     => get_option( 'multisafepay_tokenization', 'no' ),
            ),
        );
        return array_merge( $form_fields, $tokenization_field );
    }

}
