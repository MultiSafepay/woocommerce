<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Settings;

/**
 * The settings page controller.
 * Defines all the functionalities needed on the settings page
 */
class SettingsController {

    /**
     * In plugin version < 4.0.0 the options multisafepay_testmode, and multisafepay_debugmode
     * had been stored as strings and returns yes - no.
     *
     * This function also works to returns booleans instead of strings for
     * multisafepay_second_chance, multisafepay_testmode, multisafepay_debugmode options
     *
     * @see https://developer.wordpress.org/reference/hooks/option_option/
     *
     * @param   string $value
     * @return  boolean
     */
    public function filter_multisafepay_settings_as_booleans( string $value ): bool {
        if ( 'yes' === $value || '1' === $value ) {
            return true;
        }
        return false;
    }

    /**
     * This function returns int instead of strings for multisafepay_time_active
     *
     * @see https://developer.wordpress.org/reference/hooks/option_option/
     *
     * @param   string $value
     * @return  integer
     */
    public function filter_multisafepay_settings_as_int( string $value ): int {
        return (int) $value;
    }

    /**
     * Register the stylesheets and javascript files for the settings page.
     *
     * @see https://developer.wordpress.org/reference/functions/wp_enqueue_style/
     * @see https://developer.wordpress.org/reference/functions/wp_enqueue_script/
     *
     * @return void
     */
    public function enqueue_styles_and_scripts(): void {
        if ( get_current_screen()->base === 'woocommerce_page_multisafepay-settings' ) {
            wp_enqueue_style( 'multisafepay-admin-css', MULTISAFEPAY_PLUGIN_URL . '/assets/admin/css/multisafepay-admin.css', array(), MULTISAFEPAY_PLUGIN_VERSION, 'all' );
        }
        $sections = array( 'multisafepay_applepay', 'multisafepay_googlepay' );
        if ( isset( $_GET['section'] ) && in_array( $_GET['section'], $sections, true ) ) {
            wp_enqueue_script( 'multisafepay-admin-js', MULTISAFEPAY_PLUGIN_URL . '/assets/admin/js/multisafepay-admin.js', array(), MULTISAFEPAY_PLUGIN_VERSION, true );
        }
    }

    /**
     * Register the common settings page in WooCommerce menu section.
     *
     * @see https://developer.wordpress.org/reference/functions/add_submenu_page/
     *
     * @return void
     */
    public function register_common_settings_page(): void {
        $title = sprintf( __( 'MultiSafepay Settings v. %s', 'multisafepay' ), MULTISAFEPAY_PLUGIN_VERSION ); // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
        add_submenu_page(
            'woocommerce',
            esc_html( $title ),
            __( 'MultiSafepay Settings', 'multisafepay' ),
            'manage_woocommerce',
            'multisafepay-settings',
            array( $this, 'display_multisafepay_settings' )
        );
    }

    /**
     * Display the common settings page view.
     *
     * @return void
     */
    public function display_multisafepay_settings(): void {
        $tab_active = $this->get_tab_active();
        require_once MULTISAFEPAY_PLUGIN_DIR_PATH . 'templates/multisafepay-settings-display.php';
    }

    /**
     * Display the support settings page view.
     *
     * @return void
     */
    public function display_multisafepay_support_section(): void {
        require_once MULTISAFEPAY_PLUGIN_DIR_PATH . 'templates/partials/multisafepay-settings-support-display.php';
    }

    /**
     * Display the status tab.
     *
     * @return void
     */
    public function display_multisafepay_status_section(): void {
        $status_controller = new StatusController();
        $status_controller->display();
    }

    /**
     * Display the log tab.
     *
     * @return void
     */
    public function display_multisafepay_logs_section() {
        $logs_controller = new LogsController();
        $logs_controller->display();
    }

    /**
     * Returns active tab defined in get variable
     *
     * @return  string
     */
    private function get_tab_active(): string {
        if ( isset( $_GET['tab'] ) && '' !== $_GET['tab'] ) {
            return wp_unslash( sanitize_key( $_GET['tab'] ) );
        }
        return 'general';
    }

