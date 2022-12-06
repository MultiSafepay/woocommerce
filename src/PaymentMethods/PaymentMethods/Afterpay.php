<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\Services\CustomerService;

class Afterpay extends BasePaymentMethod {
    public const DEFAULT_TERMS_URL                      = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/nl_en/default';
    public const BILLING_ADDRESS_DE_LOCALE_EN_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/de_en/default';
    public const BILLING_ADDRESS_DE_LOCALE_DE_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/de_de/default';
    public const BILLING_ADDRESS_AT_LOCALE_EN_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/at_en/default';
    public const BILLING_ADDRESS_AT_LOCALE_DE_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/at_de/default';
    public const BILLING_ADDRESS_CH_LOCALE_EN_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/ch_en/default';
    public const BILLING_ADDRESS_CH_LOCALE_DE_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/ch_de/default';
    public const BILLING_ADDRESS_CH_LOCALE_FR_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/ch_fr/default';
    public const BILLING_ADDRESS_NL_LOCALE_EN_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/nl_en/default';
    public const BILLING_ADDRESS_NL_LOCALE_NL_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/nl_nl/default';
    public const BILLING_ADDRESS_BE_LOCALE_EN_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/be_en/default';
    public const BILLING_ADDRESS_BE_LOCALE_NL_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/be_nl/default';
    public const BILLING_ADDRESS_BE_LOCALE_FR_TERMS_URL = 'https://documents.riverty.com/terms_conditions/payment_methods/invoice/be_fr/default';

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
        return 'Riverty';
    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        $method_description = sprintf(
        /* translators: %2$: The payment method title */
            __( 'Conveniently allows customers to make a payment for their online purchases once receiving them. <br />Read more about <a href="%1$s" target="_blank">%2$s</a> on MultiSafepay\'s Documentation Center.', 'multisafepay' ),
            'https://docs.multisafepay.com',
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
        return 'riverty.png';
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
            wc_add_notice( __( 'Riverty terms and conditions is a required field', 'multisafepay' ), 'error' );
        }

        return parent::validate_fields();
    }

    /**
     * @param string $locale
     *
     * @return string
     */
    private function get_terms_and_conditions_url( string $locale ): string {
        $billing_country = WC()->cart->get_customer()->get_billing_country();

        if ( 'AT' === $billing_country ) {
            if ( stripos( $locale, 'de' ) !== false ) {
                return self::BILLING_ADDRESS_AT_LOCALE_DE_TERMS_URL;
            }

            return self::BILLING_ADDRESS_AT_LOCALE_EN_TERMS_URL;
        }

        if ( 'BE' === $billing_country ) {
            if ( stripos( $locale, 'nl' ) !== false ) {
                return self::BILLING_ADDRESS_BE_LOCALE_NL_TERMS_URL;
            }
            if ( stripos( $locale, 'fr' ) !== false ) {
                return self::BILLING_ADDRESS_BE_LOCALE_FR_TERMS_URL;
            }
            return self::BILLING_ADDRESS_BE_LOCALE_EN_TERMS_URL;
        }

        if ( 'CH' === $billing_country ) {
            if ( stripos( $locale, 'de' ) !== false ) {
                return self::BILLING_ADDRESS_CH_LOCALE_DE_TERMS_URL;
            }

            if ( stripos( $locale, 'fr' ) !== false ) {
                return self::BILLING_ADDRESS_CH_LOCALE_FR_TERMS_URL;
            }

            return self::BILLING_ADDRESS_CH_LOCALE_EN_TERMS_URL;
        }

        if ( 'DE' === $billing_country ) {
            if ( stripos( $locale, 'de' ) !== false ) {
                return self::BILLING_ADDRESS_DE_LOCALE_DE_TERMS_URL;
            }

            return self::BILLING_ADDRESS_DE_LOCALE_EN_TERMS_URL;
        }

        if ( 'NL' === $billing_country ) {
            if ( stripos( $locale, 'nl' ) !== false ) {
                return self::BILLING_ADDRESS_NL_LOCALE_NL_TERMS_URL;
            }
            return self::BILLING_ADDRESS_NL_LOCALE_EN_TERMS_URL;
        }

        return self::DEFAULT_TERMS_URL;
    }

}
