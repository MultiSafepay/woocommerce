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

?>
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
