<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethodBlocks;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethodsController;
use MultiSafepay\WooCommerce\Settings\SettingsController;
use MultiSafepay\WooCommerce\Settings\ThirdPartyCompatibility;
use MultiSafepay\WooCommerce\Utils\CustomLinks;
use MultiSafepay\WooCommerce\Utils\Internationalization;
use MultiSafepay\WooCommerce\Utils\Loader;
use MultiSafepay\WooCommerce\Services\PaymentComponentService;

/**
 * This class is the core of the plugin.
 * Is used to define internationalization, admin and front hooks.
 */
class Main {

    /**
     * The loader that's responsible for maintaining and registering all hooks
     *
     * @var Loader Maintains and registers all hooks for the plugin.
     */
    public $loader;

    /**
     * Define the core functionality of the plugin.
     *
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public face of the site.
     */
    public function __construct() {
        $this->loader = new Loader();
        $this->set_locale();
        $this->add_custom_links_in_plugin_list();
        $this->define_settings_hooks();
        $this->define_payment_methods_hooks();
        $this->define_compatibilities();
    }

    /**
     * Register the MultiSafepay payment methods in WooCommerce Blocks.
     *
     * @return void
     */
    public static function register_multisafepay_payment_methods_blocks(): void {
        if ( class_exists( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry::class ) ) {
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function ( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                    $payment_method_registry->register( new BasePaymentMethodBlocks() );
                }
            );
        }
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Internationalization class in order to set the domain and register the hook
     * with WordPress.
     *
     * @return void
     */
    private function set_locale() {
        $plugin_i18n = new Internationalization();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }


    /**
     * Define the custom links in the plugin list
     *
     * @see https://developer.wordpress.org/reference/hooks/plugin_action_links_plugin_file/
     *
     * @return  void
     */
    private function add_custom_links_in_plugin_list(): void {
        $custom_links = new CustomLinks();
        $this->loader->add_filter( 'plugin_action_links_multisafepay/multisafepay.php', $custom_links, 'get_links' );
    }

    /**
     * Define compatibilities with third party plugins
     *
     * @return void
     */
    private function define_compatibilities(): void {
        $compatibilities = new ThirdPartyCompatibility();
        $this->loader->add_action( 'before_woocommerce_init', $compatibilities, 'declare_all_compatibilities' );
    }

