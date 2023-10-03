<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

use WC_Log_Handler_File;

/**
 * Class Logger
 */
class Logger {

    /**
     * Log method for emergency level
     * System is unusable.
     * Example:
     *
     * @param string $message
     */
    public static function log_emergency( string $message ) {
        $logger = wc_get_logger();
        $logger->log( 'emergency', $message, array( 'source' => 'multisafepay' ) );
    }

    /**
     * Log method for alert level
     * Action must be taken immediately.
     * Example: Entire website down, database unavailable, etc.
     *
     * @param string $message
     */
    public static function log_alert( string $message ) {
        $logger = wc_get_logger();
        $logger->log( 'alert', $message, array( 'source' => 'multisafepay' ) );
    }

    /**
     * Log method for critical level
     * Critical conditions.
     * Example: Unexpected exceptions.
     *
     * @param string $message
     */
    public static function log_critical( string $message ) {
        $logger = wc_get_logger();
        $logger->log( 'critical', $message, array( 'source' => 'multisafepay' ) );
    }

    /**
     * Log method for error level
     * Error conditions
     * Example: Set to shipped or invoiced an order, which is on initialized status
     *
     * @param string $message
     */
    public static function log_error( string $message ) {
        $logger = wc_get_logger();
        $logger->log( 'error', $message, array( 'source' => 'multisafepay' ) );
    }

    /**
     * Log method for warning level
     * Exceptional occurrences that are not errors because do not lead to a complete failure of the application.
     * Example: Entire website down, database unavailable, etc.
     *
     * @param string $message
     */
    public static function log_warning( string $message ) {
        $logger = wc_get_logger();
        $logger->log( 'warning', $message, array( 'source' => 'multisafepay' ) );
    }

    /**
     * Log method for notice level
     * Normal but significant events.
     * Example: A notification has been processed using a payment method different than the one registered when the order was created.
     *
     * @param string $message
     */
    public static function log_notice( string $message ) {
        $logger = wc_get_logger();
        $logger->log( 'notice', $message, array( 'source' => 'multisafepay' ) );
    }

    /**
     * Log method for info level
     * Interesting events.
     * Example: The payment link of a transaction
     *
     * @param string $message
     */
    public static function log_info( string $message ) {
        if ( get_option( 'multisafepay_debugmode', false ) ) {
            $logger = wc_get_logger();
            $logger->log( 'info', $message, array( 'source' => 'multisafepay' ) );
        }
    }

    /**
     * Log method for debug level
     * Detailed debug information: Denotes specific and detailed information of every action.
     * Example: The trace of every action registered in the system.
     *
     * @param string $message
     */
    public static function log_debug( string $message ) {
        if ( get_option( 'multisafepay_debugmode', false ) ) {
            $logger = wc_get_logger();
            $logger->log( 'debug', $message, array( 'source' => 'multisafepay' ) );
        }
    }

    /**
     * Return an array of logs filenames
     *
     * @return array
     */
    private function get_logs(): array {
        $logs = WC_Log_Handler_File::get_log_files();
        return $logs;
    }

    /**
     * Return an array of logs that belongs to MultiSafepay
     *
     * @return array
     */
    public function get_multisafepay_logs(): array {
        $logs = $this->get_logs();
        foreach ( $logs as $key => $log ) {
            if ( strpos( $log, 'multisafepay' ) === false ) {
                unset( $logs[ $key ] );
            }
        }
        return $logs;
    }
}
