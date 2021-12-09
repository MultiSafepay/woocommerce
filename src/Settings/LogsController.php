<?php declare(strict_types=1);

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
