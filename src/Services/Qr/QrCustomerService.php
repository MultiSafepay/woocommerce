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
}
