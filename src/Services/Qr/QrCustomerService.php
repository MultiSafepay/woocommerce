<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services\Qr;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\WooCommerce\Services\CustomerService;
use MultiSafepay\WooCommerce\Utils\QrOrder;

/**
 * Class QrCustomerService
 *
 * Handles customer data processing for QR-based transactions.
 *
 * @package MultiSafepay\WooCommerce\Services\Qr
 */
class QrCustomerService extends CustomerService {

    /**
     * Create customer details from the current cart and user data.
     *
     * @param array  $customer
     * @param string $type
     * @return CustomerDetails
     */
    public function create_customer_details_from_cart(
        array $customer,
        string $type = 'billing'
    ): CustomerDetails {

        $customer_address = $this->create_address(
            $customer[ $type ]['address_1'],
            $customer[ $type ]['address_2'],
            $customer[ $type ]['country'],
            $customer[ $type ]['state'] ?? '',
            $customer[ $type ]['city'] ?? '',
            $customer[ $type ]['postcode']
        );

        return $this->create_customer(
            $customer_address,
            $customer[ $type ]['email'] ?? $customer['billing']['email'],
            $customer[ $type ]['phone'] ?? $customer['billing']['phone'],
            $customer[ $type ]['first_name'] ?? $customer['billing']['first_name'],
            $customer[ $type ]['last_name'] ?? $customer['billing']['last_name'],
            ( new QrOrder() )->get_customer_ip_address() ?? '',
            ( new QrOrder() )->get_user_agent() ?? '',
            $customer[ $type ]['company'] ?? $customer['billing']['company'],
        );
    }

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
