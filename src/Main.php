<?php declare(strict_types=1);

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MultiSafepay\WooCommerce;

use MultiSafepay\WooCommerce\PaymentMethods\Gateways;
use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethodsController;
use MultiSafepay\WooCommerce\Settings\SettingsController;
use MultiSafepay\WooCommerce\Utils\CustomLinks;
use MultiSafepay\WooCommerce\Utils\Internationalization;
use MultiSafepay\WooCommerce\PaymentMethods\TokenizationMethodsController;
use MultiSafepay\WooCommerce\Utils\Loader;

/**
 * This class is the core of the plugin.
 *
 * Is used to define internationalization, settings hooks, and
 * public face site hooks.
 *
 * @since      4.0.0
 */
class Main {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var      Loader     Maintains and registers all hooks for the plugin.
	 */
	private $loader;

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
        if ( (bool) get_option( 'multisafepay_tokenization', false ) ) {
            $this->define_tokenization_hooks();
        }
	}

    /**
     * Register all of the hooks related to the tokenization methods
     * of the plugin.
     *
     * @return  void
     */
    private function define_tokenization_hooks(): void {
        // Tokenization controller
        $tokenization_methods = new TokenizationMethodsController();
        // Add tokens to user account
        $this->loader->add_filter( 'woocommerce_get_customer_payment_tokens', $tokenization_methods, 'multisafepay_get_customer_payment_tokens', 10, 3 );
        // Delete token action
        $this->loader->add_action( 'woocommerce_payment_token_deleted', $tokenization_methods, 'woocommerce_payment_token_deleted', 10, 2 );
        // Set token as default
        $this->loader->add_action( 'woocommerce_payment_token_set_default', $tokenization_methods, 'woocommerce_payment_token_set_default', 10, 2 );
        // Customize save payment method checkbox
        $this->loader->add_filter( 'woocommerce_payment_gateway_save_new_payment_method_option_html', $tokenization_methods, 'multisafepay_payment_gateway_save_new_payment_method_option_html', 10, 1 );

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
        $this->loader->add_filter( 'option_multisafepay_remove_all_settings', $plugin_settings, 'filter_multisafepay_settings_as_booleans' );
        $this->loader->add_filter( 'option_multisafepay_time_active', $plugin_settings, 'filter_multisafepay_settings_as_int' );

        if ( is_admin() ) {
            // Enqueue styles in controller settings page
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_settings, 'enqueue_styles', 1 );
            // Enqueue scripts in controller settings page
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_settings, 'enqueue_scripts' );
            // Add menu page for common settings page
            $this->loader->add_action( 'admin_menu', $plugin_settings, 'register_common_settings_page', 60 );
            // Add the new settings page the WooCommerce screen options
            $this->loader->add_filter( 'woocommerce_screen_ids', $plugin_settings, 'set_wc_screen_options_in_common_settings_page' );
            // Register settings
            $this->loader->add_action( 'admin_init', $plugin_settings, 'register_common_settings' );
            // Intervene woocommerce_toggle_gateway_enabled admin_ajax call and validate if required settings has been setup
            $this->loader->add_action( 'wp_ajax_woocommerce_multisafepay_toggle_gateway_enabled', $plugin_settings, 'multisafepay_ajax_toggle_gateway_enabled' );
            $this->loader->add_action( 'wp_ajax_woocommerce_toggle_gateway_enabled', $plugin_settings, 'before_ajax_toggle_gateway_enabled' );
            // Filter and return ordered the results of the fields
            $this->loader->add_filter( 'multisafepay_common_settings_fields', $plugin_settings, 'filter_multisafepay_common_settings_fields', 10, 1 );
        }
	}

	/**
	 * Register all of the hooks related to the payment methods
	 * of the plugin.
     *
     * @return  void
	 */
	private function define_payment_methods_hooks(): void {
        // Payment controller
		$payment_methods = new PaymentMethodsController();
        // Enqueue styles in payment methods
		$this->loader->add_action( 'wp_enqueue_scripts', $payment_methods, 'enqueue_styles' );
        // Register the MultiSafepay payment gateways in WooCommerce.
        $this->loader->add_filter( 'woocommerce_payment_gateways', $payment_methods, 'get_gateways' );
        // Filter transaction order id on callback
        $this->loader->add_filter( 'multisafepay_transaction_order_id', $payment_methods, 'multisafepay_transaction_order_id', 11 );
        // Filter per country
        $this->loader->add_filter( 'woocommerce_available_payment_gateways', $payment_methods, 'filter_gateway_per_country', 11 );
        // Filter per min amount
        $this->loader->add_filter( 'woocommerce_available_payment_gateways', $payment_methods, 'filter_gateway_per_min_amount', 12 );
        // Set MultiSafepay transaction as shipped
        $this->loader->add_action( 'woocommerce_order_status_' . str_replace( 'wc-', '', get_option( 'multisafepay_trigger_transaction_to_shipped', 'wc-completed' ) ), $payment_methods, 'set_multisafepay_transaction_as_shipped', 10, 1 );
        // Set MultiSafepay transaction as invoiced
        $this->loader->add_action( 'woocommerce_order_status_' . str_replace( 'wc-', '', get_option( 'multisafepay_trigger_transaction_to_invoiced', 'wc-completed' ) ), $payment_methods, 'set_multisafepay_transaction_as_invoiced', 11, 1 );
        // Generate orders from backend.
        if ( is_admin() ) {
            $this->loader->add_action( 'woocommerce_new_order', $payment_methods, 'generate_orders_from_backend', 10, 2 );
        }
        // Replace checkout payment url if a payment link has been generated in backoffice
        $this->loader->add_filter( 'woocommerce_get_checkout_payment_url', $payment_methods, 'replace_checkout_payment_url', 10, 2 );
        // Register deprecated notification endpoint
        $this->loader->add_action( 'wp_loaded', $payment_methods, 'deprecated_callback' );
        // Register notification endpoint for each payment method
        foreach ( Gateways::get_gateways_ids() as $gateway_id ) {
            $this->loader->add_action( 'woocommerce_api_' . $gateway_id, $payment_methods, 'callback' );
        }
        // One new notification URL for all payment methods
        $this->loader->add_action( 'woocommerce_api_multisafepay', $payment_methods, 'callback' );
        // Allow cancel orders for on-hold status
        $this->loader->add_filter( 'woocommerce_valid_order_statuses_for_cancel', $payment_methods, 'allow_cancel_multisafepay_orders_with_on_hold_status', 10, 2 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
     *
     * @return  void
	 */
	public function init() {
		$this->loader->init();
	}

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader(): Loader {
        return $this->loader;
    }

}
