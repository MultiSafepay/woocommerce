<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Settings;

use MultiSafepay\Util\Version;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\Services\PaymentMethodService;
use WC_Countries;
use WC_Tax;
use WP_Error;

/**
 * Defines all the functionalities needed on the system report page
 *
 * Class SystemReport
 *
 * @package MultiSafepay\WooCommerce\Settings
 */
class SystemReport {

    /**
     * The WC Report
     *
     * @var array
     */
    private $report;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        $this->report = $this->get_woocommerce_system_status_report();
    }

    /**
     * Return the status report from the API
     *
     * @return array|WP_Error
     */
    private function get_woocommerce_system_status_report() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\RestApiUtil::class ) ) {
            return wc_get_container()->get( \Automattic\WooCommerce\Utilities\RestApiUtil::class )->get_endpoint_data( '/wc/v3/system_status' );
        }

        if ( class_exists( \WC_API::class ) ) {
            $wc_api = new \WC_API();
            $report = $wc_api->get_endpoint_data( '/wc/v3/system_status' );
            return $report;
        }

        return array();
    }

    /**
     * Return an array with all the information required
     *
     * @return array
     */
    public function get_multisafepay_system_status_report() {
        if ( empty( $this->report ) ) {
            return array();
        }

        $status_report = array(
            'wordpress_environment'          => $this->get_system_report_wordpress_environment(),
            'server_environment'             => $this->get_system_report_server_environment(),
            'woocommerce_settings'           => $this->get_system_report_woocommerce_settings(),
            'woocommerce_tax_rules'          => $this->get_system_report_woocommerce_tax_rules(),
            'theme_settings'                 => $this->get_system_report_theme_settings(),
            'templates_settings'             => $this->get_system_report_templates_settings(),
            'multisafepay_settings'          => $this->get_system_report_multisafepay_settings(),
            'multisafepay_gateways_settings' => $this->get_system_report_multisafepay_gateways_settings(),
            'active_plugins '                => $this->get_system_report_active_plugins(),
        );
        return $status_report;
    }

    /**
     * Return an array with information about WordPress environment
     *
     * @return array
     */
    public function get_system_report_wordpress_environment(): array {
        $environment           = $this->report['environment'];
        $wordpress_environment = array(
            'title'    => __( 'WordPress environment', 'multisafepay' ),
            'settings' => array(
                'site_url'                  => array(
                    'label' => __( 'Site URL', 'multisafepay' ),
                    'value' => $environment['site_url'],
                ),
                'home_url'                  => array(
                    'label' => __( 'Home URL', 'multisafepay' ),
                    'value' => $environment['home_url'],
                ),
                'woocommerce_version'       => array(
                    'label' => __( 'WooCommerce version', 'multisafepay' ),
                    'value' => $environment['version'],
                ),
                'is_writable_log_directory' => array(
                    'label' => __( 'Log directory writable', 'multisafepay' ),
                    'value' => $environment['log_directory_writable'] ? __( 'Enabled', 'multisafepay' ) : __( 'Disabled', 'multisafepay' ),
                ),
                'wordpress_version'         => array(
                    'label' => __( 'WordPress version', 'multisafepay' ),
                    'value' => $environment['wp_version'],
                ),
                'wordpress_multisite'       => array(
                    'label' => __( 'WordPress multisite', 'multisafepay' ),
                    'value' => $environment['wp_multisite'] ? __( 'Enabled', 'multisafepay' ) : __( 'Disabled', 'multisafepay' ),
                ),
                'wordpress_debug_mode'      => array(
                    'label' => __( 'WordPress debug mode', 'multisafepay' ),
                    'value' => $environment['wp_multisite'] ? __( 'Enabled', 'multisafepay' ) : __( 'Disabled', 'multisafepay' ),
                ),
            ),
        );
        return $wordpress_environment;
    }

    /**
     * Return an array with information about the server environment
     *
     * @return array
     */
    public function get_system_report_server_environment(): array {
        $environment        = $this->report['environment'];
        $server_environment = array(
            'title'    => __( 'Server environment', 'multisafepay' ),
            'settings' => array(
                'server_info' => array(
                    'label' => __( 'Server info', 'multisafepay' ),
                    'value' => $environment['server_info'],
                ),
                'php_version' => array(
                    'label' => __( 'PHP version', 'multisafepay' ),
                    'value' => $environment['php_version'],
                ),
            ),
        );
        return $server_environment;
    }

    /**
     * Return an array with information about WooCommerce settings
     *
     * @return array
     */
    public function get_system_report_woocommerce_settings(): array {
        $settings             = $this->report['settings'];
        $woocommerce_settings = array(
            'title'    => __( 'WooCommerce Settings', 'multisafepay' ),
            'settings' => array(
                'currency'                     => array(
                    'label' => __( 'Currency', 'multisafepay' ),
                    'value' => $settings['currency'] . ' (' . $settings['currency_symbol'] . ')',
                ),
                'currency_position'            => array(
                    'label' => __( 'Currency position', 'multisafepay' ),
                    'value' => $settings['currency_position'],
                ),
                'thousand_separator'           => array(
                    'label' => __( 'Thousand separator', 'multisafepay' ),
                    'value' => $settings['thousand_separator'],
                ),
                'decimal_separator'            => array(
                    'label' => __( 'Decimal separator', 'multisafepay' ),
                    'value' => $settings['decimal_separator'],
                ),
                'number_of_decimals'           => array(
                    'label' => __( 'Number of decimals', 'multisafepay' ),
                    'value' => $settings['number_of_decimals'],
                ),
                'product_types_taxonomies'     => array(
                    'label' => __( 'Product types taxonomies', 'multisafepay' ),
                    'value' => $this->get_product_taxonomies( $settings['taxonomies'] ),
                ),
                'taxes_enable_in_the_store'    => array(
                    'label' => __( 'Store-wide taxes', 'multisafepay' ),
                    'value' => wc_tax_enabled() ? __( 'Enabled', 'multisafepay' ) : __( 'Disabled', 'multisafepay' ),
                ),
                'round_taxes_at_subtotal'      => array(
                    'label' => __( 'Round tax at subtotal level', 'multisafepay' ),
                    'value' => ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) ? __( 'Enabled', 'multisafepay' ) : __( 'Disabled', 'multisafepay' ),
                ),
                'taxes_based_on_store'         => array(
                    'label' => __( 'Calculate tax based on', 'multisafepay' ),
                    'value' => ucfirst( get_option( 'woocommerce_tax_based_on' ) ),
                ),
                'store_location'               => array(
                    'label' => __( 'Store Location', 'multisafepay' ),
                    'value' => implode( '. ', $this->get_base_store_information() ),
                ),
                'allowed_countries'            => array(
                    'label' => __( 'Allowed countries', 'multisafepay' ),
                    'value' => implode( ', ', $this->get_allowed_countries() ),
                ),
                'taxes_display_at_checkout'    => array(
                    'label' => __( 'Display tax totals', 'multisafepay' ),
                    'value' => ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) ? __( 'Itemized', 'multisafepay' ) : __( 'As a single total', 'multisafepay' ),
                ),
                'prices_includes_taxes'        => array(
                    'label' => __( 'Prices entered with tax', 'multisafepay' ),
                    'value' => wc_prices_include_tax() ? __( 'Yes', 'multisafepay' ) : __( 'No', 'multisafepay' ),
                ),
                'coupons_applied_sequentially' => array(
                    'label' => __( 'Calculate coupon discounts sequentially', 'multisafepay' ),
                    'value' => ( 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ) ? __( 'Yes', 'multisafepay' ) : __( 'No', 'multisafepay' ),
                ),
            ),
        );
        return $woocommerce_settings;
    }

    /**
     * Return an array with information about WooCommerce taxes
     *
     * @return array
     */
    public function get_system_report_woocommerce_tax_rules(): array {
        $woocommerce_tax_rules_settings = array(
            'title'    => __( 'WooCommerce Tax Rules', 'multisafepay' ),
            'settings' => array(
                'standard_rates' => array(
                    'label' => __( 'Standard Rates', 'multisafepay' ),
                    'value' => $this->get_woocommerce_standard_rates(),
                ),
            ),
        );
        $non_standard_tax_rates         = $this->get_non_standard_tax_rates();
        foreach ( $non_standard_tax_rates as $key => $non_standard_tax_rate ) {
            $woocommerce_tax_rules_settings['settings'][ $key ]['label'] = $non_standard_tax_rate['label'];
            $woocommerce_tax_rules_settings['settings'][ $key ]['value'] = $non_standard_tax_rate['value'];
        }
        return $woocommerce_tax_rules_settings;
    }

    /**
     * Return an array with information about the MultiSafepay gateway settings
     *
     * @return array
     */
    public function get_system_report_multisafepay_gateways_settings(): array {
        $multisafepay_gateway_settings = array(
            'title'    => __( 'MultiSafepay Gateway Settings', 'multisafepay' ),
            'settings' => array(),
        );
        /** @var BasePaymentMethod $woocommerce_payment_gateway */
        foreach ( ( new PaymentMethodService() )->get_woocommerce_payment_gateways() as $woocommerce_payment_gateway ) {
            $is_enable = $woocommerce_payment_gateway->enabled ? true : false;
            if ( $is_enable ) {
                $multisafepay_gateway_settings_value = '';
                if ( ! empty( $woocommerce_payment_gateway->initial_order_status ) ) {
                    $multisafepay_gateway_settings_value .= __( 'Initial Order Status: ', 'multisafepay' ) . $woocommerce_payment_gateway->initial_order_status . '. ';
                }
                if ( ! empty( $woocommerce_payment_gateway->min_amount ) ) {
                    $multisafepay_gateway_settings_value .= __( 'Min Amount: ', 'multisafepay' ) . $woocommerce_payment_gateway->min_amount . '. ';
                }
                if ( ! empty( $woocommerce_payment_gateway->max_amount ) ) {
                    $multisafepay_gateway_settings_value .= __( 'Max Amount: ', 'multisafepay' ) . $woocommerce_payment_gateway->max_amount . '. ';
                }
                if ( ! empty( $woocommerce_payment_gateway->countries ) ) {
                    $multisafepay_gateway_settings_value .= __( 'Countries: ', 'multisafepay' ) . implode( ', ', $woocommerce_payment_gateway->countries ) . '. ';
                }
                if ( ! empty( $woocommerce_payment_gateway->is_payment_component_enabled() ) ) {
                    $multisafepay_gateway_settings_value .= __( 'Payment Component: ', 'multisafepay' ) . ( $woocommerce_payment_gateway->is_payment_component_enabled() ? __( 'Enabled', 'multisafepay' ) : __( 'Disabled', 'multisafepay' ) ) . '. ';
                }
                if ( ! empty( $woocommerce_payment_gateway->is_tokenization_enabled() ) ) {
                    $multisafepay_gateway_settings_value .= __( 'Recurring payments: ', 'multisafepay' ) . ( $woocommerce_payment_gateway->is_tokenization_enabled() ? __( 'Enabled', 'multisafepay' ) : __( 'Disabled', 'multisafepay' ) ) . '. ';
                }
                if ( ! empty( $woocommerce_payment_gateway->user_roles ) ) {
                    $multisafepay_gateway_settings_value .= __( 'User Roles: ', 'multisafepay' ) . implode( ', ', $woocommerce_payment_gateway->user_roles ) . '. ';
                }

                $multisafepay_gateway_settings['settings'][ $woocommerce_payment_gateway->id ]['label'] = $woocommerce_payment_gateway->get_payment_method_title();
                $multisafepay_gateway_settings['settings'][ $woocommerce_payment_gateway->id ]['value'] = $multisafepay_gateway_settings_value;
            }
        }
        return $multisafepay_gateway_settings;
    }

    /**
     * Return an array with information about the MultiSafepay plugin settings
     *
     * @return array
     */
    public function get_system_report_multisafepay_settings(): array {
        $multisafepay_settings = array(
            'title'    => __( 'MultiSafepay Settings', 'multisafepay' ),
            'settings' => array(
                'sdk_version'                     => array(
                    'label' => __( 'SDK version', 'multisafepay' ),
                    'value' => Version::SDK_VERSION,
                ),
                'test_mode'                       => array(
                    'label' => __( 'Test mode', 'multisafepay' ),
                    'value' => get_option( 'multisafepay_testmode', false ) ? __( 'Enabled', 'multisafepay' ) : __( 'Disabled', 'multisafepay' ),
                ),
                'debug_mode'                      => array(
                    'label' => __( 'Debug mode', 'multisafepay' ),
                    'value' => get_option( 'multisafepay_debugmode', false ) ? __( 'Enabled', 'multisafepay' ) : __( 'Disabled', 'multisafepay' ),
                ),
                'order_request_description'       => array(
                    'label' => __( 'Order description', 'multisafepay' ),
                    'value' => get_option( 'multisafepay_order_request_description' ),
                ),
                'trigger_transaction_to_invoiced' => array(
                    'label' => __( 'Trigger to invoiced', 'multisafepay' ),
                    'value' => get_option( 'multisafepay_trigger_transaction_to_invoiced' ),
                ),
                'trigger_transaction_to_shipped'  => array(
                    'label' => __( 'Trigger to shipped', 'multisafepay' ),
                    'value' => get_option( 'multisafepay_trigger_transaction_to_shipped' ),
                ),
                'final_order_status'              => array(
                    'label' => __( 'Is completed the final order status?', 'multisafepay' ),
                    'value' => $this->get_final_order_status(),
                ),
                'redirect_after_cancel'           => array(
                    'label' => __( 'Redirect after cancel', 'multisafepay' ),
                    'value' => ucfirst( get_option( 'multisafepay_redirect_after_cancel', 'cart' ) ) . ' page',
                ),
                'second_chance'                   => array(
                    'label' => __( 'Second chance', 'multisafepay' ),
                    'value' => (bool) get_option( 'multisafepay_second_chance', false ) ? __( 'Enabled', 'multisafepay' ) : __( 'Disabled', 'multisafepay' ),
                ),
            ),
        );
        $order_statuses        = SettingsFields::get_multisafepay_order_statuses();
        unset( $order_statuses['completed_status'] );
        foreach ( $order_statuses as $key => $order_status ) {
            $multisafepay_settings['settings'][ sanitize_title( $key ) ]['label'] = sprintf( __( 'Default order status for %s', 'multisafepay' ), $order_status['label'] ); // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
            $multisafepay_settings['settings'][ sanitize_title( $key ) ]['value'] = get_option( 'multisafepay_' . $key, $order_status['default'] );
        }

        return $multisafepay_settings;
    }

    /**
     * Return an array with information about the active theme in WordPress
     *
     * @return array
     */
    public function get_system_report_theme_settings(): array {
        $theme          = $this->report['theme'];
        $theme_settings = array(
            'title'    => __( 'Theme settings', 'multisafepay' ),
            'settings' => array(
                'theme_name'          => array(
                    'label' => __( 'Theme Name', 'multisafepay' ),
                    'value' => $theme['name'],
                ),
                'version'             => array(
                    'label' => __( 'Version', 'multisafepay' ),
                    'value' => $theme['version'],
                ),
                'author_url'          => array(
                    'label' => __( 'Author URL', 'multisafepay' ),
                    'value' => $theme['author_url'],
                ),
                'child_theme'         => array(
                    'label' => __( 'Child theme', 'multisafepay' ),
                    'value' => $theme['is_child_theme'] ? 'Yes' : 'No',
                ),
                'woocommerce_support' => array(
                    'label' => __( 'WooCommerce support', 'multisafepay' ),
                    'value' => $theme['has_woocommerce_support'] ? 'Yes' : 'No',
                ),
            ),
        );
        if ( $theme['is_child_theme'] ) {
            $theme_settings['settings']['parent_theme_name']       = array(
                'label' => __( 'Parent theme name', 'multisafepay' ),
                'value' => $theme['parent_name'],
            );
            $theme_settings['settings']['parent_theme_version']    = array(
                'label' => __( 'Parent theme version', 'multisafepay' ),
                'value' => $theme['parent_version'],
            );
            $theme_settings['settings']['parent_theme_author_url'] = array(
                'label' => __( 'Parent theme author URL', 'multisafepay' ),
                'value' => $theme['parent_author_url'],
            );
        }
        return $theme_settings;
    }

    /**
     * Return an array with information about the theme`s templates used by WooCommerce
     *
     * @return array
     */
    public function get_system_report_templates_settings(): array {
        $theme             = $this->report['theme'];
        $template_settings = array(
            'title'    => __( 'Template settings', 'multisafepay' ),
            'settings' => array(
                'overrides' => array(
                    'label' => __( 'Overrides WooCommerce templates', 'multisafepay' ),
                    'value' => ( ! empty( $theme['overrides'] ) ) ? __( 'Yes', 'multisafepay' ) : __( 'No', 'multisafepay' ),
                ),
            ),
        );
        if ( ! empty( $this->get_overrided_templates() ) ) {
            $template_settings['settings']['has_outdated_templates'] = array(
                'label' => __( 'Has outdated templates', 'multisafepay' ),
                'value' => $theme['has_outdated_templates'] ? __( 'Has outdated templates', 'multisafepay' ) : __( 'It does not have outdated templates', 'multisafepay' ),
            );
            $template_settings['settings']['templates_overwritten']  = array(
                'label' => __( 'Templates overwritten', 'multisafepay' ),
                'value' => $this->get_overrided_templates(),
            );
        }
        return $template_settings;
    }

    /**
     * Return an array with information about the active plugins
     *
     * @return array
     */
    public function get_system_report_active_plugins(): array {
        $active_plugins          = $this->report['active_plugins'];
        $active_plugins_settings = array(
            'title'    => __( 'Active plugins', 'multisafepay' ),
            'settings' => array(),

        );
        foreach ( $active_plugins as $active_plugin ) {
            $active_plugins_settings['settings'][ sanitize_title( $active_plugin['name'] ) ]['label'] = $active_plugin['name'] . ' (version: ' . $active_plugin['version'] . ')';
            $active_plugins_settings['settings'][ sanitize_title( $active_plugin['name'] ) ]['value'] = ( ! empty( $active_plugin['url'] ) ) ? $active_plugin['url'] : '';
        }
        return $active_plugins_settings;
    }


    /**
     * Return a string with the product taxonomies
     *
     * @param array $taxonomies
     * @return string
     */
    private function get_product_taxonomies( array $taxonomies ): string {
        $display_terms = array();
        foreach ( $taxonomies as $slug => $name ) {
            $display_terms[] = strtolower( $name ) . ' (' . $slug . ')';
        }
        return implode( ', ', $display_terms );
    }

    /**
     * Return an array with the countries in which the webshop sells.
     *
     * @return array
     */
    private function get_allowed_countries() {
        $base = new WC_Countries();
        return $base->get_allowed_countries();
    }

    /**
     * Return a string with the information of each standard tax rate
     *
     * @return string
     */
    private function get_woocommerce_standard_rates() {
        $standard_tax_rates = WC_Tax::get_rates_for_tax_class( '' );
        if ( empty( $standard_tax_rates ) ) {
            return __( 'There are not standard tax rates registered', 'multisafepay' );
        }
        $standard_rates = PHP_EOL;
        foreach ( $standard_tax_rates as $standard_tax_rate ) {
            if ( ! empty( $standard_tax_rate->tax_rate ) ) {
                $standard_rates .= 'Tax rate: ' . $standard_tax_rate->tax_rate . '.';
            }
            if ( ! empty( $standard_tax_rate->tax_rate_name ) ) {
                $standard_rates .= ' Rate name: ' . $standard_tax_rate->tax_rate_name . '.';
            }
            if ( ! empty( $standard_tax_rate->tax_rate_country ) ) {
                $standard_rates .= ' Country: ' . $standard_tax_rate->tax_rate_country . '.';
            }
            if ( ! empty( $standard_tax_rate->tax_rate_state ) ) {
                $standard_rates .= ' State: ' . $standard_tax_rate->tax_rate_state . '.';
            }
            if ( ! empty( $standard_tax_rate->tax_rate_priority ) ) {
                $standard_rates .= ' Priority: ' . $standard_tax_rate->tax_rate_priority . '.';
            }
            if ( ! empty( $standard_tax_rate->tax_rate_compound ) ) {
                $standard_rates .= ' Compound: ' . $standard_tax_rate->tax_rate_compound . '.';
            }
            if ( ! empty( $standard_tax_rate->tax_rate_shipping ) ) {
                $standard_rates .= ' Shipping: ' . $standard_tax_rate->tax_rate_shipping . '.';
            }
            $standard_rates .= PHP_EOL;
        }
        return $standard_rates;
    }

    /**
     * Return an array with the information of each non standard tax rate
     *
     * @return array
     */
    private function get_non_standard_tax_rates() {
        $tax_classes           = WC_Tax::get_tax_classes();
        $non_standard_tax_rate = array();
        foreach ( $tax_classes as $tax_class ) {
            $tax_rates = WC_Tax::get_rates_for_tax_class( $tax_class );
            if ( ! empty( $tax_rates ) ) {
                $non_standard_tax_rate[ sanitize_title( $tax_class ) ]['label'] = $tax_class;
                $non_standard_tax_rate[ sanitize_title( $tax_class ) ]['value'] = $this->extract_tax_rate_value( $tax_rates );
            }
        }
        return $non_standard_tax_rate;
    }

    /**
     * Returns a string with the information of tax the rates
     *
     * @param array $tax_rates
     * @return string
     */
    public function extract_tax_rate_value( $tax_rates ): string {
        $tax_rate_value = PHP_EOL;
        foreach ( $tax_rates as $tax_rate ) {
            if ( ! empty( $tax_rate->tax_rate ) ) {
                $tax_rate_value .= 'Tax rate: ' . $tax_rate->tax_rate . '.';
            }
            if ( ! empty( $tax_rate->tax_rate_name ) ) {
                $tax_rate_value .= ' Rate name: ' . $tax_rate->tax_rate_name . '.';
            }
            if ( ! empty( $tax_rate->tax_rate_country ) ) {
                $tax_rate_value .= ' Country: ' . $tax_rate->tax_rate_country . '.';
            }
            if ( ! empty( $tax_rate->tax_rate_state ) ) {
                $tax_rate_value .= ' State: ' . $tax_rate->tax_rate_state . '.';
            }
            if ( ! empty( $tax_rate->tax_rate_priority ) ) {
                $tax_rate_value .= ' Priority: ' . $tax_rate->tax_rate_priority . '.';
            }
            if ( ! empty( $tax_rate->tax_rate_compound ) ) {
                $tax_rate_value .= ' Compound: ' . $tax_rate->tax_rate_compound . '.';
            }
            if ( ! empty( $tax_rate->tax_rate_shipping ) ) {
                $tax_rate_value .= ' Shipping: ' . $tax_rate->tax_rate_shipping . '.';
            }
            $tax_rate_value .= PHP_EOL;
        }
        return $tax_rate_value;
    }

    /**
     * Return information about the base location of the webshop
     *
     * @return array
     */
    private function get_base_store_information() {
        $base = new WC_Countries();
        return array(
            'country'  => $base->get_base_country(),
            'state'    => $base->get_base_state(),
            'city'     => $base->get_base_city(),
            'postcode' => $base->get_base_postcode(),
        );
    }

    /**
     * Return information about the overrided templates used by WooCommerce
     *
     * @return string
     */
    private function get_overrided_templates() {
        $theme     = $this->report['theme'];
        $templates = array();
        foreach ( $theme['overrides'] as $template ) {
            $templates[] = $template['file'];
        }
        return implode( ', ', $templates );
    }

    /**
     * Return the final order status, to be displayed in the system report.
     *
     * @return string
     */
    private function get_final_order_status(): string {
        $final_order_statuses = get_option( 'multisafepay_final_order_status', false );
        return empty( $final_order_statuses ) ? 'No' : 'Yes';
    }

    /**
     * Return in plain text all the information to be displayed in a textarea read only section.
     *
     * @return string
     */
    public function get_plain_text_system_status_report() {
        $status_report             = $this->get_multisafepay_system_status_report();
        $plain_text_status_report  = '';
        $plain_text_status_report .= '=================================' . PHP_EOL;
        $plain_text_status_report .= PHP_EOL;
        foreach ( $status_report as $status_report_section ) {
            $plain_text_status_report .= $status_report_section['title'] . PHP_EOL;
            foreach ( $status_report_section['settings'] as $key => $value ) {
                $plain_text_status_report .= $value['label'] . ': ' . $value['value'] . PHP_EOL;
            }
            $plain_text_status_report .= PHP_EOL;
            $plain_text_status_report .= '=================================' . PHP_EOL;
            $plain_text_status_report .= PHP_EOL;
        }
        return $plain_text_status_report;
    }

}
