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
 */

namespace MultiSafepay\WooCommerce\Utils;

/**
 * Define the upgrade notices.
 */
class UpgradeNotices {

    /**
     * Show the upgrade notice message in plugin list for multisite WordPress
     *
     * @param string $file
     * @param array  $plugin
     */
    public function show_multisite_upgrade_notice( $file, $plugin ) {
        if ( version_compare( $plugin['Version'], $plugin['new_version'], '<' ) ) {
            $wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
            printf(
                '<tr class="plugin-update-tr"><td colspan="%s" class="plugin-update update-message notice inline notice-warning notice-alt"><div class="update-message"><h4 style="margin: 0; font-size: 14px;">%s</h4>%s</div></td></tr>',
                $wp_list_table->get_column_count(),
                __( 'Upgrade Notice', 'multisafepay' ),
                wpautop( $plugin['upgrade_notice'] )
            );
        }
    }

    /**
     * Show the upgrade notice message in plugin list for non multisite WordPress
     *
     * @param array    $file
     * @param stdClass $plugin
     */
    public function show_non_multisite_upgrade_notice( $file, $plugin ) {
        if ( isset( $plugin->upgrade_notice ) && ! empty( $plugin->upgrade_notice ) ) {
            printf(
                '<br /><br /> <strong>%s: </strong>%s',
                __( 'Upgrade Notice', 'multisafepay' ),
                wp_strip_all_tags( $plugin->upgrade_notice )
            );
        }
    }

}
