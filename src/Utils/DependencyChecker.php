<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

use MultiSafepay\WooCommerce\Exceptions\MissingDependencyException;

/**
 * Fired on bootstrap plugin file.
 * This class defines all code necessary to check if there is a dependency missing to make the plugin work
 */
class DependencyChecker {

    const REQUIRED_PLUGINS = array(
        'WooCommerce' => 'woocommerce/woocommerce.php',
    );

    /**
     * Check if there is a dependency missing to make the plugin work
     *
     * @throws MissingDependencyException
     * @return void
     */
    public function check(): void {
        $missing_plugins = $this->get_missing_plugins_list();
        if ( ! empty( $missing_plugins ) ) {
            throw new MissingDependencyException( $missing_plugins );
        }
    }

    /**
     * Return the keys of all missing plugins
     *
     * @return  array
     */
    private function get_missing_plugins_list(): array {
        return array_keys( array_filter( self::REQUIRED_PLUGINS, array( $this, 'is_plugin_inactive' ) ) );
    }

    /**
     * Check if a certain plugin is inactive
     *
     * @param   string $plugin_path
     * @return  boolean
     */
    private function is_plugin_inactive( string $plugin_path ): bool {
        if ( ! is_plugin_active( $plugin_path ) ) {
            return true;
        }

        return false;
    }
}
