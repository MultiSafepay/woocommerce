<?php declare(strict_types=1); ?>

<?php if ( defined( 'WC_LOG_HANDLER' ) && 'WC_Log_Handler_DB' === WC_LOG_HANDLER ) { ?>
    <div id="multisafepay-logs" class="multisafepay-logs">
        <h2 id="multisafepay-title"><?php echo esc_html__( 'Logs', 'multisafepay' ); ?></h2>
        <p><?php echo esc_html__( 'It seems you are writing logs in database and not in a log file. This tools only works if you enable logs using file system.', 'multisafepay' ); ?></p>
    </div>
<?php } ?>

<?php if ( ( ! defined( 'WC_LOG_HANDLER' ) || 'WC_Log_Handler_DB' !== WC_LOG_HANDLER ) && ! $logs ) { ?>
    <div id="multisafepay-logs" class="multisafepay-logs">
        <h2 id="multisafepay-title"><?php echo esc_html__( 'Logs', 'multisafepay' ); ?></h2>
        <p><?php echo esc_html__( 'There are currently no logs to view.', 'multisafepay' ); ?></p>
    </div>
<?php } ?>

<?php if ( ( ! defined( 'WC_LOG_HANDLER' ) || 'WC_Log_Handler_DB' !== WC_LOG_HANDLER ) && $logs ) { ?>
    <div id="multisafepay-logs" class="multisafepay-logs">
        <h2 id="multisafepay-title"><?php echo esc_html__( 'Logs', 'multisafepay' ); ?></h2>
        <form action="<?php echo esc_url( admin_url( 'admin.php?page=multisafepay-settings&tab=logs' ) ); ?>" method="post">
            <?php wp_nonce_field( 'view-multisafepay-log', 'view-multisafepay-log' ); ?>
            <select name="log_file">
                <?php foreach ( $logs as $log_file ) { ?>
                    <option value="<?php echo esc_attr( $log_file ); ?>" <?php selected( $log_file, $current_log ); ?>><?php echo esc_html( $log_file ); ?></option>
                <?php } ?>
            </select>
            <button type="submit" class="button" value="<?php esc_attr_e( 'View', 'multisafepay' ); ?>"><?php esc_html_e( 'View', 'multisafepay' ); ?></button>
        </form>
        <?php if ( $current_log ) { ?>
            <h3><?php echo esc_html( $current_log ); ?></h3>
            <div id="log-viewer">
                <pre><?php echo esc_html( file_get_contents( WC_LOG_DIR . $current_log ) ); ?></pre>
            </div>
        <?php } ?>
    </div>
<?php } ?>
