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

namespace MultiSafepay\WooCommerce\Utils;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since    4.0.0
 * @see      https://developer.wordpress.org/reference/functions/register_deactivation_hook/
 * @todo     Define the actions needed to run during the plugin's deactivation.
 *
 */
class Deactivator {

    /**
     * Fired during plugin deactivation according if is multisite or not.
     *
     * @return void
     */
	public function deactivate( bool $network_wide): void {
        if ( ( !is_multisite() ) || ( is_multisite() && !$network_wide) ) {
            $this->deactivate_plugin_single_site();
        }
        if ( is_multisite() && $network_wide ) {
            $this->deactivate_plugin_all_sites();
        }
	}

    /**
     * Deactivation actions for single site
     *
     * @return void
     */
    private function deactivate_plugin_single_site(): void {

    }

    /**
     * Deactivation actions for multisite
     *
     * @return void
     */
    private function deactivate_plugin_all_sites(): void {

    }

}
