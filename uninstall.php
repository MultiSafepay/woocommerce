<?php declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die();
}

/**
 * Fired when the plugin is uninstalled.
 *
 * Is used to remove settings from database if plugin is being uninstalled
 * and user select in the settings remove all data from database.
 *
 * @since      4.0.0
 */
class Uninstall {

    /**
     * The code that runs during plugin uninstall.
     *
     * @return void
     */
    public static function uninstall_multisafepay(): void {

        if ( get_option( 'multisafepay_remove_all_settings', false ) && current_user_can( 'delete_plugins' ) && 'multisafepay/multisafepay.php' === $_POST['plugin'] ) {
            global $wpdb;
            $settings = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . "options WHERE option_name LIKE '%multisafepay%';" );
            foreach ( $settings as $setting ) {
                $wpdb->prepare( 'DELETE FROM %s WHERE option_name = %s', $wpdb->prefix . 'options', $setting->option_name );
            }
        }

        if ( get_option( 'multisafepay_remove_all_settings', false ) && current_user_can( 'delete_plugins' ) && 'multisafepay/multisafepay.php' === $_POST['plugin'] && is_multisite() ) {
            $blogs_ids = get_sites( array( 'fields' => 'ids' ) );
            foreach ( $blogs_ids as $blog_id ) {
                $table_name = $wpdb->prefix . $blog_id . '_options';
                $settings   = $wpdb->prepare( 'SELECT * FROM %s WHERE option_name LIKE %s', $table_name, '%multisafepay%' );
                foreach ( $settings as $setting ) {
                    $wpdb->prepare( 'DELETE FROM %s WHERE option_name = %s', $table_name, $setting->option_name );
                }
            }
        }

        wp_cache_flush();
    }

}

Uninstall::uninstall_multisafepay();
