<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services;

use Exception;
use MultiSafepay\Api\PaymentMethodManager;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidDataInitializationException;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseGiftCardPaymentMethod;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BaseBrandedPaymentMethod;
use MultiSafepay\WooCommerce\Utils\Logger;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Class PaymentMethodsService
 *
 * @package MultiSafepay\WooCommerce\Services
 */
class PaymentMethodService {

    /**
     * Time in seconds, in which the stored value in cache with information about all gateways will expire.
     */
    public const EXPIRATION_TIME_FOR_PAYMENT_METHODS_API_REQUEST = 86400;

    /**
     * Time in seconds, in which the stored value in cache with information about all gateways with QR will expire.
     */
    public const EXPIRATION_TIME_FOR_PAYMENT_METHODS_WITH_QR = 60;

    /**
     * @var PaymentMethodManager
     */
    public $payment_method_manager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger|null $logger
     */
    public function __construct( ?Logger $logger = null ) {
        $this->logger = $logger ?? new Logger();
        try {
            $this->payment_method_manager = ( new SdkService() )->get_payment_method_manager();
        } catch ( ApiException $api_exception ) {
            $this->logger->log_error( $api_exception->getMessage() );
        }
    }

    /**
     *  Check if the current page is the WooCommerce settings page for payment methods
     *
     * @return bool
     *
     * @phpcs:disable WordPress.Security.NonceVerification.Recommended
     */
    public function is_settings_payments_page(): bool {
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
        $tab  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
        return is_admin() && ( 'wc-settings' === $page ) && ( 'checkout' === $tab );
    }

    /**
     * Return an array with the information of each payment method from the API
     * If the result is cached by transient api, will be returned from there,
     * except when the call comes from admin side, in which case it will
     * regenerate the transient.
     *
     * @see https://developer.wordpress.org/reference/functions/set_transient/
     *
     * @return array
     */
    public function get_multisafepay_payment_methods_from_api(): array {
        $multisafepay_payment_methods_in_cache = get_transient( 'multisafepay_payment_methods' );
        $admin_area                            = $this->is_settings_payments_page();

        if ( ( false !== $multisafepay_payment_methods_in_cache ) && ! $admin_area ) {
            return $multisafepay_payment_methods_in_cache;
        }

        if ( null === $this->payment_method_manager ) {
            $this->logger->log_error( 'SDK is not initialized' );
            return array();
        }

        try {
            $multisafepay_payment_methods = $this->payment_method_manager->getPaymentMethodsAsArray(
                true,
                array(
                    'group_cards' => ( (bool) get_option(
                        'multisafepay_group_credit_cards',
                        self::is_multisafepay_credit_card_woocommerce_payment_gateway_enabled()
                    ) ),
                )
            );
        } catch ( Exception | ApiException | InvalidDataInitializationException | ClientExceptionInterface $exception ) {
            $this->logger->log_error( $exception->getMessage() );
            return array();
        }

        set_transient( 'multisafepay_payment_methods', $multisafepay_payment_methods, self::EXPIRATION_TIME_FOR_PAYMENT_METHODS_API_REQUEST );

        return $multisafepay_payment_methods;
    }

    /**
     * Return an array with WC_Payment_Gateway objects, created from the results of the API.
     *
     * @return array
     */
    public function get_woocommerce_payment_gateways() : array {
        $woocommerce_payment_gateways = array();
        $multisafepay_payment_methods = $this->get_multisafepay_payment_methods_from_api();
        foreach ( $multisafepay_payment_methods as $multisafepay_payment_method ) {
            if ( isset( $multisafepay_payment_method['type'] ) ) {
                $woocommerce_payment_gateways = $this->create_woocommerce_payment_gateways( $multisafepay_payment_method, $woocommerce_payment_gateways );
            }
        }

        return $woocommerce_payment_gateways;
    }

