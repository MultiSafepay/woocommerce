<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\ValueObject\IbanNumber;
use MultiSafepay\WooCommerce\PaymentMethods\Gateways;
use MultiSafepay\WooCommerce\Services\CustomerService;
use MultiSafepay\WooCommerce\Services\OrderService;
use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\WooCommerce\Utils\Logger;
use WC_Countries;
use WC_Payment_Gateway;
use WP_Error;

abstract class BasePaymentMethod extends WC_Payment_Gateway implements PaymentMethodInterface {

    use BaseRefunds;

    const MULTISAFEPAY_COMPONENT_JS_URL   = 'https://pay.multisafepay.com/sdk/components/v2/components.js';
    const MULTISAFEPAY_COMPONENT_CSS_URL  = 'https://pay.multisafepay.com/sdk/components/v2/components.css';
    const NOT_ALLOW_REFUND_ORDER_STATUSES = array(
        'pending',
        'on-hold',
        'failed',
    );

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
     * An array with the keys of the required custom fields
     *
     * @var array
     */
    protected $checkout_fields_ids;

    /**
     * The minimun amount for the payment method
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
     * @var string
     */
    public $payment_component = false;

    /**
     * Defines if the payment method is tokenizable
     *
     * @var bool
     */
    protected $has_configurable_tokenization = false;

    /**
     * Defines if the payment method will use the Payment Component
     *
     * @var bool
     */
    protected $has_configurable_payment_component = false;