    /**
     * Register general settings in common settings page
     *
     * @return void
     */
    public function register_common_settings(): void {
        $settings_fields = new SettingsFields();
        $settings        = $settings_fields->get_settings();
        foreach ( $settings as $tab_key => $section ) {
            $this->add_settings_section( $tab_key, $section['title'] );
            foreach ( $section['fields'] as $field ) {
                $this->register_setting( $field, $tab_key );
                $this->add_settings_field( $field, $tab_key );
            }
        }
    }

    /**
     * Add settings field
     *
     * @see https://developer.wordpress.org/reference/functions/add_settings_field/
     *
     * @param   array  $field      The field
     * @param   string $tab_key    The key of the tab
     * @return  void
     */
    private function add_settings_field( array $field, string $tab_key ): void {
        add_settings_field(
            $field['id'],
            $this->generate_label_for_settings_field( $field ),
            array( $this, 'display_field' ),
            'multisafepay-settings-' . $tab_key,
            $tab_key,
            array(
                'field' => $field,
            )
        );
    }

    /**
     * Return the label tag to be used in add_settings_field
     *
     * @param   array $field  The settings field array
     * @return  string
     */
    private function generate_label_for_settings_field( array $field ): string {
        if ( '' === $field['tooltip'] ) {
            return sprintf( '<label for="%s">%s</label>', $field['id'], $field['label'] );
        }
        return sprintf( '<label for="%s">%s %s</label>', $field['id'], $field['label'], wc_help_tip( $field['tooltip'] ) );
    }

    /**
     * Filter which set the settings page and adds a screen options of WooCommerce
     *
     * @see http://hookr.io/filters/woocommerce_screen_ids/
     *
     * @param   array $screen
     * @return  array
     */
    public function set_wc_screen_options_in_common_settings_page( array $screen ): array {
        $screen[] = 'woocommerce_page_multisafepay-settings';
        return $screen;
    }

    /**
     * Register setting
     *
     * @see https://developer.wordpress.org/reference/functions/register_setting/
     *
     * @param   array  $field
     * @param   string $tab_key
     * @return  void
     */
    private function register_setting( array $field, string $tab_key ): void {
        register_setting(
            'multisafepay-settings-' . $tab_key,
            $field['id'],
            array(
                'type'              => $field['setting_type'],
                'show_in_rest'      => false,
                'sanitize_callback' => $field['callback'],
            )
        );
    }

    /**
     * Add settings section
     *
     * @see https://developer.wordpress.org/reference/functions/add_settings_section/
     *
     * @param   string $section_key
     * @param   string $section_title
     * @return  void
     */
    private function add_settings_section( string $section_key, string $section_title ): void {
        add_settings_section(
            $section_key,
            $section_title,
            array( $this, 'display_intro_section' ),
            'multisafepay-settings-' . $section_key
        );
    }

    /**
     * Callback to display the title on each settings sections
     *
     * @see https://developer.wordpress.org/reference/functions/add_settings_section/
     *
     * @param   array $args
     * @return  void
     */
    public function display_intro_section( array $args ): void {
        $settings_fields = new SettingsFields();
        $settings        = $settings_fields->get_settings();
        if ( ! empty( $settings[ $args['id'] ]['intro'] ) ) {
            esc_html( (string) printf( '<p>%s</p>', esc_html( $settings[ $args['id'] ]['intro'] ) ) );
        }
    }

    /**
     * Return the HTML view by field.
     * Is the callback function in add_settings_field
     *
     * @param   array $args
     * @return  void
     */
    public function display_field( $args ): void {
        $field                  = $args['field'];
        $settings_field_display = new SettingsFieldsDisplay( $field );
        $settings_field_display->display();
    }

    /**
     * Filter the settings field and return sort by sort_order key.
     *
     * @param   array $settings
     * @return  array
     */
    public function filter_multisafepay_common_settings_fields( array $settings ): array {
        foreach ( $settings as $key => $section ) {
            $sort_order = array();
            foreach ( $section['fields'] as $field_key => $field ) {
                $sort_order[ $field_key ] = $field['sort_order'];
            }
            array_multisort( $sort_order, SORT_ASC, $settings[ $key ]['fields'] );
        }
        return $settings;
    }
}