    /**
     * @param array $multisafepay_payment_method
     * @param array $woocommerce_payment_gateways
     * @return array
     */
    public function create_woocommerce_payment_gateways( array $multisafepay_payment_method, array $woocommerce_payment_gateways ) : array {
        $payment_method_id = self::get_legacy_woocommerce_payment_gateway_ids( $multisafepay_payment_method['id'] );

        try {
            $payment_method = new PaymentMethod( $multisafepay_payment_method );
        } catch ( InvalidDataInitializationException $exception ) {
            $this->logger->log_error( $exception->getMessage() );
            return $woocommerce_payment_gateways;
        }

        if ( 'payment-method' === $multisafepay_payment_method['type'] ) {
            $woocommerce_payment_gateways[ $payment_method_id ] = new BasePaymentMethod( $payment_method );
            $woocommerce_payment_gateways                       = $this->create_branded_woocommerce_payment_gateways( $multisafepay_payment_method, $woocommerce_payment_gateways, $payment_method );
        }

        if ( 'coupon' === $multisafepay_payment_method['type'] ) {
            $woocommerce_payment_gateways[ $payment_method_id ] = new BaseGiftCardPaymentMethod( $payment_method );
        }

        return $woocommerce_payment_gateways;
    }

    /**
     * @param array         $multisafepay_payment_method
     * @param array         $woocommerce_payment_gateways
     * @param PaymentMethod $payment_method
     * @return array
     */
    public function create_branded_woocommerce_payment_gateways( array $multisafepay_payment_method, array $woocommerce_payment_gateways, PaymentMethod $payment_method ) : array {
        foreach ( $multisafepay_payment_method['brands'] as $brand ) {
            if ( ! empty( $brand['allowed_countries'] ) ) {
                $payment_method_id                                  = self::get_legacy_woocommerce_payment_gateway_ids( $brand['id'] );
                $woocommerce_payment_gateways[ $payment_method_id ] = new BaseBrandedPaymentMethod( $payment_method, $brand );
            }
        }

        return $woocommerce_payment_gateways;
    }

    /**
     * @param string $woocommerce_payment_gateway_id
     * @return BasePaymentMethod|null
     */
    public function get_woocommerce_payment_gateway_by_id( string $woocommerce_payment_gateway_id ): ?BasePaymentMethod {
        $woocommerce_payment_gateways = $this->get_woocommerce_payment_gateways();
        /** @var BasePaymentMethod $woocommerce_payment_gateway */
        foreach ( $woocommerce_payment_gateways as $woocommerce_payment_gateway ) {
            if ( $woocommerce_payment_gateway->get_payment_method_id() === $woocommerce_payment_gateway_id ) {
                return $woocommerce_payment_gateway;
            }
        }
        return null;
    }

    /**
     * Return an array with the WooCommerce payment method object ids
     *
     * @return array
     */
    public function get_woocommerce_payment_gateway_ids(): array {
        $gateways_ids = array();
        foreach ( $this->get_multisafepay_payment_methods_from_api() as $payment_method ) {
            $gateways_ids[] = self::get_legacy_woocommerce_payment_gateway_ids( $payment_method['id'] );
        }
        return $gateways_ids;
    }

    /**
     * Return the WooCommerce payment method object by MultiSafepay gateway code.
     *
     * @param string $code
     * @return ?BasePaymentMethod
     */
    public function get_woocommerce_payment_gateway_by_multisafepay_gateway_code( string $code ): ?BasePaymentMethod {
        $woocommerce_payment_gateways = ( new PaymentMethodService() )->get_woocommerce_payment_gateways();
        /** @var BasePaymentMethod $woocommerce_payment_gateway */
        foreach ( $woocommerce_payment_gateways as $woocommerce_payment_gateway ) {
            if ( self::get_legacy_woocommerce_payment_gateway_ids( $code ) === $woocommerce_payment_gateway->get_payment_method_id() ) {
                return $woocommerce_payment_gateway;
            }
        }
        return null;
    }

    /**
     * Get all active MultiSafepay payment methods which supports payment component
     *
     * @return array
     */
    public function get_woocommerce_payment_gateway_ids_with_payment_component_support(): array {
        $payment_methods_with_payment_component = array();
        foreach ( $this->get_enabled_woocommerce_payment_gateways() as $woocommerce_payment_gateway ) {
            if ( $woocommerce_payment_gateway->is_payment_component_enabled() ) {
                $payment_methods_with_payment_component[] = $woocommerce_payment_gateway->get_payment_method_id();
            }
        }
        return $payment_methods_with_payment_component;
    }

