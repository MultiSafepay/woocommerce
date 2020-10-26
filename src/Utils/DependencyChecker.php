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

use MultiSafepay\WooCommerce\Exceptions\MissingDependencyException;

/**
 * Fired on bootstrap plugin file.
 * This class defines all code necessary to check if there is a dependency missing to make the plugin work
 *
 * @since    4.0.0
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
        if (!empty($missing_plugins)) {
            throw new MissingDependencyException($missing_plugins);
        }
    }

    /**
     * Return the keys of all missing plugins
     *
     * @return  array
     */
    private function get_missing_plugins_list(): array {
        return array_keys(array_filter(self::REQUIRED_PLUGINS, [$this, 'is_plugin_inactive']));
    }

    /**
     * Check if a certain plugin is inactive
     *
     * @param   string  $plugin_path
     * @return  bool
     */
    private function is_plugin_inactive( string $plugin_path ): bool {
        if( !is_plugin_active( $plugin_path ) ) {
            return true;
        }
        if( is_plugin_active( $plugin_path ) ) {
            return false;
        }
    }

}
