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

namespace MultiSafepay\WooCommerce\Settings;

use MultiSafepay\WooCommerce\Utils\Logger;

/**
 * The status tab controller.
 *
 * Defines all the functionalities needed on the settings page
 *
 * @since   4.0.0
 */
class LogsController {

    /**
     * Render and display the log tab
     */
    public function display() {
        $logger = new Logger();
        $logs   = $logger->get_multisafepay_logs();

        if (
            ( isset( $_POST['view-multisafepay-log'] ) && wp_verify_nonce( $_POST['view-multisafepay-log'], 'view-multisafepay-log' ) ) &&
            ! empty( $_POST['log_file'] ) && isset( $logs[ sanitize_title( wp_unslash( $_POST['log_file'] ) ) ] ) ) {
            $current_log = $logs[ sanitize_title( wp_unslash( $_POST['log_file'] ) ) ];
        // phpcs:ignore ObjectCalisthenics.ControlStructures.NoElse.ObjectCalisthenics\Sniffs\ControlStructures\NoElseSniff
        } elseif ( ! empty( $logs ) ) {
            $current_log = end( $logs );
        }

        require_once MULTISAFEPAY_PLUGIN_DIR_PATH . 'templates/partials/multisafepay-settings-logs-display.php';
    }

}
