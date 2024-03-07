<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

/**
 * Define the internationalization functionality.
 */
class Internationalization {

    /**
     * Load the plugin text domain for translation.
     *
     * @return void
     */
    public function load_plugin_textdomain(): void {
        load_plugin_textdomain( 'multisafepay', false, dirname( plugin_basename( __FILE__ ), 3 ) . '/languages/' );
    }
}
