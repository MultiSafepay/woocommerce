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

?>
<div class="wrap multisafepay woocommerce" id="multisafepay-settings">
    <h1><?php echo get_admin_page_title(); ?></h1>
    <?php settings_errors(); ?>
    <h2 class="nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=multisafepay-settings&tab=general'); ?>" class="nav-tab <?php if($tab_active === 'general') { ?>nav-tab-active<?php } ?>"><?php echo __('Account', 'multisafepay'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=multisafepay-settings&tab=payment_methods'); ?>" class="nav-tab <?php if($tab_active === 'payment_methods') { ?>nav-tab-active<?php } ?>"><?php echo __('Payment Methods', 'multisafepay'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=multisafepay-settings&tab=order_status'); ?>" class="nav-tab <?php if($tab_active === 'order_status') { ?>nav-tab-active<?php } ?>"><?php echo __('Order Status', 'multisafepay'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=multisafepay-settings&tab=options'); ?>" class="nav-tab <?php if($tab_active === 'options') { ?>nav-tab-active<?php } ?>"><?php echo __('Options', 'multisafepay'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=multisafepay-settings&tab=support'); ?>" class="nav-tab <?php if($tab_active === 'support') { ?>nav-tab-active<?php } ?>"><?php echo __('Support', 'multisafepay'); ?></a>
    </h2>
    <form method="POST" action="<?php echo admin_url('options.php'); ?>">
        <?php
            switch ($tab_active) {
                case 'order_status':
                    settings_fields( 'multisafepay-settings-order_status' );
                    do_settings_sections( 'multisafepay-settings-order_status' );
                    submit_button();
                    break;
                case 'payment_methods':
                    wp_redirect( admin_url('admin.php?page=wc-settings&tab=checkout'));
                    exit;
                    break;
                case 'options':
                    settings_fields( 'multisafepay-settings-options' );
                    do_settings_sections( 'multisafepay-settings-options' );
                    submit_button();
                    break;
                case 'support':
                    $this->display_multisafepay_support_section();
                    break;
                case 'general':
                default:
                    settings_fields( 'multisafepay-settings-general' );
                    do_settings_sections( 'multisafepay-settings-general' );
                    submit_button();
                    break;
            }
        ?>
    </form>
</div>
