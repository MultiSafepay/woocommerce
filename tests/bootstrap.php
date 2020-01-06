<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Multisafepay
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}
$_bootstrap = dirname( __DIR__ ) . '/vendor/woocommerce/woocommerce/tests/bootstrap.php';


// Verify that Composer dependencies have been installed.
if ( ! file_exists( $_bootstrap ) ) {
    echo "\033[0;31mUnable to find the WooCommerce test bootstrap file. Have you run `composer install`?\033[0;m" . PHP_EOL;
    exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/multisafepay.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once $_bootstrap;
// Register autoloader
require_once dirname(__FILE__) . '/../includes/classes/Autoload.php';

MultiSafepay_Autoload::register();
MultiSafepay_Gateways::register();