    /**
     * Construct for Core class.
     */
    public function __construct() {
        $this->supports = array( 'products', 'refunds' );
        $this->id       = $this->get_payment_method_id();
        if ( $this->is_payment_component_enable() ) {
            $this->supports[] = 'multisafepay_payment_component';
            if ( $this->is_tokenization_enable() ) {
                $this->supports[] = 'multisafepay_tokenization';
            }
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_payment_component_styles' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_payment_component_scripts' ) );
        }

        $this->type                = $this->get_payment_method_type();
        $this->method_title        = $this->get_payment_method_title();
        $this->method_description  = $this->get_payment_method_description();
        $this->gateway_code        = $this->get_payment_method_code();
        $this->has_fields          = $this->has_fields();
        $this->checkout_fields_ids = $this->get_checkout_fields_ids();
        $this->icon                = $this->get_logo();
        $this->form_fields         = $this->add_form_fields();
        $this->init_form_fields();
        $this->init_settings();

        $this->enabled              = $this->get_option( 'enabled', 'no' );
        $this->title                = $this->get_option( 'title', $this->get_method_title() );
        $this->description          = $this->get_option( 'description' );
        $this->max_amount           = $this->get_option( 'max_amount' );
        $this->min_amount           = $this->get_option( 'min_amount' );
        $this->countries            = $this->get_option( 'countries' );
        $this->initial_order_status = $this->get_option( 'initial_order_status', false );
        $this->payment_component    = $this->get_option( 'payment_component', false );
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
     * Return the full path of the (locale) logo
     *
     * @return string
     */
    private function get_logo(): string {
        $language = substr( ( new CustomerService() )->get_locale(), 0, 2 );

        $icon = $this->get_payment_method_icon();

        $icon_locale = substr_replace( $icon, "-$language", - 4, - 4 );
        if ( file_exists( MULTISAFEPAY_PLUGIN_DIR_PATH . 'assets/public/img/' . $icon_locale ) ) {
            $icon = $icon_locale;
        }

        return esc_url( MULTISAFEPAY_PLUGIN_URL . '/assets/public/img/' . $icon );
    }

    /**
     * Return an array of allowed countries defined in WooCommerce Settings.
     *
     * @return array
     */
    private function get_countries(): array {
        $countries = new WC_Countries();
        return $countries->get_allowed_countries();
    }

    /**
     * Return if payment methods requires custom checkout fields
     *
     * @return boolean
     */
    public function has_fields(): bool {
        if ( $this->is_payment_component_enable() ) {
            return true;
        }
        return false;
    }

    /**
     * Return the custom checkout fields id`s
     *
     * @return array
     */
    public function get_checkout_fields_ids(): array {
        return array();
    }

    /**
     * Return the gateway info
     *
     * @param array|null $data
     *
     * @return GatewayInfoInterface
     */
    public function get_gateway_info( array $data = null ): GatewayInfoInterface {
        return new BaseGatewayInfo();
    }

    /**
     * Define the form option - settings fields.
     *
     * @return  array
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
                'default'  => $this->get_option( 'min_amount', '' ),
            ),
            'max_amount'           => array(
                'title'    => __( 'Max Amount', 'multisafepay' ),
                'type'     => 'decimal',
                'desc_tip' => __( 'This payment method is not shown in the checkout if the order total exceeds a certain amount. Leave blank for no restrictions.', 'multisafepay' ),
                'default'  => $this->get_option( 'max_amount', '' ),
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

        if ( $this->has_configurable_payment_component ) {
            $form_fields['payment_component'] = array(
                'title'       => __( 'Payment Components', 'multisafepay' ),
                'label'       => 'Enable Payment Component in ' . $this->get_method_title() . ' Gateway',
                'type'        => 'checkbox',
                'description' => __( 'More information about Payment Components on <a href="https://docs.multisafepay.com/payment-components/" target="_blank">MultiSafepay\'s Documentation Center</a>.', 'multisafepay' ),
                'default'     => 'no',
            );
        }

        if ( $this->has_configurable_tokenization && $this->is_payment_component_enable() ) {
            $form_fields['tokenization'] = array(
                'title'       => __( 'Tokenization', 'multisafepay' ),
                'label'       => 'Enable Tokenization in ' . $this->get_method_title() . ' Gateway within the Payment Component',
                'type'        => 'checkbox',
                'description' => __( 'Tokenization only applies when payment component is enabled. More information about Tokenization on <a href="https://docs.multisafepay.com/features/recurring-payments/" target="_blank">MultiSafepay\'s Documentation Center</a>.', 'multisafepay' ),
                'default'     => get_option( 'multisafepay_tokenization', 'no' ),
            );
        }

        return $form_fields;
    }

    /**
     * Process the payment and return the result.
     *
     * @param integer $order_id Order ID.
     *
     * @return  array|mixed|void
     */
    public function process_payment( $order_id ) {
        $sdk                 = new SdkService();
        $transaction_manager = $sdk->get_transaction_manager();
        $order_service       = new OrderService();

        $gateway_info = $this->get_gateway_info( array( 'order_id' => $order_id ) );
        if ( ! $this->validate_gateway_info( $gateway_info ) ) {
            $gateway_info = null;
        }

        $order         = wc_get_order( $order_id );
        $order_request = $order_service->create_order_request( $order, $this->gateway_code, $this->type, $gateway_info );

        try {
            $transaction = $transaction_manager->create( $order_request );
        } catch ( ApiException $api_exception ) {
            Logger::log_error( $api_exception->getMessage() );
            wc_add_notice( $api_exception->getMessage(), 'error' );
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
     * This validates that the API Key has been setup properly
     * check SDK, and check if the gateway is enable for the merchant.
     *
     * @param string $key
     * @param string $value
     *
     * @return  string
     */
    public function validate_enabled_field( $key, $value ) {
        if ( null === $value ) {
            return 'no';
        }
        $gateways           = ( new SdkService() )->get_gateways();
        $available_gateways = array();
        foreach ( $gateways as $gateway ) {
            $available_gateways[] = $gateway->getId();
        }
        if ( 'CREDITCARD' !== $this->gateway_code && ! in_array( $this->gateway_code, $available_gateways, true ) && ! empty( $this->gateway_code ) ) {
            $message = sprintf(
                /* translators: %1$: The payment method title */
                __( 'It seems %1$s is not available for your MultiSafepay account. <a href="%2$s">Contact support</a>', 'multisafepay' ),
                $this->get_payment_method_title(),
                admin_url( 'admin.php?page=multisafepay-settings&tab=support' )
            );
            $this->add_error( $message );
        }

        return 'yes';
    }

    /**
     * Prints checkout custom fields
     *
     * @return  mixed
     */
    public function payment_fields() {
        require MULTISAFEPAY_PLUGIN_DIR_PATH . 'templates/multisafepay-checkout-fields-display.php';
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

        if ( ( isset( $_POST[ $this->id . '_salutation' ] ) ) && '' === $_POST[ $this->id . '_salutation' ] ) {
            wc_add_notice( __( 'Salutation is a required field', 'multisafepay' ), 'error' );
        }

        if ( ( isset( $_POST[ $this->id . '_gender' ] ) ) && '' === $_POST[ $this->id . '_gender' ] ) {
            wc_add_notice( __( 'Gender is a required field', 'multisafepay' ), 'error' );
        }

        if ( isset( $_POST[ $this->id . '_birthday' ] ) && '' === $_POST[ $this->id . '_birthday' ] ) {
            wc_add_notice( __( 'Date of birth is a required field', 'multisafepay' ), 'error' );
        }

        if ( isset( $_POST[ $this->id . '_bank_account' ] ) && '' === $_POST[ $this->id . '_bank_account' ] ) {
            wc_add_notice( __( 'Bank Account is a required field', 'multisafepay' ), 'error' );
        }

        if ( isset( $_POST[ $this->id . '_bank_account' ] ) && '' !== $_POST[ $this->id . '_bank_account' ] ) {
            if ( ! $this->validate_iban( $_POST[ $this->id . '_bank_account' ] ) ) {
                wc_add_notice( __( 'IBAN does not seems valid', 'multisafepay' ), 'error' );
            }
        }

        if ( isset( $_POST[ $this->id . '_account_holder_name' ] ) && '' === $_POST[ $this->id . '_account_holder_name' ] ) {
            wc_add_notice( __( 'Account holder is a required field', 'multisafepay' ), 'error' );
        }

        if ( isset( $_POST[ $this->id . '_account_holder_iban' ] ) && '' === $_POST[ $this->id . '_account_holder_iban' ] ) {
            wc_add_notice( __( 'IBAN is a required field', 'multisafepay' ), 'error' );
        }

        if ( isset( $_POST[ $this->id . '_account_holder_iban' ] ) && '' !== $_POST[ $this->id . '_account_holder_iban' ] ) {
            if ( ! $this->validate_iban( $_POST[ $this->id . '_account_holder_iban' ] ) ) {
                wc_add_notice( __( 'IBAN does not seems valid', 'multisafepay' ), 'error' );
            }
        }

        if ( isset( $_POST[ $this->id . '_payment_component_errors' ] ) && '' !== $_POST[ $this->id . '_payment_component_errors' ] ) {
            foreach ( $_POST[ $this->id . '_payment_component_errors' ] as $payment_component_error ) {
                wc_add_notice( $payment_component_error, 'error' );
            }
        }

        if ( wc_get_notices( 'error' ) ) {
            return false;
        }

        return true;

    }

    /**
     * Returns bool after validates IBAN format
     *
     * @param string $iban
     *
     * @return  boolean
     */
    public function validate_iban( $iban ): bool {
        try {
            $iban = new IbanNumber( $iban );
            return true;
        } catch ( InvalidArgumentException $invalid_argument_exception ) {
            return false;
        }
    }

    /**
     * Returns the WooCommerce registered order statuses
     *
     * @see     http://hookr.io/functions/wc_get_order_statuses/
     *
     * @return  array
     */
    private function get_order_statuses(): array {
        $order_statuses               = wc_get_order_statuses();
        $order_statuses['wc-default'] = __( 'Default value set in common settings', 'multisafepay' );
        return $order_statuses;
    }

    /**
     * Validate the gatewayinfo, return true if validation is successful
     *
     * @param GatewayInfoInterface $gateway_info
     *
     * @return boolean
     */
    public function validate_gateway_info( GatewayInfoInterface $gateway_info ): bool {
        return true;
    }

    /**
     * @param WC_Order $order
     *
     * @return bool
     */
    public function can_refund_order( $order ) {
        if ( in_array( $order->get_status(), self::NOT_ALLOW_REFUND_ORDER_STATUSES, true ) ) {
            return false;
        }

        return $order && $this->supports( 'refunds' );
    }

    /**
     * @param array|null $data
     *
     * @return GatewayInfoInterface
     */
    protected function get_gateway_info_meta( ?array $data = null ): GatewayInfoInterface {
        $gateway_info = new Meta();
        if ( isset( $_POST[ $this->id . '_gender' ] ) ) {
            $gateway_info->addGenderAsString( $_POST[ $this->id . '_gender' ] );
        }
        if ( isset( $_POST[ $this->id . '_salutation' ] ) ) {
            $gateway_info->addGenderAsString( $_POST[ $this->id . '_salutation' ] );
        }
        if ( isset( $_POST[ $this->id . '_birthday' ] ) ) {
            $gateway_info->addBirthdayAsString( $_POST[ $this->id . '_birthday' ] );
        }
        if ( isset( $_POST[ $this->id . '_bank_account' ] ) ) {
            $gateway_info->addBankAccountAsString( $_POST[ $this->id . '_bank_account' ] );
        }
        if ( isset( $data ) && ! empty( $data['order_id'] ) ) {
            $order = wc_get_order( $data['order_id'] );
            $gateway_info->addEmailAddressAsString( $order->get_billing_email() );
            $gateway_info->addPhoneAsString( $order->get_billing_phone() );
        }
        return $gateway_info;
    }

    /**
     * This method use get_option instead $this->get_option;
     * because in the place where is called, settings are not being initialized yet.
     *
     * @return bool
     */
    public function is_payment_component_enable(): bool {
        $settings = get_option( 'woocommerce_' . $this->id . '_settings', array( 'payment_component' => 'no' ) );
        if ( ! isset( $settings['payment_component'] ) ) {
            return false;
        }
        return 'yes' === $settings['payment_component'];
    }

    /**
     * This method use get_option instead $this->get_option;
     * because in the place where is called, settings are not being initialized yet.
     *
     * @return bool
     */
    public function is_tokenization_enable(): bool {
        $settings = get_option( 'woocommerce_' . $this->id . '_settings', array( 'tokenization' => 'no' ) );
        if ( ! isset( $settings['tokenization'] ) ) {
            return false;
        }
        return 'yes' === $settings['tokenization'];
    }


    /**
     * Enqueue CSS styles related with Payment Component.
     *
     * @return void
     */
    public function enqueue_payment_component_styles() {
        if ( ( is_checkout() || is_wc_endpoint_url( 'order-pay' ) ) && $this->supports( 'multisafepay_payment_component' ) ) {
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
     * Enqueue Javascript related with Payment Component.
     *
     * @return void
     */
    public function enqueue_payment_component_scripts() {
        if ( ( is_checkout() || is_wc_endpoint_url( 'order-pay' ) ) && $this->supports( 'multisafepay_payment_component' ) ) {

            wp_enqueue_script( 'multisafepay-payment-component-script', self::MULTISAFEPAY_COMPONENT_JS_URL, array(), MULTISAFEPAY_PLUGIN_VERSION, true );

            $multisafepay_payment_component_config = $this->get_credit_card_payment_component_arguments();
            $gateways_with_payment_component       = Gateways::get_gateways_with_payment_component();

            $route = MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-payment-component.js';
            wp_enqueue_script( 'multisafepay-payment-component-js', $route, array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
            wp_localize_script( 'multisafepay-payment-component-js', 'payment_component_config_' . $this->id, $multisafepay_payment_component_config );
            wp_localize_script( 'multisafepay-payment-component-js', 'multisafepay_payment_component_gateways', $gateways_with_payment_component );
            wp_enqueue_script( 'multisafepay-payment-component-js' );

        }
    }

    /**
     * Return the arguments required to initialize the payment component library
     *
     * @return array
     */
    private function get_credit_card_payment_component_arguments(): array {
        $sdk_service = new SdkService();
        return array(
			'debug'      => (bool) get_option( 'multisafepay_debugmode', false ),
			'env'        => $sdk_service->get_test_mode() ? 'test' : 'live',
			'api_token'  => $sdk_service->get_api_token(),
			'orderData'  => array(
				'currency'  => get_woocommerce_currency(),
				'amount'    => ( WC()->cart ) ? ( WC()->cart->total * 100 ) : null,
				'customer'  => array(
					'locale'    => ( new CustomerService() )->get_locale(),
					'country'   => ( WC()->customer )->get_billing_country(),
					'reference' => $this->is_tokenization_enable() ? get_current_user_id() : null,
				),
				'template'  => array(
					'settings' => array(
						'embed_mode' => true,
					),
				),
				'recurring' => array(
					'model' => $this->is_tokenization_enable() ? 'cardOnFile' : null,
				),
			),
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'credit_card_payment_component_arguments_nonce' ),
			'gateway_id' => $this->id,
			'gateway'    => $this->get_payment_method_code(),
        );
    }

}
