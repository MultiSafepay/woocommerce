<?php

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

namespace MultiSafepay\WooCommerce\Settings;

use MultiSafepay\WooCommerce\Settings\SettingsFields;
use MultiSafepay\WooCommerce\Settings\SettingsFieldsDisplay;

/**
 * The settings page controller.
 *
 * Defines all the functionalities needed on the settings page
 *
 * @since   4.0.0
 * @todo Validate sections id
 * @todo Check if is possible to use the filter 'multisafepay_common_settings_fields' to return fields ordered
 */
class SettingsController {

	/**
	 * The ID of this plugin.
	 *
	 * @var      string    $plugin_name
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var      string    $version
	 */
	private $version;

    /**
     * The plugin dir url
     *
     * @var      string    $plugin_dir_url
     */
    private $plugin_dir_url;

    /**
     * The plugin dir path
     *
     * @var      string    $plugin_dir_url
     */
    private $plugin_dir_path;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string    $plugin_name       The name of this plugin.
	 * @param   string    $version           The version of this plugin.
     * @param   string    $plugin_dir_url    The plugin dir url.
     * @param   string    $plugin_dir_path   The plugin dir path.
	 */
	public function __construct( string $plugin_name, string $version, string $plugin_dir_url, string $plugin_dir_path) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->plugin_dir_url = $plugin_dir_url;
        $this->plugin_dir_path = $plugin_dir_path;
	}

	/**
	 * Register the stylesheets for the settings page.
     *
     * @see https://developer.wordpress.org/reference/functions/wp_enqueue_style/
     * @return void
	 */
	public function enqueue_styles(): void {
		wp_enqueue_style( $this->plugin_name, $this->plugin_dir_url . 'assets/admin/css/multisafepay-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the settings page.
     *
     * @see https://developer.wordpress.org/reference/functions/wp_enqueue_script/
     * @return void
	 */
	public function enqueue_scripts():void {
		wp_enqueue_script( $this->plugin_name, $this->plugin_dir_url . 'assets/admin/js/multisafepay-admin.js', array( 'jquery' ), $this->version, false );
	}

    /**
     * Register the common settings page in WooCommerce menu section.
     *
     * @see https://developer.wordpress.org/reference/functions/add_submenu_page/
     * @return void
     */
	public function register_common_settings_page(): void {
        add_submenu_page(
            'woocommerce',
            __('MultiSafepay Settings v.' . $this->version, $this->plugin_name),
            __('MultiSafepay Settings', $this->plugin_name),
            'manage_options',
            'multisafepay-settings',
            array($this, 'display_multisafepay_settings')
        );
    }

    /**
     * Display the common settings page view.
     *
     * @return void
     */
	public function display_multisafepay_settings(): void {
        $tab_active = $this->get_tab_active();
        require_once($this->plugin_dir_path . 'templates/' . $this->plugin_name . '-settings-display.php');
    }

    /**
     * Display the common settings page view.
     *
     * @return void
     */
    public function display_multisafepay_support_section(): void {
        require_once($this->plugin_dir_path . 'templates/partials/' . $this->plugin_name . '-settings-support-display.php');
    }

    /**
     * Returns active tab defined in get variable
     *
     * @return string $tab_active
     */
    private function get_tab_active(): string {
	    if(!isset($_GET['tab']) || $_GET['tab'] === '') {
	        return 'general';
        }
        if(isset($_GET['tab']) || $_GET['tab'] === '') {
            return $_GET['tab'];
        }
    }

    /**
     * Register general settings in common settings page
     *
     * @return void
     */
    public function register_common_settings(): void {
        $settings_fields = new SettingsFields( $this->plugin_name );
        $settings = $settings_fields->get_settings();
        foreach ( $settings as $tab_key => $section ) {
            $this->add_settings_section( $tab_key, $section['title'] );
            foreach ( $section['fields'] as $field ) {
                $this->register_setting( $field, $tab_key );
                $this->add_settings_field( $field, $tab_key );
            }
        }
    }

    /**
     * Add settings field
     *
     * @see https://developer.wordpress.org/reference/functions/add_settings_field/
     * @param   array   $field      The field
     * @param   string  $tab_key    The key of the tab
     * @return void
     */
    private function add_settings_field( array $field, string $tab_key ): void {
        add_settings_field(
            $field['id'],
            $this->generate_label_for_settings_field($field),
            array( $this, 'display_field' ),
            'multisafepay-settings-' . $tab_key,
            $tab_key,
            array(
                'field'     => $field
            )
        );
    }

    /**
     * Return the label tag to be used in add_settings_field
     *
     * @param   array   $field  The settings field array
     * @return  string
     */
    private function generate_label_for_settings_field( array $field ): string {
        if($field['tooltip'] == '') {
            return sprintf('<label for="%s">%s</label>', $field['id'], $field['label']);
        }
        return sprintf('<label for="%s">%s %s</label>', $field['id'], $field['label'], wc_help_tip($field['tooltip']) );
    }

    /**
     * Filter which set the settings page and adds a screen options of WooCommerce
     *
     * @see http://hookr.io/filters/woocommerce_screen_ids/
     * @return  array $screen
     */
    public function set_wc_screen_options_in_common_settings_page( array $screen ): array {
        $screen[] = 'woocommerce_page_multisafepay-settings';
        return $screen;
    }

    /**
     * Register setting
     *
     * @see https://developer.wordpress.org/reference/functions/register_setting/
     * @param   array    $field
     * @return  void
     */
    private function register_setting( array $field, string $tab_key): void {
        register_setting(
            'multisafepay-settings-' . $tab_key,
            $field['id'],
            array(
                'type'                  => $field['setting_type'],
                'show_in_rest'          => false,
                'sanitize_callback'     => $field['callback']
            )
        );
    }

    /**
     * Add settings section
     *
     * @see https://developer.wordpress.org/reference/functions/add_settings_section/
     * @return void
     */
    private function add_settings_section( string $section_key, string $section_title ): void {
        add_settings_section(
            $section_key,
            $section_title,
            '',
            'multisafepay-settings-' . $section_key
        );
    }

    /**
     * Return the HTML view by field.
     * Is the callback function in add_settings_field
     *
     * @return  string  $html
     */
    public function display_field( $args ) {
        $field      = $args['field'];
        $settings_field_display = new SettingsFieldsDisplay($this->plugin_name, $field);
        $settings_field_display->display();
    }

}