<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\AddressParser;
use MultiSafepay\ValueObject\Customer\Country;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use MultiSafepay\ValueObject\IpAddress;
use WC_Order;

/**
 * Class CustomerService
 *
 * @package MultiSafepay\WooCommerce\Services
 */
class CustomerService {
    public const DEFAULT_LOCALE = 'en_US';

    /**
     * @param WC_Order $order
     * @return CustomerDetails
     */
    public function create_customer_details( WC_Order $order ): CustomerDetails {
        $customer_address = $this->create_address(
            $order->get_billing_address_1(),
            $order->get_billing_address_2(),
            $order->get_billing_country(),
            $order->get_billing_state(),
            $order->get_billing_city(),
            $order->get_billing_postcode()
        );

        return $this->create_customer(
            $customer_address,
            $order->get_billing_email(),
            $order->get_billing_phone(),
            $order->get_billing_first_name(),
            $order->get_billing_last_name(),
            $order->get_customer_ip_address() ? $order->get_customer_ip_address() : '',
            $order->get_customer_user_agent() ? $order->get_customer_user_agent() : '',
            $order->get_billing_company()
        );
    }

    /**
     * @param WC_Order $order
     * @return CustomerDetails
     */
    public function create_delivery_details( WC_Order $order ): CustomerDetails {
        $delivery_address = $this->create_address(
            $order->get_shipping_address_1(),
            $order->get_shipping_address_2(),
            $order->get_shipping_country(),
            $order->get_shipping_state(),
            $order->get_shipping_city(),
            $order->get_shipping_postcode()
        );

        return $this->create_customer(
            $delivery_address,
            $order->get_billing_email(),
            $order->get_billing_phone(),
            $order->get_shipping_first_name(),
            $order->get_shipping_last_name(),
            '',
            '',
            $order->get_shipping_company()
        );
    }

    /**
     * @param Address $address
     * @param string  $email_address
     * @param string  $phone_number
     * @param string  $first_name
     * @param string  $last_name
     * @param string  $ip_address
     * @param string  $user_agent
     * @param string  $company_name
     * @return CustomerDetails
     */
    private function create_customer(
        Address $address,
        string $email_address,
        string $phone_number,
        string $first_name,
        string $last_name,
        string $ip_address,
        string $user_agent,
        string $company_name = null
    ): CustomerDetails {
        $customer_details = new CustomerDetails();
        $customer_details
            ->addAddress( $address )
            ->addEmailAddress( new EmailAddress( $email_address ) )
            ->addFirstName( $first_name )
            ->addLastName( $last_name )
            ->addPhoneNumber( new PhoneNumber( $phone_number ) )
            ->addLocale( $this->get_locale() )
            ->addCompanyName( $company_name ? $company_name : '' );

        if ( ! empty( $ip_address ) ) {
            $customer_details->addIpAddress( new IpAddress( $ip_address ) );
        }

        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $customer_details->addForwardedIp( new IpAddress( sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
        }

        if ( ! empty( $user_agent ) ) {
            $customer_details->addUserAgent( $user_agent );
        }

        return $customer_details;
    }

    /**
     * @param string $address_line_1
     * @param string $address_line_2
     * @param string $country
     * @param string $state
     * @param string $city
     * @param string $zip_code
     * @return Address
     */
    private function create_address(
        string $address_line_1,
        string $address_line_2,
        string $country,
        string $state,
        string $city,
        string $zip_code
    ): Address {
        $address_parser = new AddressParser();
        $address        = $address_parser->parse( $address_line_1, $address_line_2 );

        $street       = $address[0];
        $house_number = $address[1];

        $customer_address = new Address();
        return $customer_address
            ->addStreetName( $street )
            ->addHouseNumber( $house_number )
            ->addState( $state )
            ->addCity( $city )
            ->addCountry( new Country( $country ) )
            ->addZipCode( $zip_code );
    }

    /**
     * Return customer locale
     *
     * @return string
     */
    public function get_locale(): string {
        $locale = get_locale() ?? self::DEFAULT_LOCALE;
        return apply_filters( 'multisafepay_customer_locale', $locale );
    }

}
