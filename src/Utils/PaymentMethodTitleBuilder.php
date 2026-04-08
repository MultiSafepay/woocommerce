<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

/**
 * Build display labels for payment methods based on wallet and instrument details.
 */
class PaymentMethodTitleBuilder {

    /**
     * Normalize known payment instrument labels for display.
     *
     * The same keys are reused to validate whether wallet + instrument titles can be combined.
     */
    private const PAYMENT_INSTRUMENT_TITLE_MAP = array(
        'VISA'       => 'Visa',
        'AMEX'       => 'American Express',
        'MASTERCARD' => 'Mastercard',
    );

    /**
     * Return the underlying payment instrument label
     *
     * @param string $payment_instrument_gateway_code
     * @return string
     */
    public function get_underlying_payment_method_title( string $payment_instrument_gateway_code ): string {
        $trimmed_payment_method_gateway_code = trim( $payment_instrument_gateway_code );

        return self::PAYMENT_INSTRUMENT_TITLE_MAP[ $trimmed_payment_method_gateway_code ] ?? $trimmed_payment_method_gateway_code;
    }

    /**
     * Check whether wallet and payment instrument titles can be combined.
     *
     * @param string $payment_instrument_gateway_code
     * @return bool
     */
    public function can_build_wallet_combined_payment_method_title( string $payment_instrument_gateway_code ): bool {
        return isset( self::PAYMENT_INSTRUMENT_TITLE_MAP[ trim( $payment_instrument_gateway_code ) ] );
    }

    /**
     * Build the combined payment method title, e.g., Google Pay (Visa)
     *
     * @param string $wallet_title
     * @param string $payment_instrument_title
     * @return string
     */
    public function build_combined_payment_method_title( string $wallet_title, string $payment_instrument_title ): string {
        if ( '' === $wallet_title ) {
            return $payment_instrument_title;
        }

        if ( '' === $payment_instrument_title ) {
            return $wallet_title;
        }

        return $wallet_title . ' (' . $payment_instrument_title . ')';
    }
}
