<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

use MultiSafepay\WooCommerce\Exceptions\MissingDependencyException;

/**
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @see      https://developer.wordpress.org/reference/functions/register_activation_hook/
 */
class Activator {

    /**
     * Fired during plugin activation according if is multisite or not.
     *
     * @param  null|bool $network_wide
     * @return  void
     */
    public function activate( ?bool $network_wide ): void {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            die( esc_html__( 'It seems you don\'t have permission to activate plugins', 'multisafepay' ) );
        }
        if ( ( ! is_multisite() ) || ( is_multisite() && ! $network_wide ) ) {
            $this->activate_plugin_single_site();
        }
        if ( $network_wide ) {
            $this->activate_plugin_all_sites();
        }
    }

    /**
     * Check if dependencies are not active and return fatal error
     * for a single site.
     *
     * @return  void
     */
    private function activate_plugin_single_site(): void {
        try {
            $dependency_checker = new DependencyChecker();
            $dependency_checker->check();
        } catch ( MissingDependencyException $missing_dependency_exception ) {
            $dependencies = implode( ', ', $missing_dependency_exception->get_missing_plugin_names() );
            $message      = sprintf( __( 'Missing dependencies: %s. Please install these extensions to use the MultiSafepay WooCommerce plugin', 'multisafepay' ), $dependencies ); // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
            die( esc_html( $message ) );
        }
    }

    /**
     * Check if dependencies are not active and return fatal error
     * for a network.
     *
     * @return  void
     */
    private function activate_plugin_all_sites(): void {
        $blog_ids = $this->get_blogs_ids();
        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            $this->activate_plugin_single_site();
            restore_current_blog();
        }
    }

    /**
     * Return all sites ids
     *
     * @return array
     */
    private function get_blogs_ids(): array {
        $args      = array(
            'fields' => 'ids',
        );
        $blogs_ids = get_sites( $args );
        return $blogs_ids;
    }
}
