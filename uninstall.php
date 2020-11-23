<?php declare(strict_types=1);

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
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */


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
 *
 */
class Uninstall {

    /**
     * The code that runs during plugin uninstall.
     *
     * @return void
     */
    public static function uninstall_multisafepay(): void {

        if( get_option( 'multisafepay_remove_all_settings', false ) && current_user_can( 'delete_plugins' ) && $_POST['plugin'] === 'multisafepay/multisafepay.php' ) {

            global $wpdb;

            $settings =  $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "options WHERE option_name LIKE '%multisafepay%';");
            foreach ($settings as $setting) {
                $wpdb->query("DELETE FROM " . $wpdb->prefix . "options WHERE option_name = '".$setting->option_name."';");
            }

            if ( is_multisite() ) {
                $blogs_ids = get_sites( array( 'fields' => 'ids' ) );
                foreach ($blogs_ids as $blog_id) {
                    $table_name = $wpdb->prefix . $blog_id . "_options";
                    $settings =  $wpdb->get_results("SELECT * FROM " . $table_name . " WHERE option_name LIKE '%multisafepay%';");
                    foreach ($settings as $setting) {
                        $wpdb->query("DELETE FROM " . $table_name . " WHERE option_name = '" . $setting->option_name . "';");
                    }
                }
            }

            wp_cache_flush();

        }
    }

}

Uninstall::uninstall_multisafepay();