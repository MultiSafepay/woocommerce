<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\WooCommerce\Services\BlocksPaymentDataService;
use MultiSafepay\WooCommerce\Services\OrderService;
use MultiSafepay\WooCommerce\Services\PaymentComponentService;
use MultiSafepay\WooCommerce\Services\PaymentMethodService;
use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\WooCommerce\Services\Blocks\BlocksContextService;
use MultiSafepay\WooCommerce\Utils\Logger;
use MultiSafepay\WooCommerce\Utils\PaymentMethodTitleBuilder;
use Psr\Http\Client\ClientExceptionInterface;
use Throwable;
use WC_Countries;
use WC_Order;
use WC_Payment_Gateway;

/**
 * Class BasePaymentMethod
 *
 * @package MultiSafepay\WooCommerce\PaymentMethods\Base
 */
class BasePaymentMethod extends WC_Payment_Gateway {

    use BaseRefunds;

    public const TRANSACTION_TYPE_DIRECT   = 'direct';
    public const TRANSACTION_TYPE_REDIRECT = 'redirect';

    public const GOOGLEPAY_TEST_MERCHANT_ID   = '12345678901234567890';
    public const GOOGLEPAY_TEST_MERCHANT_NAME = 'Example Merchant';
    public const APPLEPAY_TEST_MERCHANT_NAME  = 'Example Merchant';

    public const DIRECT_PAYMENT_METHODS_WITHOUT_COMPONENTS = array(
        'BANKTRANS',
    );

    public const MULTISAFEPAY_COMPONENT_JS_URL  = 'https://pay.multisafepay.com/sdk/components/v2/components.js';
    public const MULTISAFEPAY_COMPONENT_CSS_URL = 'https://pay.multisafepay.com/sdk/components/v2/components.css';

    public const NOT_ALLOW_REFUND_ORDER_STATUSES = array(
        'pending',
        'on-hold',
        'failed',
    );

    public const NOT_ALLOW_REFUND_PAYMENT_METHODS = array(
        'MULTIBANCO',
    );

    /**
     * A PaymentMethod object with the information of the payment method object
     *
     * @var PaymentMethod
     */
    protected $payment_method;

    /**
     * What type of transaction should be 'direct' or 'redirect'
     *
     * @var string
     */
    protected $type;

    /**
     * The MultiSafepay gateway code.
     *
     * @var string
     */
    protected $gateway_code;

    /**
     * The minimum amount for the payment method
     *
     * @var string
     */
    public $min_amount;

    /**
     * A custom initialized order status for this payment method
     *
     * @var string
     */
    public $initial_order_status;

    /**
     * User Roles
     *
     * @var array
     */
    public $user_roles;

    /**
     * If supports the payment component
     *
     * @var bool
     */
    public $payment_component = false;

    /**
     * Merchant name for Google Pay and Apple Pay
     *
     * @var string
     */
    public $merchant_name = '';

    /**
     * Merchant ID for Google Pay
     *
     * @var string
     */
    public $merchant_id = '';

