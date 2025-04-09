<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\AddressParser;
use MultiSafepay\ValueObject\Customer\Country;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use MultiSafepay\ValueObject\IpAddress;
use MultiSafepay\WooCommerce\Utils\Logger;
use WC_Order;

/**
 * Class CustomerService
 *
 * @package MultiSafepay\WooCommerce\Services
 */
class CustomerService {
    public const DEFAULT_LOCALE = 'en_US';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger|null $logger
     */
    public function __construct( ?Logger $logger = null ) {
        $this->logger = $logger ?? new Logger();
    }

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
            $order->get_billing_company(),
            $this->should_send_customer_reference( $order->get_payment_method() ) ? (string) $order->get_customer_id() : null,
            $this->get_customer_browser_info()
        );
    }

    /**
     * Return browser information
     *
     * @return array|null
     */
    protected function get_customer_browser_info(): ?array {
        $browser = sanitize_text_field( wp_unslash( $_POST['browser'] ?? '' ) );

        if ( ! empty( $browser ) ) {
            return json_decode( $browser, true );
        }

        return null;
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
     * @param Address     $address
     * @param string      $email_address
     * @param string      $phone_number
     * @param string      $first_name
     * @param string      $last_name
     * @param string      $ip_address
     * @param string      $user_agent
     * @param null|string $company_name
     * @param null|string $customer_id
     * @param null|array  $browser
     * @return CustomerDetails
     */
    protected function create_customer(
        Address $address,
        string $email_address,
        string $phone_number,
        string $first_name,
        string $last_name,
        string $ip_address,
        string $user_agent,
        string $company_name = null,
        string $customer_id = null,
        ?array $browser = null
    ): CustomerDetails {
        $customer_details = new CustomerDetails();
        $customer_details
            ->addAddress( $address )
            ->addEmailAddress( new EmailAddress( $email_address ) )
            ->addFirstName( $first_name )
            ->addLastName( $last_name )
            ->addPhoneNumber( new PhoneNumber( $phone_number ) )
            ->addLocale( $this->get_locale() )
            ->addCompanyName( $company_name ?? '' );

        if ( ! empty( $ip_address ) ) {
            try {
                $customer_details->addIpAddress( new IpAddress( $ip_address ) );
            } catch ( InvalidArgumentException $invalid_argument_exception ) {
                $this->logger->log_warning( 'Invalid Customer IP address: ' . $invalid_argument_exception->getMessage() );
            }
        }

        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            try {
                $customer_details->addForwardedIp( new IpAddress( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) );
            } catch ( InvalidArgumentException $invalid_argument_exception ) {
                $this->logger->log_warning( 'Invalid Forwarded IP address: ' . $invalid_argument_exception->getMessage() );
            }
        }

        if ( ! empty( $user_agent ) ) {
            $customer_details->addUserAgent( $user_agent );
        }

        if ( ! empty( $customer_id ) ) {
            $customer_details->addReference( $customer_id );
        }

        if ( ! empty( $browser ) ) {
            $customer_details->addData( $browser );
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
    protected function create_address(
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

    /**
     * Customer reference only needs to be sent when a payment token is being used, or
     * when a payment tokens needs to be created.
     *
     * @param string $payment_method_id
     * @return bool
     */
    protected function should_send_customer_reference( string $payment_method_id ): bool {
        if ( ! isset( $_POST[ $payment_method_id . '_payment_component_tokenize' ] ) ) {
            return false;
        }

        if ( ! (bool) $_POST[ $payment_method_id . '_payment_component_tokenize' ] ) {
            return false;
        }

        return true;
    }
}