    /**
     * Register all of the hooks related to the common settings
     * of the plugin.
     *
     * @return void
     */
    private function define_settings_hooks(): void {
        // Settings controller
        $plugin_settings = new SettingsController();

        // Filter get_option for some option names.
        $this->loader->add_filter( 'option_multisafepay_testmode', $plugin_settings, 'filter_multisafepay_settings_as_booleans' );
        $this->loader->add_filter( 'option_multisafepay_debugmode', $plugin_settings, 'filter_multisafepay_settings_as_booleans' );
        $this->loader->add_filter( 'option_multisafepay_second_chance', $plugin_settings, 'filter_multisafepay_settings_as_booleans' );
        $this->loader->add_filter( 'option_multisafepay_final_order_status', $plugin_settings, 'filter_multisafepay_settings_as_booleans' );
        $this->loader->add_filter( 'option_multisafepay_disable_shopping_cart', $plugin_settings, 'filter_multisafepay_settings_as_booleans' );
        $this->loader->add_filter( 'option_multisafepay_time_active', $plugin_settings, 'filter_multisafepay_settings_as_int' );

        if ( is_admin() ) {
            // Enqueue styles and JavaScript file in controller settings page
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_settings, 'enqueue_styles_and_scripts', 1 );
            // Add menu page for common settings page
            $this->loader->add_action( 'admin_menu', $plugin_settings, 'register_common_settings_page', 60 );
            // Add the new settings page the WooCommerce screen options
            $this->loader->add_filter( 'woocommerce_screen_ids', $plugin_settings, 'set_wc_screen_options_in_common_settings_page' );
            // Register settings
            $this->loader->add_action( 'admin_init', $plugin_settings, 'register_common_settings' );
            // Filter and return ordered the results of the fields
            $this->loader->add_filter( 'multisafepay_common_settings_fields', $plugin_settings, 'filter_multisafepay_common_settings_fields', 10, 1 );
        }
    }

    /**
     * Register all of the hooks related to the payment methods
     * of the plugin.
     *
     * @return void
     */
    private function define_payment_methods_hooks(): void {
        // Payment controller
        $payment_methods = new PaymentMethodsController();
        // Enqueue styles in payment methods
        $this->loader->add_action( 'wp_enqueue_scripts', $payment_methods, 'enqueue_styles' );
        // Register the MultiSafepay payment gateways in WooCommerce.
        $this->loader->add_filter( 'woocommerce_payment_gateways', $payment_methods, 'get_woocommerce_payment_gateways' );
        // Filter transaction order id on callback
        $this->loader->add_filter( 'multisafepay_transaction_order_id', $payment_methods, 'multisafepay_transaction_order_id', 11 );
        // Filter per country
        $this->loader->add_filter( 'woocommerce_available_payment_gateways', $payment_methods, 'filter_gateway_per_country', 11 );
        // Filter per min amount
        $this->loader->add_filter( 'woocommerce_available_payment_gateways', $payment_methods, 'filter_gateway_per_min_amount', 12 );
        // Filter per user role
        $this->loader->add_filter( 'woocommerce_available_payment_gateways', $payment_methods, 'filter_gateway_per_user_roles', 13 );
        // Set MultiSafepay transaction as shipped
        $this->loader->add_action( 'woocommerce_order_status_' . str_replace( 'wc-', '', get_option( 'multisafepay_trigger_transaction_to_shipped', 'wc-completed' ) ), $payment_methods, 'set_multisafepay_transaction_as_shipped', 10, 1 );
        // Set MultiSafepay transaction as invoiced
        $this->loader->add_action( 'woocommerce_order_status_' . str_replace( 'wc-', '', get_option( 'multisafepay_trigger_transaction_to_invoiced', 'wc-completed' ) ), $payment_methods, 'set_multisafepay_transaction_as_invoiced', 11, 1 );
        // Generate orders from backend.
        if ( is_admin() ) {
            $this->loader->add_action( 'woocommerce_new_order', $payment_methods, 'generate_orders_from_backend', 10, 1 );
        }
        // Replace checkout payment url if a payment link has been generated in backoffice
        $this->loader->add_filter( 'woocommerce_get_checkout_payment_url', $payment_methods, 'replace_checkout_payment_url', 10, 2 );
        // One notification URL for all payment methods
        $this->loader->add_action( 'woocommerce_api_multisafepay', $payment_methods, 'callback' );
        // One endpoint to handle notifications via POST.
        $this->loader->add_action( 'rest_api_init', $payment_methods, 'multisafepay_register_rest_route' );
        // Allow cancel orders for on-hold status
        $this->loader->add_filter( 'woocommerce_valid_order_statuses_for_cancel', $payment_methods, 'allow_cancel_multisafepay_orders_with_on_hold_status', 10, 2 );
        // Allow to refresh the data sent to initialize the Payment Components, when in the checkout, something changed in the order details
        $payment_component_service = new PaymentComponentService();
        $this->loader->add_action( 'wp_ajax_get_payment_component_arguments', $payment_component_service, 'ajax_get_payment_component_arguments' );
        $this->loader->add_action( 'wp_ajax_nopriv_get_payment_component_arguments', $payment_component_service, 'ajax_get_payment_component_arguments' );
        // Ajax related to Apple Pay Direct validation
        $this->loader->add_action( 'wp_ajax_applepay_direct_validation', $payment_methods, 'applepay_direct_validation' );
        $this->loader->add_action( 'wp_ajax_nopriv_applepay_direct_validation', $payment_methods, 'applepay_direct_validation' );
        // Getting total price update for payment methods
        $this->loader->add_action( 'wp_ajax_get_updated_total_price', $payment_methods, 'get_updated_total_price' );
        $this->loader->add_action( 'wp_ajax_nopriv_get_updated_total_price', $payment_methods, 'get_updated_total_price' );
        // Add the MultiSafepay transaction link in the order details page
        $this->loader->add_action( 'woocommerce_admin_order_data_after_payment_info', $payment_methods, 'add_multisafepay_transaction_link' );
        // Register the MultiSafepay payment methods in WooCommerce Blocks.
        add_action( 'woocommerce_blocks_loaded', array( $this, 'register_multisafepay_payment_methods_blocks' ) );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @return void
     */
    public function init() {
        $this->loader->init();
    }
}