    /**
     * Check if any of the active MultiSafepay payment methods supports payment component with QR
     *
     * @return bool
     */
    public function is_any_woocommerce_payment_gateway_with_payment_component_qr_enabled(): bool {
        $is_any_woocommerce_payment_gateway_with_payment_component_qr_enabled = get_transient( 'is_multisafepay_payment_component_qr_enabled' );

        if ( ( false !== $is_any_woocommerce_payment_gateway_with_payment_component_qr_enabled ) ) {
            return (bool) $is_any_woocommerce_payment_gateway_with_payment_component_qr_enabled;
        }

        /** @var BasePaymentMethod $woocommerce_payment_gateway */
        foreach ( $this->get_enabled_woocommerce_payment_gateways() as $woocommerce_payment_gateway ) {
            if ( $woocommerce_payment_gateway->is_qr_enabled() || $woocommerce_payment_gateway->is_qr_only_enabled() ) {
                set_transient( 'is_multisafepay_payment_component_qr_enabled', true, self::EXPIRATION_TIME_FOR_PAYMENT_METHODS_WITH_QR );
                return true;
            }
        }

        set_transient( 'is_multisafepay_payment_component_qr_enabled', false, self::EXPIRATION_TIME_FOR_PAYMENT_METHODS_WITH_QR );
        return false;
    }

    /**
     * Get all active MultiSafepay WooCommerce payment gateways
     *
     * @return array
     */
    public function get_enabled_woocommerce_payment_gateways(): array {
        $enabled_payment_gateways = array();
        foreach ( $this->get_woocommerce_payment_gateways() as $woocommerce_payment_gateway ) {
            if ( 'yes' === $woocommerce_payment_gateway->enabled ) {
                $enabled_payment_gateways[] = $woocommerce_payment_gateway;
            }
        }
        return $enabled_payment_gateways;
    }

    /**
     * Return the woocommerce_payment_gateway_ids considering those one previously set
     * which don't match the new format
     *
     * @param string $code
     * @return string
     */
    public static function get_legacy_woocommerce_payment_gateway_ids( string $code ): string {
        $woocommerce_payment_gateway_id = 'multisafepay_' . str_replace( '-', '_', sanitize_title( strtolower( $code ) ) );

        $legacy_woocommerce_payment_gateway_ids = array(
            'multisafepay_alipayplus' => 'multisafepay_alipay_plus',
            'multisafepay_amazonbtn'  => 'multisafepay_amazonpay',
            'multisafepay_mistercash' => 'multisafepay_bancontact',
            'multisafepay_bnpl_instm' => 'multisafepay_payafterdelivery_installments',
            'multisafepay_psafecard'  => 'multisafepay_paysafecard',
            'multisafepay_directbank' => 'multisafepay_sofort',
            'multisafepay_babycad'    => 'multisafepay_babycadeaubon',
            'multisafepay_beautywell' => 'multisafepay_beautyandwellness',
            'multisafepay_fashionchq' => 'multisafepay_fashioncheque',
            'multisafepay_fashiongft' => 'multisafepay_fashiongiftcard',
            'multisafepay_gezondheid' => 'multisafepay_gezondheidsbon',
            'multisafepay_natnletuin' => 'multisafepay_nationaletuinbon',
            'multisafepay_parfumcade' => 'multisafepay_parfumcadeaukaart',
            'multisafepay_vvvgiftcrd' => 'multisafepay_vvvcadeaukaart',
        );

        return $legacy_woocommerce_payment_gateway_ids[ $woocommerce_payment_gateway_id ] ?? $woocommerce_payment_gateway_id;
    }

    /**
     * For legacy reasons, this method returns if Credit Card WooCommerce Payment gateway is enabled,
     * in which case this argument is used to return payment methods with argument group_cards as true
     *
     * @return bool
     */
    public static function is_multisafepay_credit_card_woocommerce_payment_gateway_enabled(): bool {

        $multisafepay_credit_card_settings = get_option( 'woocommerce_multisafepay_creditcard_settings', false );

        if ( ! $multisafepay_credit_card_settings ) {
            return false;
        }

        $credit_card_woocommerce_payment_gateway_settings = maybe_unserialize( $multisafepay_credit_card_settings );

        return isset( $credit_card_woocommerce_payment_gateway_settings['enabled'] ) &&
            'yes' === $credit_card_woocommerce_payment_gateway_settings['enabled'];
    }
}
