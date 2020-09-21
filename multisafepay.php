<?php

use MultiSafepay\WooCommerce\DependencyChecker;
use MultiSafepay\WooCommerce\Exceptions\MissingDependencyException;
use MultiSafepay\WooCommerce\Installer;

require_once ABSPATH . '/wp-admin/includes/plugin.php';
require_once __DIR__ . '/vendor/autoload.php';
/*
  Plugin Name: MultiSafepay
  Plugin URI: https://docs.multisafepay.com/integrations/woocommerce/
  Description: MultiSafepay Payment Plugin
  Author: MultiSafepay
  Author URI: https://www.multisafepay.com
  Version: 4.0.0
  Copyright: ? 2020 MultiSafepay (email : integration@multisafepay.com)
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

try{
    (new DependencyChecker())->check();
    Installer::register();
} catch(MissingDependencyException $e) {
    die('Missing dependencies: ' . implode(', ', $e->getMissingPluginNames()) . '<br><br>Please install these extensions to use the Multisafepay WooCommerce plugin');
}
