<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services;

use WC_Validation;

/**
 * Class ValidationService
 *
 * @package MultiSafepay\WooCommerce\Services
 */
class ValidationService {

    /**
     * Validate a zip code using WooCommerce's validation method
     *
     * @return void
     */
    public function validate_postcode(): void {
        check_ajax_referer( 'multisafepay_validator_nonce', 'security' );

        $postcode = isset( $_POST['postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['postcode'] ) ) : '';
        $country  = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';

        // If WC_Validation is not available, consider it valid and finish
        if ( ! class_exists( 'WC_Validation' ) || ! method_exists( 'WC_Validation', 'is_postcode' ) ) {
            wp_send_json(
                array(
                    'success' => true,
                    'valid'   => true,
                    'error'   => 'WC_Validation class not available',
                )
            );
            return;
        }

        // Use the WooCommerce validation class to validate the zip code
        $is_valid = WC_Validation::is_postcode( $postcode, $country );

        wp_send_json(
            array(
                'success'  => $is_valid,
                'valid'    => $is_valid,
                'postcode' => $postcode,
                'country'  => $country,
            )
        );
    }
}
