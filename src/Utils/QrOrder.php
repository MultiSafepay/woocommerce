<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

/**
 * This class defines methods to handle easily different actions related with the WC_Order object
 */
class QrOrder {

    /**
     * Get the customer IP address.
     *
     * @return string
     */
    public function get_customer_ip_address(): string {
        $possible_ip_sources = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        );

        foreach ( $possible_ip_sources as $source ) {
            if ( ! empty( $_SERVER[ $source ] ) ) {
                return sanitize_text_field( wp_unslash( $_SERVER[ $source ] ) );
            }
        }

        return '';
    }

    /**
     * Get the user agent.
     *
     * @return string
     */
    public function get_user_agent(): string {
        return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
    }

}
