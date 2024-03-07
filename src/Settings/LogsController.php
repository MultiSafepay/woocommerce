<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Settings;

use MultiSafepay\WooCommerce\Utils\Logger;

/**
 * Defines all the functionalities needed on the logs tab on the settings page
 *
 * Class LogsController
 *
 * @package MultiSafepay\WooCommerce\Settings
 */
class LogsController {

    /**
     * Render and display the log tab
     */
    public function display() {
        $logger                      = new Logger();
        $logs                        = $logger->get_multisafepay_logs();
        $view_multisafepay_log_nonce = sanitize_key( $_POST['view-multisafepay-log'] ?? '' );

        if (
            ( ! empty( $view_multisafepay_log_nonce ) && wp_verify_nonce( wp_unslash( $view_multisafepay_log_nonce ), 'view-multisafepay-log' ) ) &&
            ! empty( $_POST['log_file'] ) && isset( $logs[ sanitize_title( wp_unslash( $_POST['log_file'] ) ) ] ) ) {
            $current_log = $logs[ sanitize_title( wp_unslash( $_POST['log_file'] ) ) ];
        // phpcs:ignore ObjectCalisthenics.ControlStructures.NoElse.ObjectCalisthenics\Sniffs\ControlStructures\NoElseSniff
        } elseif ( ! empty( $logs ) ) {
            $current_log = end( $logs );
        }

        require_once MULTISAFEPAY_PLUGIN_DIR_PATH . 'templates/partials/multisafepay-settings-logs-display.php';
    }
}
