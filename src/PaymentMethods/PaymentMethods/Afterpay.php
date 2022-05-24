<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\Services\CustomerService;

class Afterpay extends BasePaymentMethod {
    public const DEFAULT_TERMS_AND_CONDITIONS = 'https://www.afterpay.nl/en/about/pay-with-afterpay/payment-conditions';
    public const NL_TERMS_AND_CONDITIONS      = 'https://www.afterpay.nl/nl/algemeen/betalen-met-afterpay/betalingsvoorwaarden';
    public const BE_NL_TERMS_AND_CONDITIONS   = 'https://www.afterpay.be/be/footer/betalen-met-afterpay/betalingsvoorwaarden';
    public const BE_FR_TERMS_AND_CONDITIONS   = 'https://www.afterpay.be/fr/footer/payer-avec-afterpay/conditions-de-paiement';

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_afterpay';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'AFTERPAY';
    }

    /**
     * @return string
     */
    public function get_payment_method_type(): string {
        return ( $this->get_option( 'direct', 'yes' ) === 'yes' ) ? 'direct' : 'redirect';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return 'AfterPay';
    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        $method_description = sprintf(
        /* translators: %2$: The payment method title */
            __( 'Conveniently allows customers to make a payment for their online purchases once receiving them. <br />Read more about <a href="%1$s" target="_blank">%2$s</a> on MultiSafepay\'s Documentation Center.', 'multisafepay' ),
            'https://docs.multisafepay.com/payment-methods/billing-suite/afterpay/?utm_source=woocommerce&utm_medium=woocommerce-cms&utm_campaign=woocommerce-cms',
            $this->get_payment_method_title()
        );

        return $method_description;
    }

    /**
     * @return boolean
     */
    public function has_fields(): bool {
        return ( $this->get_option( 'direct', 'yes' ) === 'yes' ) ? true : false;
    }

    /**
     * @return array
     */
    public function add_form_fields(): array {
        $form_fields           = parent::add_form_fields();
        $form_fields['direct'] = array(
            'title'    => __( 'Transaction Type', 'multisafepay' ),
            /* translators: %1$: The payment method title */
            'label'    => sprintf( __( 'Enable direct %1$s', 'multisafepay' ), $this->get_payment_method_title() ),
            'type'     => 'checkbox',
            'default'  => 'yes',
            'desc_tip' => __( 'If enabled, additional information can be entered during WooCommerce checkout. If disabled, additional information will be requested on the MultiSafepay payment page.', 'multisafepay' ),
        );

        return $form_fields;
    }

    /**
     * Prints checkout custom fields
     *
     * @return  void
     */
    public function payment_fields(): void {
        $terms_and_conditions_url = $this->get_terms_and_conditions_url( ( new CustomerService() )->get_locale() );
        require MULTISAFEPAY_PLUGIN_DIR_PATH . 'templates/multisafepay-checkout-fields-display.php';
    }

    /**
     * @return array
     */
    public function get_checkout_fields_ids(): array {
        return array( 'salutation', 'birthday', 'afterpay-terms-conditions' );
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'afterpay.png';
    }

    /**
     * @param array|null $data
     *
     * @return GatewayInfoInterface
     */
    public function get_gateway_info( array $data = null ): GatewayInfoInterface {
        return $this->get_gateway_info_meta( $data );
    }

    /**
     * @return bool
     */
    public function validate_fields(): bool {

        if ( ! isset( $_POST[ $this->id . '_afterpay_terms_conditions' ] ) ) {
            wc_add_notice( __( 'AfterPay terms and conditions is a required field', 'multisafepay' ), 'error' );
        }

        return parent::validate_fields();
    }

    /**
     * @param string $locale
     *
     * @return string
     */
    private function get_terms_and_conditions_url( string $locale ): string {
        if ( 'nl_NL' === $locale || 'nl_NL_formal' === $locale ) {
            return self::NL_TERMS_AND_CONDITIONS;
        }

        if ( 'be_NL' === $locale ) {
            return self::BE_NL_TERMS_AND_CONDITIONS;
        }

        if ( 'be_FR' === $locale ) {
            return self::BE_FR_TERMS_AND_CONDITIONS;
        }

        return self::DEFAULT_TERMS_AND_CONDITIONS;
    }

}