    /**
     * Is WooCommerce checkout blocks active?
     *
     * @var bool
     */
    private $is_checkout_blocks;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * BasePaymentMethod constructor.
     *
     * @param PaymentMethod $payment_method
     * @param Logger|null   $logger
     */
    public function __construct( PaymentMethod $payment_method, ?Logger $logger = null ) {
        $this->logger         = $logger ?? new Logger();
        $this->payment_method = $payment_method;
        $this->supports       = array( 'products', 'refunds' );
        $this->id             = $this->get_payment_method_id();

        $this->is_checkout_blocks = $this->is_woocommerce_checkout_block_active();

        // Classic payment-component assets must always be registered, because the
        // "Pay for order" endpoint (form-pay.php) is rendered with the classic
        // template even when the regular Checkout page uses the WooCommerce
        // Checkout block. The enqueue callbacks themselves are responsible for
        // skipping the standard checkout when Blocks is active to avoid
        // conflicting with the Blocks integration.
        if ( $this->is_payment_component_enabled() ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_payment_component_styles' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_payment_component_scripts' ) );
        }
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_multisafepay_scripts_by_gateway_code' ) );

        $this->type               = $this->get_payment_method_type();
        $this->method_title       = $this->get_payment_method_title();
        $this->method_description = $this->get_payment_method_description();
        $this->gateway_code       = $this->get_payment_method_gateway_code();
        $this->has_fields         = $this->has_fields();
        $this->icon               = $this->get_logo();
        $this->form_fields        = $this->add_form_fields();

        // Init form fields and load the settings.
        $this->init_form_fields();
        $this->init_settings();

        $this->enabled              = $this->get_option( 'enabled', 'yes' );
        $this->title                = $this->get_option( 'title', $this->get_method_title() );
        $this->description          = $this->get_option( 'description' );
        $this->max_amount           = $this->get_option( 'max_amount' );
        $this->min_amount           = $this->get_option( 'min_amount' );
        $this->user_roles           = (array) $this->get_option( 'user_roles', array() );
        $this->countries            = $this->get_option( 'countries' );
        $this->initial_order_status = $this->get_option( 'initial_order_status', false );
        $this->payment_component    = $this->is_payment_component_enabled();
        $this->merchant_name        = $this->get_option( 'merchant_name', false );
        $this->merchant_id          = $this->get_option( 'merchant_id', false );
        $this->errors               = array();

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array(
                $this,
                'process_admin_options',
            )
        );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'display_errors' ) );
    }

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return PaymentMethodService::get_legacy_woocommerce_payment_gateway_ids( $this->payment_method->getId() );
    }

    /**
     * @return string
     */
    public function get_payment_method_gateway_code(): string {
        return $this->payment_method->getId();
    }

    /**
     *  Get the defined direct payment methods without components
     *
     * @return array
     */
    public function get_defined_direct_payment_methods_without_components(): array {
        return apply_filters( 'multisafepay_direct_payment_methods_without_components', self::DIRECT_PAYMENT_METHODS_WITHOUT_COMPONENTS );
    }

    /**
     *  Get the custom payment method type
     *
     * @return bool
     */
    public function is_payment_method_type_direct(): bool {
        return (bool) $this->get_option( 'direct_transaction', '0' ) ||
            (bool) $this->get_option( 'use_direct_button', '0' );
    }

    /**
     * Checks if the admin is editing the checkout page in the admin area
     *
     * This identifies Checkout block editor context in wp-admin, so Blocks gateway
     * registration can rely on enabled settings instead of runtime availability.
     *
     * @return bool
     *
     * @phpcs:disable WordPress.Security.NonceVerification.Recommended
     */
    public function admin_editing_checkout_page(): bool {
        // "null" as default value to avoid matching a potential page with id 0, or false
        $page_post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : null;
        $checkout_id  = (int) get_option( 'woocommerce_checkout_page_id', false );
        $page_action  = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

        return $page_post_id &&
            ( $page_post_id === $checkout_id ) &&
            ( 'edit' === $page_action ) &&
            is_admin() &&
            current_user_can( 'edit_pages' );
    }

    /**
     * Get the payment method type
     *
     * @return string
     */
    public function get_payment_method_type(): string {
        // Converting to direct the transaction type for iDEAL
        if ( $this->is_ideal_2_0() ) {
            return self::TRANSACTION_TYPE_DIRECT;
        }

        return $this->is_payment_method_type_direct() ||
            $this->is_payment_component_enabled()
                ? self::TRANSACTION_TYPE_DIRECT
                : self::TRANSACTION_TYPE_REDIRECT;
    }

    /**
     * Get the status of Google Pay or Apple Pay direct button
     *
     * @param string $payment_method
     *
     * @return string
     */
    public function get_google_apple_pay_use_button( string $payment_method ): string {
        $settings = get_option( 'woocommerce_multisafepay_' . $payment_method . '_settings' );
        if ( is_array( $settings ) && isset( $settings['use_direct_button'] ) ) {
            return '1' === $settings['use_direct_button'] ? self::TRANSACTION_TYPE_DIRECT : self::TRANSACTION_TYPE_REDIRECT;
        }
        return self::TRANSACTION_TYPE_REDIRECT;
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return $this->payment_method->getName();
    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        return sprintf(
        /* translators: %2$: The payment method title */
            __( 'Read more about %2$s on <a href="%1$s" target="_blank">MultiSafepay\'s Docs</a>.', 'multisafepay' ),
            'https://docs.multisafepay.com',
            $this->get_payment_method_title()
        );
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return $this->payment_method->getLargeIconUrl();
    }

    /**
     * @return string
     */
    protected function get_logo(): string {
        return $this->get_payment_method_icon();
    }

    /**
     * @return bool
     */
    public function has_fields(): bool {
        if ( $this->is_payment_component_enabled() ) {
            return true;
        }

        return false;
    }

    /**
     * Return if the tokenization card on file is enabled.
     *
     * @return bool
     */
    public function is_tokenization_enabled(): bool {
        $settings = get_option( 'woocommerce_' . $this->id . '_settings', array( 'tokenization' => 'no' ) );
        if ( ! isset( $settings['tokenization'] ) ) {
            return false;
        }
        return 'yes' === $settings['tokenization'];
    }

    /**
     * Return if the payment component is enabled.
     *
     * @return bool
     */
    public function is_payment_component_enabled(): bool {
        if ( ! $this->payment_method->supportsPaymentComponent() ) {
            return false;
        }

        if ( $this->is_ideal_2_0() ) {
            $settings = get_option( 'woocommerce_' . $this->id . '_settings', array( 'tokenization' => 'yes' ) );
            if ( ! isset( $settings['tokenization'] ) ) {
                return true;
            }
            return 'yes' === $settings['tokenization'];
        }

        $settings = get_option( 'woocommerce_' . $this->id . '_settings', array( 'payment_component' => 'yes' ) );
        if ( ! isset( $settings['payment_component'] ) ) {
            return true;
        }

        if ( $this->is_qr_enabled() || $this->is_qr_only_enabled() ) {
            return true;
        }

        return 'yes' === $settings['payment_component'];
    }

    /**
     * Return if QR is enabled for payment components.
     *
     * @return bool
     */
    public function is_qr_enabled(): bool {
        if ( ! $this->payment_method->supportsQr() ) {
            return false;
        }

        $settings = get_option( 'woocommerce_' . $this->id . '_settings', array( 'payment_component' => 'yes' ) );
        if ( ! isset( $settings['payment_component'] ) ) {
            return false;
        }
        return 'qr' === $settings['payment_component'];
    }

    /**
     * Return if QR only is enabled for payment components.
     *
     * @return bool
     */
    public function is_qr_only_enabled(): bool {
        if ( ! $this->payment_method->supportsQr() ) {
            return false;
        }

        $settings = get_option( 'woocommerce_' . $this->id . '_settings', array( 'payment_component' => 'yes' ) );
        if ( ! isset( $settings['payment_component'] ) ) {
            return false;
        }
        return 'qr_only' === $settings['payment_component'];
    }

    /**
     * Return the custom QR width in pixels
     *
     * @return string
     */
    public function get_qr_width(): string {
        return $this->get_option( 'qr_width', '' );
    }

    /**
     * Enqueue JavaScript related with Payment Component.
     *
     * @return void
     */
    public function enqueue_payment_component_scripts(): void {
        if ( ! $this->should_enqueue_classic_assets() ) {
            return;
        }

        if ( is_checkout() || is_wc_endpoint_url( 'order-pay' ) ) {

            wp_enqueue_script( 'multisafepay-payment-component-script', self::MULTISAFEPAY_COMPONENT_JS_URL, array(), MULTISAFEPAY_PLUGIN_VERSION, true );

            $multisafepay_payment_component_config = ( new PaymentComponentService() )->get_payment_component_arguments( $this );
            $gateways_with_payment_component       = ( new PaymentMethodService() )->get_woocommerce_payment_gateway_ids_with_payment_component_support();

            $route = MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-payment-component.js';
            wp_enqueue_script( 'multisafepay-payment-component-js', $route, array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );

            static $enabled_gateways_added = false;
            if ( ! $enabled_gateways_added ) {
                wp_localize_script( 'multisafepay-payment-component-js', 'multisafepay_payment_component_gateways', $gateways_with_payment_component );
                $enabled_gateways_added = true;
            }
            wp_localize_script( 'multisafepay-payment-component-js', 'payment_component_config_' . $this->id, $multisafepay_payment_component_config );
            wp_enqueue_script( 'multisafepay-payment-component-js' );
        }
    }

    /**
     * Whether the classic (non-Blocks) payment-component assets should be
     * enqueued for the current request.
     *
     * The "Pay for order" endpoint is always rendered by the classic
     * `form-pay.php` template, so the classic assets are required there even
     * when the regular Checkout page uses the WooCommerce Checkout block.
     * In every other context, when Blocks is active for the Checkout page,
     * the dedicated Blocks integration is responsible for loading its own
     * assets, and the classic ones must not be loaded to avoid conflicts.
     *
     * @return bool
     */
    private function should_enqueue_classic_assets(): bool {
        if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
            return true;
        }

        return ! $this->is_checkout_blocks;
    }

    /**
     * Check if the woocommerce checkout block is active.
     *
     * @return bool
     */
    private function is_woocommerce_checkout_block_active(): bool {
        $checkout_page_id = wc_get_page_id( 'checkout' );
        $checkout_page    = get_post( $checkout_page_id );
        $has_block        = ( new BlocksContextService() )->is_checkout_blocks_active();

        // Fallback: WC_Blocks_Utils::has_block_in_page() doesn't detect nested blocks (e.g. inside columns).
        // If not detected, check directly in post-content.
        if ( ! $has_block && $checkout_page ) {
            $has_block = strpos( $checkout_page->post_content, 'wp:woocommerce/checkout' ) !== false;
        }

        return $has_block;
    }

    /**
     * Enqueue JavaScript related with a MultiSafepay Payment Method.
     *
     * @return void
     */
    public function enqueue_multisafepay_scripts_by_gateway_code(): void {
        if ( ! $this->should_enqueue_classic_assets() ) {
            return;
        }

        $gateway_code = $this->get_payment_method_gateway_code();

        if ( ( 'APPLEPAY' === $gateway_code ) && ( ( $this->get_google_apple_pay_use_button( 'applepay' ) === self::TRANSACTION_TYPE_REDIRECT ) || is_wc_endpoint_url( 'order-pay' ) ) ) {
            wp_enqueue_script( 'multisafepay-apple-pay-js', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-apple-pay.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
        }

        if ( ( 'GOOGLEPAY' === $gateway_code ) && ( ( $this->get_google_apple_pay_use_button( 'googlepay' ) === self::TRANSACTION_TYPE_REDIRECT ) || is_wc_endpoint_url( 'order-pay' ) ) ) {
            wp_enqueue_script( 'multisafepay-google-pay-js', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-google-pay.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
        }

        // Static variable to track if the payment variables have been added
        static $payment_variables_for_applepay_added  = false;
        static $payment_variables_for_googlepay_added = false;

        if ( is_checkout() && ! is_wc_endpoint_url( 'order-pay' ) ) {
            if ( ( 'APPLEPAY' === $gateway_code ) && ! $payment_variables_for_applepay_added && ( $this->get_google_apple_pay_use_button( 'applepay' ) === self::TRANSACTION_TYPE_DIRECT ) ) {
                wp_enqueue_script( 'multisafepay-apple-pay-wallet', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-apple-pay-wallet.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
                $payment_variables = $this->build_applepay_wallet_variables( self::APPLEPAY_TEST_MERCHANT_NAME ) ?? '';
                wp_add_inline_script( 'multisafepay-apple-pay-wallet', $payment_variables, 'before' );
                // Mark that the payment variables have been added
                $payment_variables_for_applepay_added = true;
            }

            if ( 'GOOGLEPAY' === $gateway_code ) {
                wp_enqueue_script( 'google-pay-js', 'https://pay.google.com/gp/p/js/pay.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
                if ( ! $payment_variables_for_googlepay_added && ( $this->get_google_apple_pay_use_button( 'googlepay' ) === self::TRANSACTION_TYPE_DIRECT ) ) {
                    wp_enqueue_script( 'multisafepay-google-pay-wallet', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-google-pay-wallet.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
                    $payment_variables = $this->build_googlepay_wallet_variables( self::GOOGLEPAY_TEST_MERCHANT_ID, self::GOOGLEPAY_TEST_MERCHANT_NAME ) ?? '';
                    wp_add_inline_script( 'multisafepay-google-pay-wallet', $payment_variables, 'before' );
                    // Mark that the payment variables have been added
                    $payment_variables_for_googlepay_added = true;
                }
            }

            if (
                ( ! $payment_variables_for_googlepay_added || ! $payment_variables_for_applepay_added ) &&
                ( ( 'GOOGLEPAY' === $gateway_code ) || ( 'APPLEPAY' === $gateway_code ) )
            ) {
                wp_enqueue_script( 'multisafepay-validator-wallets', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-validator-wallets.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
                wp_enqueue_script( 'multisafepay-common-wallets', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-common-wallets.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
                // Add parameters for validator wallets
                wp_localize_script(
                    'multisafepay-validator-wallets',
                    'multisafepayParams',
                    array(
                        'location' => admin_url( 'admin-ajax.php' ),
                        'nonce'    => wp_create_nonce( 'multisafepay_validator_nonce' ),
                    )
                );

                $admin_url_array = array(
                    'location' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'total_price_nonce' ),
                );
                wp_localize_script( 'multisafepay-common-wallets', 'configAdminUrlAjax', $admin_url_array );
                wp_enqueue_script( 'multisafepay-jquery-wallets', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-jquery-wallets.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
            }
        }

        // On the order-pay endpoint, also load wallet direct scripts so that
        // Google Pay / Apple Pay direct buttons work for pending-payment orders.
        if ( is_wc_endpoint_url( 'order-pay' ) && ( ( 'GOOGLEPAY' === $gateway_code ) || ( 'APPLEPAY' === $gateway_code ) ) ) {
            $this->enqueue_wallet_direct_scripts_for_order_pay( $gateway_code, $payment_variables_for_googlepay_added, $payment_variables_for_applepay_added );
        }
    }

    /**
     * Enqueue wallet direct scripts (Google Pay / Apple Pay) specifically for
     * the order-pay endpoint. The order-pay page uses a fixed order total
     * instead of the cart total and does not have customer_details fields.
     *
     * @param string $gateway_code                        The gateway code (GOOGLEPAY or APPLEPAY).
     * @param bool   $payment_variables_for_googlepay_added Whether Google Pay variables were already added.
     * @param bool   $payment_variables_for_applepay_added  Whether Apple Pay variables were already added.
     *
     * @return void
     */
    private function enqueue_wallet_direct_scripts_for_order_pay( string $gateway_code, bool $payment_variables_for_googlepay_added, bool $payment_variables_for_applepay_added ): void {
        // Use static variables to prevent adding inline scripts more than once,
        // since wp_add_inline_script() appends content each time it is called.
        static $order_pay_googlepay_inline_added = false;
        static $order_pay_applepay_inline_added  = false;

        // Load the supporting scripts (validator, common utilities, jQuery handler).
        wp_enqueue_script( 'multisafepay-validator-wallets', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-validator-wallets.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
        wp_enqueue_script( 'multisafepay-common-wallets', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-common-wallets.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );

        wp_localize_script(
            'multisafepay-validator-wallets',
            'multisafepayParams',
            array(
                'location' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'multisafepay_validator_nonce' ),
            )
        );

        $admin_url_array = array(
            'location' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'total_price_nonce' ),
        );
        wp_localize_script( 'multisafepay-common-wallets', 'configAdminUrlAjax', $admin_url_array );
        wp_enqueue_script( 'multisafepay-jquery-wallets', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-jquery-wallets.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );

        // Apple Pay wallet script.
        if ( ( 'APPLEPAY' === $gateway_code ) && ! $payment_variables_for_applepay_added && ! $order_pay_applepay_inline_added ) {
            wp_enqueue_script( 'multisafepay-apple-pay-wallet', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-apple-pay-wallet.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
            $payment_variables = $this->build_applepay_wallet_variables( self::APPLEPAY_TEST_MERCHANT_NAME ) ?? '';
            wp_add_inline_script( 'multisafepay-apple-pay-wallet', $payment_variables, 'before' );
            $order_pay_applepay_inline_added = true;
        }

        // Google Pay wallet script.
        if ( 'GOOGLEPAY' === $gateway_code ) {
            wp_enqueue_script( 'google-pay-js', 'https://pay.google.com/gp/p/js/pay.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
            if ( ! $payment_variables_for_googlepay_added && ! $order_pay_googlepay_inline_added ) {
                wp_enqueue_script( 'multisafepay-google-pay-wallet', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-google-pay-wallet.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
                $payment_variables = $this->build_googlepay_wallet_variables( self::GOOGLEPAY_TEST_MERCHANT_ID, self::GOOGLEPAY_TEST_MERCHANT_NAME ) ?? '';
                wp_add_inline_script( 'multisafepay-google-pay-wallet', $payment_variables, 'before' );
                $order_pay_googlepay_inline_added = true;
            }
        }
    }

    /**
     * @return array
     */
    public function add_form_fields(): array {
        $form_fields = array(
            'enabled'              => array(
                'title'   => __( 'Enable/Disable', 'multisafepay' ),
                'label'   => 'Enable ' . $this->get_method_title() . ' Gateway',
                'type'    => 'checkbox',
                'default' => 'no',
            ),
            'title'                => array(
                'title'    => __( 'Title', 'multisafepay' ),
                'type'     => 'text',
                'desc_tip' => __( 'This controls the title which the user sees during checkout.', 'multisafepay' ),
                'default'  => $this->get_method_title(),
            ),
            'description'          => array(
                'title'    => __( 'Description', 'multisafepay' ),
                'type'     => 'textarea',
                'desc_tip' => __( 'This controls the description which the user sees during checkout.', 'multisafepay' ),
                'default'  => '',
            ),
            'initial_order_status' => array(
                'title'    => __( 'Initial Order Status', 'multisafepay' ),
                'type'     => 'select',
                'options'  => $this->get_order_statuses(),
                'desc_tip' => __( 'Initial order status for this payment method.', 'multisafepay' ),
                'default'  => 'wc-default',
            ),
            'min_amount'           => array(
                'title'    => __( 'Min Amount', 'multisafepay' ),
                'type'     => 'decimal',
                'desc_tip' => __( 'This payment method is not shown in the checkout if the order total is lower than the defined amount. Leave blank for no restrictions.', 'multisafepay' ),
                'default'  => ( (int) $this->payment_method->getMinAmount() / 100 ),
                'value'    => (float) $this->get_option( 'min_amount', ( (int) $this->payment_method->getMinAmount() / 100 ) ),
            ),
            'max_amount'           => array(
                'title'    => __( 'Max Amount', 'multisafepay' ),
                'type'     => 'decimal',
                'desc_tip' => __( 'This payment method is not shown in the checkout if the order total exceeds a certain amount. Leave blank for no restrictions.', 'multisafepay' ),
                'default'  => $this->payment_method->getMaxAmount() ? ( (int) $this->payment_method->getMaxAmount() / 100 ) : '',
                'value'    => (float) $this->get_option( 'max_amount', ( $this->payment_method->getMaxAmount() ? ( (int) $this->payment_method->getMaxAmount() / 100 ) : '' ) ),
            ),
            'countries'            => array(
                'title'       => __( 'Country', 'multisafepay' ),
                'type'        => 'multiselect',
                'description' => __( 'If you select one or more countries, this payment method will be shown in the checkout page, if the payment address`s country of the customer match with the selected values. Leave blank for no restrictions.', 'multisafepay' ),
                'desc_tip'    => __( 'For most operating system and configurations, you must hold Ctrl or Cmd in your keyboard, while you click in the options to select more than one value.', 'multisafepay' ),
                'options'     => $this->get_countries(),
                'default'     => $this->get_option( 'countries', array() ),
            ),
            'user_roles'           => array(
                'title'       => __( 'User Roles', 'multisafepay' ),
                'type'        => 'multiselect',
                'description' => __( 'If you select one or more user roles, this payment method will be shown in the checkout page, if the user rules of the customer match with the selected values. Leave blank for no restrictions.', 'multisafepay' ),
                'desc_tip'    => __( 'For most operating system and configurations, you must hold Ctrl or Cmd in your keyboard, while you click in the options to select more than one value.', 'multisafepay' ),
                'options'     => $this->get_user_roles(),
                'default'     => $this->get_option( 'user_roles', array() ),
            ),
        );

        if ( 'APPLEPAY' === $this->get_payment_method_gateway_code() ) {
            $form_fields['use_direct_button'] = array(
                'title'       => __( 'Use Apple Pay Direct Button', 'multisafepay' ),
                'type'        => 'select',
                'options'     => array(
                    '0' => 'Disabled',
                    '1' => 'Enabled',
                ),
                'description' => __( 'If you enable this, the place order button on the checkout page will be replaced by the Apple Pay button when this method is selected. We strongly recommend you test this feature carefully before enabled. More information about Apple Pay Direct on <a href="https://docs.multisafepay.com/docs/apple-pay-direct" target="_blank">MultiSafepay\'s Documentation Center</a>', 'multisafepay' ),
                'desc_tip'    => __( 'Enable this feature, to replace the place order button on the checkout page with the Apple Pay button, when the customer selects this one.', 'multisafepay' ),
                'default'     => '0',
            );
            $form_fields['merchant_name']     = array(
                'title'    => __( 'Apple Pay Merchant Name', 'multisafepay' ),
                'type'     => 'text',
                'desc_tip' => __( 'Field required by Apple Pay direct transactions.', 'multisafepay' ),
                'default'  => '',
            );
        }

        if ( in_array( $this->get_payment_method_gateway_code(), $this->get_defined_direct_payment_methods_without_components(), true ) ) {
            $form_fields['direct_transaction'] = array(
                'title'    => __( 'Transaction Type', 'multisafepay' ),
                'type'     => 'select',
                'options'  => array(
                    '0' => 'Redirect',
                    '1' => 'Direct',
                ),
                'desc_tip' => __( 'If enabled, the consumer receives an e-mail with payment details, and no extra information is required during checkout.', 'multisafepay' ),
                'default'  => '0',
            );
        }

        if ( 'GOOGLEPAY' === $this->get_payment_method_gateway_code() ) {
            $form_fields['use_direct_button'] = array(
                'title'       => __( 'Use Google Pay Direct Button', 'multisafepay' ),
                'type'        => 'select',
                'options'     => array(
                    '0' => 'Disabled',
                    '1' => 'Enabled',
                ),
                'description' => __( 'If you enable this, the place order button on the checkout page will be replaced by the Google Pay button when this method is selected. We strongly recommend you test this feature carefully before enabled. More information about Google Pay Direct on <a href="https://docs.multisafepay.com/docs/google-pay-direct" target="_blank">MultiSafepay\'s Documentation Center</a>', 'multisafepay' ),
                'desc_tip'    => __( 'Enable this feature, to replace the place order button on the checkout page with the Google Pay button, when the customer selects this one.', 'multisafepay' ),
                'default'     => '0',
            );
            $form_fields['merchant_name']     = array(
                'title'    => __( 'Google Merchant Name', 'multisafepay' ),
                'type'     => 'text',
                'desc_tip' => __( 'Field required by Google Pay direct transactions.', 'multisafepay' ),
                'default'  => '',
            );
            $form_fields['merchant_id']       = array(
                'title'    => __( 'Google Merchant ID', 'multisafepay' ),
                'type'     => 'text',
                'desc_tip' => __( 'Field required by Google Pay direct transactions.', 'multisafepay' ),
                'default'  => '',
            );
        }

        if ( $this->payment_method->supportsPaymentComponent() && ! $this->payment_method->supportsQr() && ! $this->is_ideal_2_0() ) {
            $form_fields['payment_component'] = array(
                'title'       => __( 'Payment Type', 'multisafepay' ),
                'type'        => 'select',
                'options'     => array(
                    'no'  => __( 'Redirect', 'multisafepay' ),
                    'yes' => __( 'Payment component', 'multisafepay' ),
                ),
                'description' => __( 'Redirect - Redirect the customer to a payment page to finish the payment. <br /> Payment Component - Payment components let you embed payment checkout fields directly into your checkout. <br /><br /> More information about Payment Components on <a href="https://docs.multisafepay.com/docs/payment-components" target="_blank">MultiSafepay\'s Documentation Center</a>.', 'multisafepay' ),
                'default'     => $this->get_option( 'payment_component', $this->payment_method->supportsPaymentComponent() ? 'yes' : 'no' ),
                'value'       => $this->get_option( 'payment_component', $this->payment_method->supportsPaymentComponent() ? 'yes' : 'no' ),
            );

            if ( 'BILLINK' === $this->get_payment_method_gateway_code() ) {
                $form_fields['payment_component']['options']['P'] = __( 'B2C Only', 'multisafepay' );
                $form_fields['payment_component']['options']['B'] = __( 'B2B Only', 'multisafepay' );
                $form_fields['payment_component']['description'] .= __( '<br /><br /> B2C Only - For individual customers. <br /> B2B Only - For business customers. <br /><br /> Select the appropriate contract type based on your target customer base.', 'multisafepay' );
            }
        }

        if ( $this->payment_method->supportsPaymentComponent() && $this->payment_method->supportsQr() ) {
            $form_fields['payment_component'] = array(
                'title'       => __( 'Payment Type', 'multisafepay' ),
                'type'        => 'select',
                'options'     => array(
                    'no'      => __( 'Redirect', 'multisafepay' ),
                    'yes'     => __( 'Payment component', 'multisafepay' ),
                    'qr'      => __( 'Payment component with QR', 'multisafepay' ),
                    'qr_only' => __( 'Payment component with QR only', 'multisafepay' ),
                ),
                'description' => __( 'Redirect - Redirect the customer to a payment page to finish the payment. <br /> Payment Component - Payment components let you embed payment checkout fields directly into your checkout. <br /> Payment Component with QR (*) – Similar to the previous option, but now includes the ability to pay using a QR code too. <br /> Payment Component with QR only (*) – Payment can only be completed via a QR code. <br /><br /> (*) Payment Components using QR are an experimental feature and may not work as expected. Contact MultiSafepay support for more information. <br /><br /> More information about Payment Components on <a href="https://docs.multisafepay.com/docs/payment-components" target="_blank">MultiSafepay\'s Documentation Center</a>.', 'multisafepay' ),
                'default'     => $this->get_option( 'payment_component', $this->payment_method->supportsPaymentComponent() ? 'yes' : 'no' ),
                'value'       => $this->get_option( 'payment_component', $this->payment_method->supportsPaymentComponent() ? 'yes' : 'no' ),
            );
            $form_fields['qr_width']          = array(
                'title'       => __( 'QR image width', 'multisafepay' ),
                'type'        => 'text',
                'desc_tip'    => __( 'Defines the width of the QR code in pixels. An appropriate size improves user experience and makes scanning easier.', 'multisafepay' ),
                'description' => __( 'The numeric value represents the width in pixels. If left blank, the default size will be used.', 'multisafepay' ),
                'default'     => '',
            );
        }

        if ( $this->payment_method->supportsTokenization() && $this->payment_method->supportsPaymentComponent() ) {
            $form_fields['tokenization'] = array(
                'title'       => __( 'Recurring payments', 'multisafepay' ),
                'type'        => 'select',
                'options'     => array(
                    'no'  => __( 'Disabled', 'multisafepay' ),
                    'yes' => __( 'Enabled', 'multisafepay' ),
                ),
                'description' => __( 'Ensure that the Payment component is enabled. It won\'t work using redirect payment type.', 'multisafepay' ),
                'default'     => $this->get_option( 'tokenization', 'no' ),
                'value'       => $this->get_option( 'tokenization', 'no' ),
            );

            if ( $this->is_ideal_2_0() ) {
                unset( $form_fields['tokenization']['description'] );
            }
        }

        return $form_fields;
    }

    /**
     * Enqueue CSS styles related with Payment Component.
     *
     * @return void
     */
    public function enqueue_payment_component_styles() {
        if ( ! $this->should_enqueue_classic_assets() ) {
            return;
        }

        if ( ( is_checkout() || is_wc_endpoint_url( 'order-pay' ) ) ) {
            wp_enqueue_style(
                'multisafepay-payment-component-style',
                self::MULTISAFEPAY_COMPONENT_CSS_URL,
                array(),
                MULTISAFEPAY_PLUGIN_VERSION
            );
        }
    }

    /**
     * Prints checkout custom fields
     *
     * @return void
     */
    public function payment_fields() {
        require MULTISAFEPAY_PLUGIN_DIR_PATH . 'templates/multisafepay-checkout-fields-display.php';
    }

    /**
     * @param WC_Order $order
     * @return bool
     */
    public function can_refund_order( $order ): bool {
        if ( in_array( $this->get_payment_method_gateway_code(), self::NOT_ALLOW_REFUND_PAYMENT_METHODS, true ) ) {
            return false;
        }

        if ( in_array( $order->get_status(), self::NOT_ALLOW_REFUND_ORDER_STATUSES, true ) ) {
            return false;
        }

        return $order && $this->supports( 'refunds' );
    }

    /**
     * @param int|string $order_id
     * @return array
     */
    public function process_payment( $order_id ): array {
        return ( new BlocksContextService() )->is_store_api_request()
            ? $this->process_blocks_payment( $order_id )
            : $this->process_classic_payment( $order_id );
    }

    /**
     * Handles classic checkout payment processing.
     *
     * @param int|string $order_id
     * @return array
     */
    private function process_classic_payment( $order_id ): array {
        $order = wc_get_order( $order_id );
        if ( ! $order instanceof WC_Order ) {
            wc_add_notice( __( 'Invalid order. Please try again.', 'multisafepay' ), 'error' );

            return $this->get_invalid_order_process_payment_response();
        }

        try {
            $transaction = $this->create_transaction_for_payment( $order, new OrderService() );
        } catch ( ApiException | ClientExceptionInterface | InvalidArgumentException $exception ) {
            $this->logger->log_error( $exception->getMessage() );
            wc_add_notice( __( 'There was a problem processing your payment. Please try again later or contact with us.', 'multisafepay' ), 'error' );

            return $this->get_failed_process_payment_response();
        }

        $payment_url = $transaction->getPaymentUrl();
        $this->logger->log_info( 'Start MultiSafepay transaction for the order ID ' . $order_id . ' on ' . date( 'd/m/Y H:i:s' ) . ' with payment URL ' . $payment_url );

        return array(
            'result'   => 'success',
            'redirect' => esc_url_raw( $payment_url ),
        );
    }

    /**
     * Handles Store API / Blocks payment processing.
     *
     * @param int|string $order_id
     * @return array
     */
    private function process_blocks_payment( $order_id ): array {
        $order_service = new OrderService();
        $order         = wc_get_order( $order_id );

        if ( ! $order instanceof WC_Order ) {
            return $this->get_invalid_order_process_payment_response();
        }

        $is_wallet_gateway                        = in_array( (string) $this->gateway_code, array( 'APPLEPAY', 'GOOGLEPAY' ), true );
        $has_wallet_token                         = $is_wallet_gateway && ! empty( $order_service->get_wallet_payment_token( $order ) );
        $has_wallet_combined_payment_method_title = false;

        try {
            $transaction = $this->create_transaction_for_payment( $order, $order_service );

            // In Blocks wallet-direct flows, callback enrichment can arrive after redirect.
            // Use payment_details from the create() transaction to prebuild and persist the
            // combined wallet title, so the initial 'Thank You' render does not show a generic wallet label.
            if ( $is_wallet_gateway && $has_wallet_token ) {
                $has_wallet_combined_payment_method_title = $this->persist_wallet_combined_payment_method_title_from_transaction( $order, $transaction );
            }

            $payment_url = $transaction->getPaymentUrl();
            $this->logger->log_info( 'Start MultiSafepay transaction for the order ID ' . $order_id . ' on ' . date( 'd/m/Y H:i:s' ) . ' with payment URL ' . $payment_url );

            $redirect_url = ( ( $is_wallet_gateway && $has_wallet_token && $has_wallet_combined_payment_method_title ) || empty( $payment_url ) )
                ? $this->get_return_url( $order )
                : esc_url_raw( $payment_url );

            return array(
                'result'   => 'success',
                'redirect' => $redirect_url,
            );
        } catch ( ApiException | ClientExceptionInterface | InvalidArgumentException $exception ) {
            $this->logger->log_error( $exception->getMessage() );

            return $this->get_failed_process_payment_response();
        } finally {
            $this->clear_blocks_process_payment_data( $order, $order_id, $order_service );
        }
    }

    /**
     * Creates the MultiSafepay transaction for the given order.
     *
     * Exposed as protected so tests can override it to bypass the real SDK call.
     *
     * @param WC_Order     $order
     * @param OrderService $order_service
     * @return TransactionResponse
     * @throws ApiException
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     */
    protected function create_transaction_for_payment( WC_Order $order, OrderService $order_service ): TransactionResponse {
        $transaction_manager = ( new SdkService() )->get_transaction_manager();
        $order_request       = $order_service->create_order_request( $order, $this->gateway_code, $this->type );

        return $transaction_manager->create( $order_request );
    }

    /**
     * Best-effort cleanup for request-lifecycle Blocks checkout data.
     *
     * @param WC_Order     $order
     * @param int|string   $order_id
     * @param OrderService $order_service
     * @return void
     */
    private function clear_blocks_process_payment_data( WC_Order $order, $order_id, OrderService $order_service ): void {
        try {
            $order_service->blocks_payment_data_service->clear_blocks_payment_data_from_order_meta_by_pattern(
                $order,
                BlocksPaymentDataService::REQUEST_LIFECYCLE_KEY_PATTERN
            );
        } catch ( Throwable $exception ) {
            $this->logger->log_warning( 'Unable to clear request-lifecycle Blocks payment data for order ID ' . $order_id . ': ' . $exception->getMessage() );
        }
    }

    /**
     * Returns the invalid-order response expected by WooCommerce payment flows.
     *
     * @return array
     */
    private function get_invalid_order_process_payment_response(): array {
        return array(
            'result'  => 'failure',
            'message' => __( 'Invalid order. Please try again.', 'multisafepay' ),
        );
    }

    /**
     * Returns the generic processing failure response expected by WooCommerce payment flows.
     *
     * @return array
     */
    private function get_failed_process_payment_response(): array {
        return array(
            'result'  => 'failure',
            'message' => __( 'There was a problem processing your payment. Please try again later or contact with us.', 'multisafepay' ),
        );
    }

    /**
     * Persist a combined wallet title (e.g., Google Pay (Visa)) using data from
     * the transaction returned by create().
     *
     * The wallet code and payment instrument type come from payment_details; the
     * wallet label is resolved from the configured WooCommerce gateway and then
     * combined with the instrument label.
     *
     * This mitigates the Blocks timing gap where the 'Thank You' page can render
     * before callback enrichment.
     *
     * @param WC_Order $order
     * @param mixed    $transaction
     * @return bool
     */
    protected function persist_wallet_combined_payment_method_title_from_transaction( WC_Order $order, $transaction ): bool {
        try {
            // Guard against unexpected SDK responses; in edge cases the create() transaction
            // may not include payment_details, so a combined wallet title cannot be built.
            if ( ! is_object( $transaction ) || ! method_exists( $transaction, 'getPaymentDetails' ) ) {
                return false;
            }

            $payment_details = $transaction->getPaymentDetails();
            if ( ! is_object( $payment_details ) || ! method_exists( $payment_details, 'getType' ) || ! method_exists( $payment_details, 'get' ) ) {
                return false;
            }

            // `wallet` identifies the wallet wrapper (Google/Apple Pay), while `type` carries the underlying instrument.
            $wallet_code  = (string) $payment_details->get( 'wallet' );
            $gateway_code = (string) $payment_details->getType();
            if ( '' === $wallet_code || '' === $gateway_code ) {
                return false;
            }

            // Resolve the wallet title from the configured WooCommerce gateway to keep labels consistent.
            $wallet_payment_method = $this->resolve_wallet_woocommerce_payment_gateway( $wallet_code );
            if ( ! $wallet_payment_method ) {
                return false;
            }

            $wallet_title = $wallet_payment_method->get_payment_method_title();
            if ( '' === $wallet_title ) {
                return false;
            }

            $payment_method_title_builder = new PaymentMethodTitleBuilder();
            // Only combine titles for known instruments (e.g., Visa, Mastercard, Amex).
            if ( ! $payment_method_title_builder->can_build_wallet_combined_payment_method_title( $gateway_code ) ) {
                return false;
            }

            $underlying_title = $payment_method_title_builder->get_underlying_payment_method_title( $gateway_code );
            $combined_title   = $payment_method_title_builder->build_combined_payment_method_title( $wallet_title, $underlying_title );
            if ( '' === $combined_title ) {
                return false;
            }

            // Avoid unnecessary order writes when title is already persisted.
            if ( $order->get_payment_method_title() !== $combined_title ) {
                $order->set_payment_method_title( $combined_title );
                $order->save();
            }

            return true;
        } catch ( Throwable $exception ) {
            $this->logger->log_warning( 'Unable to persist combined wallet payment title for order ID ' . $order->get_id() . ': ' . $exception->getMessage() );
            return false;
        }
    }

    /**
     * Resolve a configured WooCommerce gateway for the given MultiSafepay wallet code.
     *
     * Extracted as a protected seam so tests can supply a stub wallet gateway without
     * hitting the MultiSafepay API through PaymentMethodService.
     *
     * @param string $wallet_code
     * @return BasePaymentMethod|null
     */
    protected function resolve_wallet_woocommerce_payment_gateway( string $wallet_code ): ?BasePaymentMethod {
        return ( new PaymentMethodService() )->get_woocommerce_payment_gateway_by_multisafepay_gateway_code( $wallet_code );
    }

    /**
     * Validate_fields
     *
     * @return  boolean
     */
    public function validate_fields(): bool {

        if ( is_wc_endpoint_url( 'add-payment-method' ) ) {
            return false;
        }

        // Checkout Blocks uses the Store API; payloads may be stored in order meta and later
        // consumed by OrderService. This POST check applies only to classic checkout, since
        // Store API surfaces errors as REST exceptions rather than HTML notices.
        $is_store_api_request = ( new BlocksContextService() )->is_store_api_request();

        if (
            $this->is_payment_component_enabled() &&
            ! $is_store_api_request &&
            (
                ! isset( $_POST[ $this->id . '_payment_component_payload' ] ) ||
                empty( $_POST[ $this->id . '_payment_component_payload' ] )
            )
        ) {
            wc_add_notice( '<strong>' . $this->get_payment_method_title() . ' payment details</strong> is a required field.', 'error' );
        }

        if ( isset( $_POST[ $this->id . '_payment_component_errors' ] ) && '' !== $_POST[ $this->id . '_payment_component_errors' ] ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            foreach ( wp_unslash( $_POST[ $this->id . '_payment_component_errors' ] ) as $payment_component_error ) {
                wc_add_notice( sanitize_text_field( $payment_component_error ), 'error' );
            }
        }

        if ( wc_get_notices( 'error' ) ) {
            return false;
        }

        return true;
    }

    /**
     * Returns the WooCommerce registered order statuses
     *
     * @see     http://hookr.io/functions/wc_get_order_statuses/
     * @return  array
     */
    protected function get_order_statuses(): array {
        $order_statuses               = wc_get_order_statuses();
        $order_statuses['wc-default'] = __( 'Default value set in common settings', 'multisafepay' );
        return $order_statuses;
    }

    /**
     * Build the variables required to initialize the Credit Card direct transactions
     *
     * @param string $payment_method
     * @return ?array
     */
    private function common_wallets_data( string $payment_method ): ?array {
        $sdk_service   = new SdkService();
        $environment   = $sdk_service->get_test_mode() ? 'TEST' : 'LIVE';
        $debug_mode    = (bool) get_option( 'multisafepay_debugmode', false );
        $currency_code = get_woocommerce_currency();

        // Wallet config is exposed through BasePaymentMethodBlocks::get_payment_method_data()
        // in both frontend checkout and the Checkout block editor. In the wp-admin / editor context,
        // WooCommerce may not have initialized customer/cart session objects yet, so guard these reads.
        $country_code = '';
        // On order-pay, prefer the billing country stored in the order.
        if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
            global $wp;
            $pay_order_id = isset( $wp->query_vars['order-pay'] ) ? absint( $wp->query_vars['order-pay'] ) : 0;
            $pay_order    = $pay_order_id ? wc_get_order( $pay_order_id ) : null;
            if ( $pay_order instanceof WC_Order ) {
                $country_code = $pay_order->get_billing_country() ? $pay_order->get_billing_country() : $pay_order->get_shipping_country();
            }
        }
        if ( empty( $country_code ) && function_exists( 'WC' ) && WC() && WC()->customer ) {
            $country_code = WC()->customer->get_billing_country() ? WC()->customer->get_billing_country() : WC()->customer->get_shipping_country();
        }
        if ( empty( $country_code ) ) {
            $default_country = (string) get_option( 'woocommerce_default_country', '' );
            $country_prefix  = strstr( $default_country, ':', true );
            $country_code    = ( false !== $country_prefix ) ? $country_prefix : $default_country;
        }

        $total_price = 0.0;
        // On the order-pay endpoint the cart may be empty; use the order total instead.
        if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
            global $wp;
            $order_id    = isset( $wp->query_vars['order-pay'] ) ? absint( $wp->query_vars['order-pay'] ) : 0;
            $order       = $order_id ? wc_get_order( $order_id ) : null;
            $total_price = $order instanceof WC_Order ? (float) $order->get_total() : 0.0;
        }
        if ( empty( $total_price ) && function_exists( 'WC' ) && WC() && WC()->cart ) {
            $total_price = (float) WC()->cart->get_total( '' );
        }

        $common_data = array(
            'environment'         => $environment,
            'gateway_merchant_id' => $sdk_service->get_multisafepay_account_id(),
            'debug_mode'          => $debug_mode,
            'country_code'        => $country_code,
            'currency_code'       => $currency_code,
            'total_price'         => $total_price,
        );

        if ( 'apple_pay' === $payment_method ) {
            unset( $common_data['gateway_merchant_id'] );
        }

        return $common_data;
    }

    /**
     * Build the variables required to initialize the Google Pay direct transactions
     *
     * @param string $test_merchant_id
     * @param string $test_merchant_name
     * @return string|null
     */
    public function build_googlepay_wallet_variables( string $test_merchant_id, string $test_merchant_name ): ?string {
        $google_pay = $this->common_wallets_data( 'google_pay' );
        if ( is_null( $google_pay ) ) {
            return null;
        }
        $merchant_id   = $test_merchant_id;
        $merchant_name = $test_merchant_name;

        if ( 'LIVE' === $google_pay['environment'] ) {
            $merchant_id   = $this->merchant_id;
            $merchant_name = $this->merchant_name;
        }

        return 'var configGooglePay = ' . wp_json_encode(
                array(
                    'environment'       => $google_pay['environment'],
                    'gatewayMerchantId' => $google_pay['gateway_merchant_id'],
                    'debugMode'         => $google_pay['debug_mode'],
                    'countryCode'       => $google_pay['country_code'],
                    'currencyCode'      => $google_pay['currency_code'],
                    'merchantId'        => $merchant_id,
                    'merchantName'      => $merchant_name,
                    'totalPrice'        => $google_pay['total_price'],
                )
        ) . ';';
    }

    /**
     * Returns Google Pay configuration for frontend usage (including Blocks).
     *
     * @return array|null
     */
    public function get_googlepay_wallet_config(): ?array {
        $google_pay = $this->common_wallets_data( 'google_pay' );
        if ( is_null( $google_pay ) ) {
            return null;
        }

        $merchant_id   = self::GOOGLEPAY_TEST_MERCHANT_ID;
        $merchant_name = self::GOOGLEPAY_TEST_MERCHANT_NAME;

        if ( 'LIVE' === $google_pay['environment'] ) {
            $merchant_id   = (string) $this->merchant_id;
            $merchant_name = (string) $this->merchant_name;
        }

        return array(
            'environment'       => $google_pay['environment'],
            'gatewayMerchantId' => $google_pay['gateway_merchant_id'],
            'debugMode'         => (bool) $google_pay['debug_mode'],
            'countryCode'       => (string) $google_pay['country_code'],
            'currencyCode'      => (string) $google_pay['currency_code'],
            'merchantId'        => (string) $merchant_id,
            'merchantName'      => (string) $merchant_name,
            'totalPrice'        => (float) $google_pay['total_price'],
        );
    }

    /**
     * Build the variables required to initialize the Apple Pay direct transactions
     *
     * @param string $test_merchant_name
     * @return string|null
     */
    public function build_applepay_wallet_variables( string $test_merchant_name ): ?string {
        $apple_pay = $this->common_wallets_data( 'apple_pay' );
        if ( is_null( $apple_pay ) ) {
            return null;
        }
        $merchant_name = $test_merchant_name;

        if ( 'LIVE' === $apple_pay['environment'] ) {
            $merchant_name = $this->merchant_name;
        }

        return 'var configApplePay = ' . wp_json_encode(
                array(
                    'debugMode'    => $apple_pay['debug_mode'],
                    'countryCode'  => $apple_pay['country_code'],
                    'currencyCode' => $apple_pay['currency_code'],
                    'merchantName' => $merchant_name,
                    'totalPrice'   => $apple_pay['total_price'],
                )
            ) . ';';
    }

    /**
     * Returns Apple Pay configuration for frontend usage (including Blocks).
     *
     * @return array|null
     */
    public function get_applepay_wallet_config(): ?array {
        $apple_pay = $this->common_wallets_data( 'apple_pay' );
        if ( is_null( $apple_pay ) ) {
            return null;
        }

        $merchant_name = self::APPLEPAY_TEST_MERCHANT_NAME;

        if ( 'LIVE' === $apple_pay['environment'] ) {
            $merchant_name = (string) $this->merchant_name;
        }

        return array(
            'environment'  => $apple_pay['environment'],
            'debugMode'    => (bool) $apple_pay['debug_mode'],
            'countryCode'  => (string) $apple_pay['country_code'],
            'currencyCode' => (string) $apple_pay['currency_code'],
            'merchantName' => (string) $merchant_name,
            'totalPrice'   => (float) $apple_pay['total_price'],
        );
    }

    /**
     * Get the countries allowed by WooCommerce
     *
     * @return array
     */
    protected function get_countries(): array {
        $countries = new WC_Countries();
        return $countries->get_allowed_countries();
    }

    /**
     * Get the user roles allowed by WordPress
     *
     * @return array
     */
    protected function get_user_roles(): array {
        $roles = wp_roles()->roles;

        return array_map(
            static function ( $role ) {
                return $role['name'];
            },
            $roles
        );
    }

    /**
     * If the API starts returning that the payment component has no fields for IDEAL,
     * it's because the payment component is disabled for this payment method.
     *
     * @return bool
     */
    private function is_ideal_2_0(): bool {
        if ( $this->payment_method->getId() !== 'IDEAL' ) {
            return false;
        }

        $payment_method_apps = $this->payment_method->getApps();
        if ( false === $payment_method_apps[ PaymentMethod::PAYMENT_COMPONENT_KEY ][ PaymentMethod::PAYMENT_COMPONENT_HAS_FIELDS_KEY ] ) {
            return true;
        }

        return false;
    }
}
