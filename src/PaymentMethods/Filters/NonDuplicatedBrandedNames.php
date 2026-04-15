<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Filters;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;

/**
 * Filter duplicated branded payment method titles.
 */
class NonDuplicatedBrandedNames {

    /**
     * Filter the payment methods to handle duplicated branded names.
     *
     * @param array $payment_gateways
     * @return array
     */
    public function filter_non_duplicated_branded_names( array $payment_gateways ): array {
        if ( is_admin() ) {
            return $payment_gateways;
        }

        $valid_gateways = array();
        $title_count    = $this->collect_and_count_branded_gateways( $payment_gateways, $valid_gateways );

        // Create a reference association between the valid gateways and the original payment gateways.
        $gateway_map = array();
        foreach ( $valid_gateways as $valid_gateway ) {
            $gateway_map[ $valid_gateway->id ] = $valid_gateway;
        }

        $this->process_gateway_titles( $valid_gateways, $title_count );

        // Use the original payment gateways but modify their titles.
        $result = array();
        foreach ( $payment_gateways as $key => $gateway ) {
            if ( $gateway instanceof BasePaymentMethod && isset( $gateway_map[ $gateway->id ] ) ) {
                $gateway->title = $gateway_map[ $gateway->id ]->title;
            }
            $result[ $key ] = $gateway;
        }

        return $result;
    }

    /**
     * Collect all branded gateway codes and count their occurrences.
     *
     * @param array $payment_gateways
     * @param array &$valid_gateways
     * @return array
     */
    private function collect_and_count_branded_gateways( array $payment_gateways, array &$valid_gateways ): array {
        $gateway_codes = array();

        foreach ( $payment_gateways as $gateway ) {
            if ( ! ( $gateway instanceof BasePaymentMethod ) ) {
                continue;
            }

            $valid_gateways[] = $gateway;
            $gateway_code     = str_replace( 'multisafepay_', '', $gateway->id );
            $gateway_codes[]  = trim( explode( '_', $gateway_code )[0] );
        }

        return array_count_values( $gateway_codes );
    }

    /**
     * Process gateways and modify titles based on occurrence count.
     *
     * @param array $valid_gateways
     * @param array $title_count
     * @return void
     */
    private function process_gateway_titles( array $valid_gateways, array $title_count ): void {
        foreach ( $valid_gateways as $gateway ) {
            $gateway_code = str_replace( 'multisafepay_', '', $gateway->id );
            $base_code    = explode( '_', $gateway_code )[0];

            $gateway->title = $gateway->get_title();
            if ( isset( $title_count[ $base_code ] ) && ( 1 === $title_count[ $base_code ] ) ) {
                $gateway->title = explode( ' - ', $gateway->title )[0];
            }
        }
    }
}
