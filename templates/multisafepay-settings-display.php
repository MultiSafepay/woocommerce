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
<div class="wrap multisafepay woocommerce" id="multisafepay-settings">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <?php settings_errors(); ?>
    <h2 class="nav-tab-wrapper">
        <?php // phpcs:disable ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=multisafepay-settings&tab=general' ) ); ?>" class="nav-tab <?php if ( 'general' === $tab_active ) { ?> nav-tab-active <?php } ?>"><?php echo esc_html__( 'Account', 'multisafepay' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); ?>" class="nav-tab"><?php echo esc_html__( 'Payment Methods', 'multisafepay' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=multisafepay-settings&tab=order_status' ) ); ?>" class="nav-tab <?php if ( 'order_status' === $tab_active ) { ?> nav-tab-active<?php } ?>"><?php echo esc_html__( 'Order Status', 'multisafepay' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=multisafepay-settings&tab=options' ) ); ?>" class="nav-tab <?php if ( 'options' === $tab_active ) { ?> nav-tab-active<?php } ?>"><?php echo esc_html__( 'Options', 'multisafepay' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=multisafepay-settings&tab=status' ) ); ?>" class="nav-tab <?php if ( 'status' === $tab_active ) { ?> nav-tab-active<?php } ?>"><?php echo esc_html__( 'MultiSafepay System Report', 'multisafepay' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=multisafepay-settings&tab=support' ) ); ?>" class="nav-tab <?php if ( 'support' === $tab_active ) { ?> nav-tab-active<?php } ?>"><?php echo esc_html__( 'Support', 'multisafepay' ); ?></a>
        <?php // phpcs:enable ?>
    </h2>
        <?php
        switch ( $tab_active ) {
            case 'order_status':
                echo '<form method="POST" action="' . esc_url( admin_url( 'options.php' ) ) . '">';
                settings_fields( 'multisafepay-settings-order_status' );
                do_settings_sections( 'multisafepay-settings-order_status' );
                submit_button();
                echo '</form>';
                break;
            case 'options':
                echo '<form method="POST" action="' . esc_url( admin_url( 'options.php' ) ) . '">';
                settings_fields( 'multisafepay-settings-options' );
                do_settings_sections( 'multisafepay-settings-options' );
                submit_button();
                echo '</form>';
                break;
            case 'support':
                $this->display_multisafepay_support_section();
                break;
            case 'status':
                $this->display_multisafepay_status_section();
                break;
            case 'general':
            default:
                echo '<form method="POST" action="' . esc_url( admin_url( 'options.php' ) ) . '">';
                settings_fields( 'multisafepay-settings-general' );
                do_settings_sections( 'multisafepay-settings-general' );
                submit_button();
                echo '</form>';
                break;
        }
        ?>
</div>
