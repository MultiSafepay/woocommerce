<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

/**
 * This class defines the custom links added to the WordPress plugin list for this plugin
 */
class CustomLinks {

    /**
     * Filter and add links to the WordPress plugin list
     *
     * @see https://developer.wordpress.org/reference/hooks/plugin_action_links_plugin_file/
     *
     * @param array $links
     * @return array
     */
    public function get_links( array $links ): array {
        $custom_links = array(
            '<a href="' . admin_url( 'admin.php?page=multisafepay-settings' ) . '">' . __( 'Settings', 'multisafepay' ) . '</a>',
            '<a target="_blank" href="https://docs.multisafepay.com/docs/woocommerce">' . __( 'Docs & Support', 'multisafepay' ) . '</a>',
        );
        return array_merge( $custom_links, $links );
    }
}
