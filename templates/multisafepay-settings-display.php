<?php declare(strict_types=1); ?>
<div class="wrap multisafepay woocommerce" id="multisafepay-settings">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <?php settings_errors(); ?>
    <h2 class="nav-tab-wrapper">
        <?php // phpcs:disable ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=multisafepay-settings&tab=general' ) ); ?>" class="nav-tab <?php if ( 'general' === $tab_active ) { ?> nav-tab-active <?php } ?>"><?php echo esc_html__( 'Account', 'multisafepay' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); ?>" class="nav-tab"><?php echo esc_html__( 'Payment Methods', 'multisafepay' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=multisafepay-settings&tab=order_status' ) ); ?>" class="nav-tab <?php if ( 'order_status' === $tab_active ) { ?> nav-tab-active<?php } ?>"><?php echo esc_html__( 'Order Status', 'multisafepay' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=multisafepay-settings&tab=options' ) ); ?>" class="nav-tab <?php if ( 'options' === $tab_active ) { ?> nav-tab-active<?php } ?>"><?php echo esc_html__( 'Options', 'multisafepay' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=multisafepay-settings&tab=logs' ) ); ?>" class="nav-tab <?php if ( 'logs' === $tab_active ) { ?> nav-tab-active<?php } ?>"><?php echo esc_html__( 'Logs', 'multisafepay' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=multisafepay-settings&tab=status' ) ); ?>" class="nav-tab <?php if ( 'status' === $tab_active ) { ?> nav-tab-active<?php } ?>"><?php echo esc_html__( 'MultiSafepay System Report', 'multisafepay' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=multisafepay-settings&tab=support' ) ); ?>" class="nav-tab <?php if ( 'support' === $tab_active ) { ?> nav-tab-active<?php } ?>"><?php echo esc_html__( 'Support', 'multisafepay' ); ?></a>
        <?php // phpcs:enable ?>
    </h2>
        <?php
        switch ( $tab_active ) {
            case 'order_status':
                echo '<div id="multisafepay-order-status" class="multisafepay-order-status">';
                echo '<h2 id="multisafepay-title">' . esc_html__( 'Order Status', 'multisafepay' ) . '</h2>';
                echo '<form method="POST" action="' . esc_url( admin_url( 'options.php' ) ) . '">';
                settings_fields( 'multisafepay-settings-order_status' );
                do_settings_sections( 'multisafepay-settings-order_status' );
                submit_button();
                echo '</form>';
                echo '</div>';
                break;
            case 'options':
                echo '<div id="multisafepay-options" class="multisafepay-options">';
                echo '<h2 id="multisafepay-title">' . esc_html__( 'Options', 'multisafepay' ) . '</h2>';
                echo '<form method="POST" action="' . esc_url( admin_url( 'options.php' ) ) . '">';
                settings_fields( 'multisafepay-settings-options' );
                do_settings_sections( 'multisafepay-settings-options' );
                submit_button();
                echo '</form>';
                echo '</div>';
                break;
            case 'logs':
                $this->display_multisafepay_logs_section();
                break;
            case 'support':
                $this->display_multisafepay_support_section();
                break;
            case 'status':
                $this->display_multisafepay_status_section();
                break;
            case 'general':
            default:
                echo '<div id="multisafepay-account" class="multisafepay-account">';
                echo '<h2 id="multisafepay-title">' . esc_html__( 'Account', 'multisafepay' ) . '</h2>';
                echo '<form method="POST" action="' . esc_url( admin_url( 'options.php' ) ) . '">';
                settings_fields( 'multisafepay-settings-general' );
                do_settings_sections( 'multisafepay-settings-general' );
                submit_button();
                echo '</form>';
                echo '</div>';
                break;
        }
        ?>
</div>
