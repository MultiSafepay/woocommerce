<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Settings;

/**
 * Defines all the functionalities needed on the system report tab in the settings page
 *
 * Class StatusController
 *
 * @package MultiSafepay\WooCommerce\Settings
 */
class StatusController {

    /**
     * Render and display the status tab
     */
    public function display() {
        $system_report            = new SystemReport();
        $status_report            = $system_report->get_multisafepay_system_status_report();
        $plain_text_status_report = $system_report->get_plain_text_system_status_report();
        require_once MULTISAFEPAY_PLUGIN_DIR_PATH . 'templates/partials/multisafepay-settings-status-display.php';
    }
}
