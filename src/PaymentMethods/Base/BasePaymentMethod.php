<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use Exception;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\WooCommerce\Services\OrderService;
use MultiSafepay\WooCommerce\Services\PaymentComponentService;
use MultiSafepay\WooCommerce\Services\PaymentMethodService;
use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\WooCommerce\Utils\Logger;
use Psr\Http\Client\ClientExceptionInterface;
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

    public const MULTISAFEPAY_COMPONENT_JS_URL  = 'https://pay.multisafepay.com/sdk/components/v2/components.js';
    public const MULTISAFEPAY_COMPONENT_CSS_URL = 'https://pay.multisafepay.com/sdk/components/v2/components.css';

    public const NOT_ALLOW_REFUND_ORDER_STATUSES = array(
        'pending',
        'on-hold',
        'failed',
    );

    /**
     * A PaymentMethod object with the information of the payment method object
     *
     * @var PaymentMethod
     */
    protected $payment_method;

    /**
     * What type of transaction, should be 'direct' or 'redirect'
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
     * If supports payment component
     *
     * @var bool
     */
    public $payment_component = false;

    /**
     * BasePaymentMethod constructor.
     *
     * @param PaymentMethod $payment_method
     */
    public function __construct( PaymentMethod $payment_method ) {
        $this->payment_method = $payment_method;
        $this->supports       = array( 'products', 'refunds' );
        $this->id             = $this->get_payment_method_id();

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
        $this->countries            = $this->get_option( 'countries' );
        $this->initial_order_status = $this->get_option( 'initial_order_status', false );
        $this->payment_component    = $this->is_payment_component_enabled();
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
     * @return string
     */
    public function get_payment_method_type(): string {
        if ( $this->is_payment_component_enabled() ) {
            return self::TRANSACTION_TYPE_DIRECT;
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
     * Return if tokenization card on file is enabled.
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
     * Return if payment component is enabled.
     *
     * @return bool
     */
    public function is_payment_component_enabled(): bool {
        if ( ! $this->payment_method->supportsPaymentComponent() ) {
            return false;
        }
        $settings = get_option( 'woocommerce_' . $this->id . '_settings', array( 'payment_component' => 'yes' ) );
        if ( ! isset( $settings['payment_component'] ) ) {
            return true;
        }
        return 'yes' === $settings['payment_component'];
    }

    /**
     * Enqueue Javascript related with Payment Component.
     *
     * @return void
     */
    public function enqueue_payment_component_scripts() {
        if ( is_checkout() || is_wc_endpoint_url( 'order-pay' ) ) {

            wp_enqueue_script( 'multisafepay-payment-component-script', self::MULTISAFEPAY_COMPONENT_JS_URL, array(), MULTISAFEPAY_PLUGIN_VERSION, true );

            $multisafepay_payment_component_config = ( new PaymentComponentService() )->get_payment_component_arguments( $this );
            $gateways_with_payment_component       = ( new PaymentMethodService() )->get_woocommerce_payment_gateway_ids_with_payment_component_support();

            $route = MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-payment-component.js';
            wp_enqueue_script( 'multisafepay-payment-component-js', $route, array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
            wp_localize_script( 'multisafepay-payment-component-js', 'payment_component_config_' . $this->id, $multisafepay_payment_component_config );
            wp_localize_script( 'multisafepay-payment-component-js', 'multisafepay_payment_component_gateways', $gateways_with_payment_component );
            wp_enqueue_script( 'multisafepay-payment-component-js' );

        }
    }

    /**
     * Check if woocommerce checkout block is active.
     *
     * @return bool
     */
    private function is_woocommerce_checkout_block_active(): bool {
        global $post;

        if ( $post && has_blocks( $post->post_content ) ) {
            $blocks = parse_blocks( $post->post_content );
            foreach ( $blocks as $block ) {
                if ( 'woocommerce/checkout' === $block['blockName'] ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Enqueue Javascript related with a MultiSafepay Payment Method.
     *
     * @return void
     */
    public function enqueue_multisafepay_scripts_by_gateway_code() {
        if ( is_checkout() || is_wc_endpoint_url( 'order-pay' ) ) {

            if ( 'APPLEPAY' === $this->get_payment_method_gateway_code() && ! $this->is_woocommerce_checkout_block_active() ) {
                wp_enqueue_script( 'multisafepay-apple-pay-js', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-apple-pay.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
            }

            if ( 'GOOGLEPAY' === $this->get_payment_method_gateway_code() ) {
                wp_enqueue_script( 'multisafepay-google-pay-js', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-google-pay.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
                wp_enqueue_script( 'google-pay-js', 'https://pay.google.com/gp/p/js/pay.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
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
                'default'  => $this->payment_method->getMinAmount(),
                'value'    => (float) $this->get_option( 'min_amount', $this->payment_method->getMinAmount() ),
            ),
            'max_amount'           => array(
                'title'    => __( 'Max Amount', 'multisafepay' ),
                'type'     => 'decimal',
                'desc_tip' => __( 'This payment method is not shown in the checkout if the order total exceeds a certain amount. Leave blank for no restrictions.', 'multisafepay' ),
                'default'  => $this->payment_method->getMaxAmount(),
                'value'    => (float) $this->get_option( 'max_amount', $this->payment_method->getMaxAmount() ),
            ),
            'countries'            => array(
                'title'       => __( 'Country', 'multisafepay' ),
                'type'        => 'multiselect',
                'description' => __( 'If you select one or more countries, this payment method will be shown in the checkout page, if the payment address`s country of the customer match with the selected values. Leave blank for no restrictions.', 'multisafepay' ),
                'desc_tip'    => __( 'For most operating system and configurations, you must hold Ctrl or Cmd in your keyboard, while you click in the options to select more than one value.', 'multisafepay' ),
                'options'     => $this->get_countries(),
                'default'     => $this->get_option( 'countries', array() ),
            ),
        );

        if ( $this->payment_method->supportsPaymentComponent() ) {
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
        }

        return $form_fields;
    }

    /**
     * Enqueue CSS styles related with Payment Component.
     *
     * @return void
     */
    public function enqueue_payment_component_styles() {
        if ( ( is_checkout() || is_wc_endpoint_url( 'order-pay' ) ) ) {
            wp_enqueue_style(
                'multisafepay-payment-component-style',
                self::MULTISAFEPAY_COMPONENT_CSS_URL,
                array(),
                MULTISAFEPAY_PLUGIN_VERSION,
                'all'
            );
        }
    }

    /**
     * Prints checkout custom fields
     *
     * @return mixed
     */
    public function payment_fields() {
        require MULTISAFEPAY_PLUGIN_DIR_PATH . 'templates/multisafepay-checkout-fields-display.php';
    }

    /**
     * @param WC_Order $order
     * @return bool
     */
    public function can_refund_order( $order ) {
        if ( in_array( $order->get_status(), self::NOT_ALLOW_REFUND_ORDER_STATUSES, true ) ) {
            return false;
        }

        return $order && $this->supports( 'refunds' );
    }

    /**
     * @param int|string $order_id
     * @return array|void
     */
    public function process_payment( $order_id ) {
        $sdk                 = new SdkService();
        $transaction_manager = $sdk->get_transaction_manager();
        $order_service       = new OrderService();

        $order         = wc_get_order( $order_id );
        $order_request = $order_service->create_order_request( $order, $this->gateway_code, $this->type );

        try {
            $transaction = $transaction_manager->create( $order_request );
        } catch ( ApiException | ClientExceptionInterface $exception ) {
            Logger::log_error( $exception->getMessage() );
            wc_add_notice( __( 'There was a problem processing your payment. Please try again later or contact with us.', 'multisafepay' ), 'error' );
            return;
        }

        if ( get_option( 'multisafepay_debugmode', false ) ) {
            Logger::log_info( 'Start MultiSafepay transaction for the order ID ' . $order_id . ' on ' . date( 'd/m/Y H:i:s' ) . ' with payment URL ' . $transaction->getPaymentUrl() );
        }

        return array(
            'result'   => 'success',
            'redirect' => esc_url_raw( $transaction->getPaymentUrl() ),
        );
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

        if (
            $this->is_payment_component_enabled() &&
            (
                ! isset( $_POST[ $this->id . '_payment_component_payload' ] ) ||
                empty( $_POST[ $this->id . '_payment_component_payload' ] )
            )
        ) {
            wc_add_notice( '<strong>' . $this->get_payment_method_title() . ' payment details</strong>  is a required field.', 'error' );
        }

        if ( isset( $_POST[ $this->id . '_payment_component_errors' ] ) && '' !== $_POST[ $this->id . '_payment_component_errors' ] ) {
            foreach ( $_POST[ $this->id . '_payment_component_errors' ] as $payment_component_error ) {
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
     * Return an array of allowed countries defined in WooCommerce Settings.
     *
     * @return array
     */
    protected function get_countries(): array {
        $countries = new WC_Countries();
        return $countries->get_allowed_countries();
    }
}
