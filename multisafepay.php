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
  Plugin Name: MultiSafepay
  Plugin URI: https://docs.multisafepay.com/integrations/woocommerce/
  Description: MultiSafepay Payment Plugin
  Author: MultiSafepay
  Author URI: https://www.multisafepay.com
  Version: 3.5.1

  Copyright: ? 2012 MultiSafepay (email : integration@multisafepay.com)
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

define('MULTISAFEPAY_PLUGIN_FILE', plugins_url('/' . plugin_basename(__DIR__)));

// Load plugin functions
require_once ABSPATH . '/wp-admin/includes/plugin.php';

// Load textdomain
load_plugin_textdomain('multisafepay', false, plugin_basename(dirname(__FILE__)) . '/languages');

function msp_error_woocommerce_not_active()
{
    echo '<div class="error"><p>' . __('Activate WooCommerce to use the MultiSafepay plugin', 'multisafepay') . '</p></div>';
}

function msp_error_curl_not_installed()
{
    echo '<div class="error"><p>' . __('cURL is not installed.<br />In order to use the MultiSafepay plugin, you must install cURL.<br />Ask your system administrator to install php_curl', 'multisafepay') . '</p></div>';
}


// cURL is niet geinstalleerd. foutmelding weergeven
if (!function_exists('curl_version')) {
    add_action('admin_notices', __('msp_error_curl_not_installed', 'multisafepay'));
}


if (is_plugin_active('woocommerce/woocommerce.php') || is_plugin_active_for_network('woocommerce/woocommerce.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    \MultiSafepay\WooCommerce\Gateways::register();
} else {
    add_action('admin_notices', 'msp_error_woocommerce_not_active');
}
