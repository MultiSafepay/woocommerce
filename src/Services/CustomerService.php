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
use MultiSafepay\WooCommerce\Services\Blocks\BlocksContextService;
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
     * @var BlocksPaymentDataService
     */
    private $blocks_payment_data_service;

    /**
     * @param Logger|null $logger
     */
    public function __construct( ?Logger $logger = null ) {
        $this->logger                      = $logger ?? new Logger();
        $this->blocks_payment_data_service = new BlocksPaymentDataService();
    }

    /**
     * Browser info is sent as a JSON string from the frontend.
     * Treat it as opaque: do not sanitize as plain text.
     *
     * @param string $value
     * @return string
     */
    private function normalize_browser_payload( string $value ): string {
        $value = trim( $value );
        if ( '' === $value ) {
            return '';
        }

        $value = str_replace( "\0", '', $value );

        $decoded = json_decode( $value, true );
        if ( ! is_array( $decoded ) || ! isset( $decoded['browser'] ) || ! is_array( $decoded['browser'] ) ) {
            return '';
        }

        $normalized_browser = $this->normalize_browser_fields( $decoded['browser'] );
        if ( empty( $normalized_browser ) ) {
            return '';
        }

        $normalized_value = wp_json_encode(
            array(
                'browser' => $normalized_browser,
            )
        );

        return is_string( $normalized_value ) ? $normalized_value : '';
    }

    /**
     * Normalize expected browser fields to a stable and bounded payload.
     *
     * @param array $browser
     * @return array
     */
    private function normalize_browser_fields( array $browser ): array {
        $boolean_fields     = array( 'javascript_enabled', 'java_enabled', 'cookies_enabled' );
        $normalized_browser = array();
        $field_rules        = array_fill_keys(
            $boolean_fields,
            array( 'type' => 'bool' )
        ) + array(
            'language'           => array(
                'type'       => 'string',
                'max_length' => 35,
            ),
            'screen_color_depth' => array(
                'type'           => 'int',
                'allowed_values' => array( 1, 4, 8, 15, 16, 24, 32, 48 ),
            ),
            'screen_width'       => array(
                'type'      => 'int',
                'min_value' => 0,
            ),
            'screen_height'      => array(
                'type'      => 'int',
                'min_value' => 0,
            ),
            'time_zone'          => array(
                'type'      => 'int',
                'min_value' => -1440,
                'max_value' => 1440,
            ),
            'user_agent'         => array(
                'type'       => 'string',
                'max_length' => 512,
            ),
            'platform'           => array(
                'type'       => 'string',
                'max_length' => 128,
            ),
        );

        foreach ( $field_rules as $field => $rule ) {
            if ( ! array_key_exists( $field, $browser ) ) {
                continue;
            }

            $normalized_value = $this->normalize_browser_field_by_rule( $browser[ $field ], $rule );
            if ( null === $normalized_value ) {
                continue;
            }

            $normalized_browser[ $field ] = $normalized_value;
        }

        return $normalized_browser;
    }

    /**
     * Normalize a browser field according to its configured rule.
     *
     * @param mixed $value
     * @param array $rule
     * @return bool|int|string|null
     */
    private function normalize_browser_field_by_rule( $value, array $rule ) {
        if ( 'bool' === $rule['type'] ) {
            return $this->normalize_browser_boolean_field( $value );
        }

        if ( 'int' === $rule['type'] ) {
            return $this->normalize_browser_integer_field( $value, $rule );
        }

        return $this->normalize_browser_string_field( $value, $rule['max_length'] );
    }

    /**
     * Normalize a scalar browser field into a trimmed string without truncation.
     *
     * @param mixed $value
     * @return string
     */
    private function normalize_browser_scalar_string_value( $value ): string {
        if ( is_bool( $value ) ) {
            $value = $value ? 'true' : 'false';
        }

        if ( is_int( $value ) || is_float( $value ) ) {
            $value = (string) $value;
        }

        if ( ! is_string( $value ) ) {
            return '';
        }

        return trim( str_replace( "\0", '', $value ) );
    }

    /**
     * Normalize a browser string field by trimming, removing null bytes, and truncating.
     *
     * @param mixed $value
     * @param int   $max_length
     * @return string
     */
    private function normalize_browser_string_field( $value, int $max_length ): string {
        $value = $this->normalize_browser_scalar_string_value( $value );
        if ( '' === $value ) {
            return '';
        }

        return strlen( $value ) > $max_length ? substr( $value, 0, $max_length ) : $value;
    }

    /**
     * Normalize a browser boolean field from scalar input.
     *
     * @param mixed $value
     * @return bool
     */
    private function normalize_browser_boolean_field( $value ): bool {
        if ( is_bool( $value ) ) {
            return $value;
        }

        $value = strtolower( $this->normalize_browser_scalar_string_value( $value ) );
        return in_array( $value, array( '1', 'true' ), true );
    }

    /**
     * Normalize a browser integer field from scalar input.
     *
     * @param mixed $value
     * @param array $rule
     * @return int|null
     */
    private function normalize_browser_integer_field( $value, array $rule ): ?int {
        $normalized_value = is_int( $value ) ? $value : null;

        if ( null === $normalized_value ) {
            $value = $this->normalize_browser_scalar_string_value( $value );
            if ( '' === $value || ! preg_match( '/^-?\d+$/', $value ) ) {
                return 0;
            }

            $normalized_value = (int) $value;
        }

        if ( isset( $rule['allowed_values'] ) && ! in_array( $normalized_value, $rule['allowed_values'], true ) ) {
            return null;
        }

        if ( isset( $rule['min_value'] ) ) {
            $normalized_value = max( $rule['min_value'], $normalized_value );
        }

        if ( isset( $rule['max_value'] ) ) {
            $normalized_value = min( $rule['max_value'], $normalized_value );
        }

        return $normalized_value;
    }

    /**
     * @param WC_Order $order
     * @return CustomerDetails
     * @throws InvalidArgumentException
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
            $this->should_send_customer_reference( $order ) ? (string) $order->get_customer_id() : null,
            $this->get_customer_browser_info( $order )
        );
    }

    /**
     * Return browser information
     *
     * @param WC_Order|null $order
     * @return array|null
     */
    protected function get_customer_browser_info( ?WC_Order $order = null ): ?array {
        $browser = ( new BlocksContextService() )->is_store_api_request()
            ? $this->get_blocks_checkout_browser_payload( $order )
            : $this->get_classic_checkout_browser_payload();

        return $this->decode_browser_payload( $browser );
    }

    /**
     * Read browser information from classic checkout POST data.
     *
     * @return string
     */
    private function get_classic_checkout_browser_payload(): string {
        return $this->normalize_browser_payload( $this->get_request_browser_payload( 'browser', false ) );
    }

    /**
     * Read browser information from Blocks request- / meta-sources.
     *
     * @param WC_Order|null $order
     * @return string
     */
    private function get_blocks_checkout_browser_payload( ?WC_Order $order = null ): string {
        $browser = $this->normalize_browser_payload( $this->get_request_browser_payload( 'browser', true ) );

        if ( ! empty( $browser ) || ! $order instanceof WC_Order ) {
            return $browser;
        }

        $payment_method_id  = (string) $order->get_payment_method();
        $payment_method_key = $payment_method_id ? $payment_method_id . '_browser' : '';

        if ( ! empty( $payment_method_key ) ) {
            $browser = $this->normalize_browser_payload( $this->get_request_browser_payload( $payment_method_key, true ) );

            if ( ! empty( $browser ) ) {
                return $browser;
            }

            $browser = $this->normalize_browser_payload( $this->blocks_payment_data_service->get_blocks_payment_data_value( $order, $payment_method_key ) );

            if ( ! empty( $browser ) ) {
                return $browser;
            }
        }

        return $this->normalize_browser_payload( $this->blocks_payment_data_service->get_blocks_payment_data_value( $order, 'browser' ) );
    }

    /**
     * Read the raw browser JSON payload from the request.
     *
     * @param string $key
     * @param bool   $is_store_api_request
     * @return string
     */
    private function get_request_browser_payload( string $key, bool $is_store_api_request ): string {
        if ( ! isset( $_POST[ $key ] ) ) {
            return '';
        }

        // Treat browser payloads as opaque JSON: do not sanitize/alter them, only decode later.
        // Store API requests are already unslashed by the REST layer.
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( $is_store_api_request ) {
            return (string) $_POST[ $key ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        }

        return (string) wp_unslash( $_POST[ $key ] );
        // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    }

    /**
     * Decode browser JSON into the array structure expected by the SDK.
     *
     * @param string $browser
     * @return array|null
     */
    private function decode_browser_payload( string $browser ): ?array {
        if ( '' === $browser ) {
            return null;
        }

        $decoded = json_decode( $browser, true );
        return is_array( $decoded ) ? $decoded : null;
    }

    /**
     * @param WC_Order $order
     * @return CustomerDetails
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
     */
    protected function create_customer(
        Address $address,
        string $email_address,
        string $phone_number,
        string $first_name,
        string $last_name,
        string $ip_address,
        string $user_agent,
        ?string $company_name = null,
        ?string $customer_id = null,
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
     * @throws InvalidArgumentException
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
     * Normalize tokenize flags from checkout payloads.
     *
     * @param mixed $value
     * @return bool|null Null means the value is not a recognized boolean representation.
     */
    private function normalize_tokenize_flag( $value ): ?bool {
        $normalized_value = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
        return is_bool( $normalized_value ) ? $normalized_value : null;
    }

    /**
     * Customer reference only needs to be sent when a payment token is being used or
     * when payment tokens need to be created.
     *
     * @param WC_Order $order
     * @return bool
     */
    protected function should_send_customer_reference( WC_Order $order ): bool {
        $payment_method_id  = $order->get_payment_method();
        $tokenize_field_key = $payment_method_id . '_payment_component_tokenize';

        $tokenize_from_blocks = trim( $this->blocks_payment_data_service->get_blocks_payment_data_value( $order, $tokenize_field_key ) );
        if ( '' !== $tokenize_from_blocks ) {
            $normalized_tokenize_from_blocks = $this->normalize_tokenize_flag( $tokenize_from_blocks );
            return null === $normalized_tokenize_from_blocks ? false : $normalized_tokenize_from_blocks;
        }

        if ( ! isset( $_POST[ $tokenize_field_key ] ) ) {
            return false;
        }

        $tokenize_from_post            = sanitize_text_field( wp_unslash( $_POST[ $tokenize_field_key ] ) );
        $normalized_tokenize_from_post = $this->normalize_tokenize_flag( $tokenize_from_post );
        return null === $normalized_tokenize_from_post ? false : $normalized_tokenize_from_post;
    }
}
