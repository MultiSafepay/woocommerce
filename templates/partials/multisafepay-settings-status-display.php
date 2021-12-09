<?php declare(strict_types=1); ?>
<div id="multisafepay-system-status" class="multisafepay-system-status">
    <h2 id="multisafepay-title"><?php echo esc_html__( 'MultiSafepay System Report', 'multisafepay' ); ?></h2>
    <div class="system-status-section">
        <p><?php echo esc_html__( 'Please copy and paste this information in your ticket when contacting support.', 'multisafepay' ); ?></p>
        <p class="submit">
            <a href="#" onclick="jQuery( '#multisafepay-system-report' ).toggle( 'slow');" class="button-primary multisafepay-system-report"><?php esc_html_e( 'Get system report', 'multisafepay' ); ?></a>
        </p>
        <div id="multisafepay-system-report">
            <textarea readonly="readonly"><?php echo esc_html( $plain_text_status_report ); ?></textarea>
        </div>
    </div>
    <?php foreach ( $status_report as $status_report_section ) { ?>
        <table class="multisafepay_status_table widefat">
            <thead>
            <tr>
                <th colspan="2">
                    <h2><?php echo esc_html( $status_report_section['title'] ); ?></h2>
                </th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ( $status_report_section['settings'] as $key => $value ) { ?>
                <tr>
                    <td>
                        <?php echo esc_html( $value['label'] ); ?>:
                    </td>
                    <td>
                        <?php echo esc_html( $value['value'] ); ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    <?php } ?>
</div>
