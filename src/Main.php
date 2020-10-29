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
 *
 */

namespace MultiSafepay\WooCommerce;

use MultiSafepay\WooCommerce\PaymentMethods\PaymentMethodsController;
use MultiSafepay\WooCommerce\Settings\SettingsController;
use MultiSafepay\WooCommerce\Utils\CustomLinks;
use MultiSafepay\WooCommerce\Utils\Internationalization;
use MultiSafepay\WooCommerce\Utils\Loader;

/**
 * This class is the core of the plugin.
 *
 * Is used to define internationalization, settings hooks, and
 * public face site hooks.
 *
 * @since      4.0.0
 *
 */
class Main {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var      Loader     Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var      string     The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var      string     The current version of the plugin.
	 */
	protected $version;

    /**
     * The plugin dir url
     *
     * @var      string     The plugin directory url
     */
    protected $plugin_dir_url;

    /**
     * The plugin dir path
     *
     * @var      string     The plugin directory path
     */
    protected $plugin_dir_path;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public face of the site.
	 */
	public function __construct() {
		$this->plugin_name = 'multisafepay';
		$this->version = '4.0.0';
		$this->plugin_dir_url = plugin_dir_url(__DIR__);
        $this->plugin_dir_path = plugin_dir_path(__DIR__);
		$this->loader = new Loader();
		$this->set_locale();
        $this->add_custom_links_in_plugin_list();
		$this->define_settings_hooks();
		$this->define_payment_methods_hooks();
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
		$plugin_i18n->set_domain( $this->get_plugin_name() );
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
        $custom_links = new CustomLinks( $this->get_plugin_name(), $this->get_version(), $this->plugin_dir_url );
        $this->loader->add_filter( 'plugin_action_links_multisafepay/multisafepay.php', $custom_links, 'get_links');
    }


	/**
	 * Register all of the hooks related to the common settings
	 * of the plugin.
     *
     * @return void
	 */
	private function define_settings_hooks(): void {
	    if( is_admin() ) {
            // Controller settings page
            $plugin_settings = new SettingsController( $this->get_plugin_name(), $this->get_version(), $this->plugin_dir_url,  $this->plugin_dir_path );
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
     * @todo    Probably css and js is just necessary in checkout page
	 */
	private function define_payment_methods_hooks(): void {
        // Payment controller
		$payment_methods = new PaymentMethodsController( $this->get_plugin_name(), $this->get_version(), $this->plugin_dir_url );
        // Init MultiSafepay payment methods
        $this->loader->add_action( 'plugins_loaded', $payment_methods, 'init_multisafepay_payment_methods' );
        // Enqueue styles in payment methods
		$this->loader->add_action( 'wp_enqueue_scripts', $payment_methods, 'enqueue_styles' );
        // Enqueue scripts in payment methods
		$this->loader->add_action( 'wp_enqueue_scripts', $payment_methods, 'enqueue_scripts' );
        // Register the MultiSafepay payment gateways in WooCommerce.
        $this->loader->add_filter( 'woocommerce_payment_gateways', $payment_methods, 'get_gateways' );
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
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
     *
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

    /**
     * The plugin directory url used to call styles and scripts
     *
     * @return    string    The plugin dir url
     */
    public function get_plugin_dir_url(): string {
        return $this->plugin_dir_url;
    }

    /**
     * The plugin directory path used to require partials
     *
     * @return    string    The plugin dir path
     */
    public function get_plugin_dir_path(): string {
        return $this->plugin_dir_path;
    }

	/**
	 * Retrieve the version number of the plugin.
     *
	 * @return    string    The version number of the plugin.
	 */
	public function get_version(): string {
		return $this->version;
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
