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
 * @author      TechSupport <techsupport@multisafepay.com>
 * @copyright   Copyright (c) 2017 MultiSafepay, Inc. (http://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/*
  Plugin Name: Multisafepay
  Plugin URI: http://www.multisafepay.com
  Description: Multisafepay Payment Plugin
  Author: Multisafepay
  Author URI:http://www.multisafepay.com
  Version: 3.2.0

  Copyright: ? 2012 Multisafepay(email : techsupport@multisafepay.com)
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */



// Load plugin functions
require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

// Load textdomain
load_plugin_textdomain('multisafepay', false, plugin_basename(dirname(__FILE__)) . "/languages");

function error_woocommerce_not_active()
{
    echo '<div class="error"><p>' . __('To use the Multisafepay plug-in it is required that Woocommerce is active', 'multisafepay') . '</p></div>';
}

function error_curl_not_installed()
{
    echo '<div class="error"><p>' . __('Curl is not installed.<br />In order to use the MultiSafepay plug-in, you must install CURL.<br />Ask your system administrator to install php_curl', 'multisafepay') . '</p></div>';
}


// Curl is niet geinstalleerd. foutmelding weergeven
if (!function_exists('curl_version')) {
    add_action('admin_notices', __('error_curl_not_installed', 'multisafepay'));
}


if (is_plugin_active('woocommerce/woocommerce.php') || is_plugin_active_for_network('woocommerce/woocommerce.php')) {

    //Autoloader laden en registreren
    require_once dirname(__FILE__) . '/includes/classes/Autoload.php';

    MultiSafepay_Autoload::register();
    MultiSafepay_Gateways::register();
} else {
    add_action('admin_notices', error_woocommerce_not_active);
}