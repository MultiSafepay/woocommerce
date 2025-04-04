<?php declare(strict_types=1);

/**
 * Plugin Name:             MultiSafepay
 * Plugin URI:              https://docs.multisafepay.com/docs/woocommerce
 * Description:             MultiSafepay Payment Plugin
 * Version:                 6.8.1
 * Author:                  MultiSafepay
 * Author URI:              https://www.multisafepay.com
 * Copyright:               Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 * License:                 GNU General Public License v3.0
 * License URI:             http://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least:       6.0
 * Tested up to:            6.7.2
 * WC requires at least:    6.0.0
 * WC tested up to:         9.7.1
 * Requires PHP:            7.3
 * Text Domain:             multisafepay
 * Domain Path:             /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    die();
}

/**
 * Plugin version
 */
define( 'MULTISAFEPAY_PLUGIN_VERSION', '6.8.1' );

/**
 * Plugin URL
 * Do not include a trailing slash. Should be include it in the string with which is concatenated
 */
define( 'MULTISAFEPAY_PLUGIN_URL', plugins_url( '', __FILE__ ) );

/**
 * Plugin dir path
 * Include a trailing slash. Should not be include it in the string with which is concatenated
 */
define( 'MULTISAFEPAY_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Composer's autoload file.
 */
require_once MULTISAFEPAY_PLUGIN_DIR_PATH . 'vendor/autoload.php';

use MultiSafepay\WooCommerce\Utils\Activator;
use MultiSafepay\WooCommerce\Main;

/**
 * The code that runs during plugin activation.
 * The class is documented in src/utils/Activator.php
 *
 * @see     https://developer.wordpress.org/reference/functions/register_activation_hook/
 *
 * @param   null|bool $network_wide
 * @return void
 */
function activate_multisafepay( ?bool $network_wide ): void {
    $activator = new Activator();
    $activator->activate( $network_wide );
}
register_activation_hook( __FILE__, 'activate_multisafepay' );

/**
 * Init plugin
 *
 * @see      https://developer.wordpress.org/plugins/hooks/
 * @return void
 */
function init_multisafepay() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        $plugin = new Main();
        $plugin->init();
    }
}

/**
 * Wait for WooCommerce to load
 *
 * @return void
 */
function action_woocommerce_loaded() {
    init_multisafepay();
}
add_action( 'woocommerce_loaded', 'action_woocommerce_loaded', 10, 1 );
