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
 * This class defines the custom links added to the Wordpress plugin list
 * for this plugin
 *
 * @since    4.0.0
 */
class CustomLinks {

    /**
     * The ID of this plugin.
     *
     * @var      string     The ID of this plugin.
     */
    private $plugin_name;

    /**
     * Initialize the class and set its properties.
     *
     * @param      string    $plugin_name       The name of this plugin.
     */
    public function __construct( string $plugin_name ) {
        $this->plugin_name = $plugin_name;
    }

    /**
     * Filter and add links to the Wordpress plugin list
     *
     * @see https://developer.wordpress.org/reference/hooks/plugin_action_links_plugin_file/
     *
     * @param array $links
     * @return array
     */
    public function get_links( array $links ): array {
        $custom_links = array(
            '<a href="' . admin_url('multisafepay-settings') . '">' . __('Settings', $this->plugin_name) . '</a>',
            '<a target="_blank" href="https://docs.multisafepay.com/integrations/plugins/woocommerce/">' . __('Docs', $this->plugin_name) . '</a>',
            '<a target="_blank" href="https://docs.multisafepay.com/integrations/plugins/woocommerce/#introduction">' . __('Support', $this->plugin_name) . '</a>',
        );
        return array_merge($custom_links, $links);
    }


}