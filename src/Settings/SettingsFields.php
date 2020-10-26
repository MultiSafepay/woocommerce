<?php

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

namespace MultiSafepay\WooCommerce\Settings;

/**
 * The settings fields.
 *
 * Defines all the settings fields properties
 *
 * @since   4.0.0
 * @todo Validate sections id
 * @todo Add validations tasks
 * @todo tooltips on fields
 */
class SettingsFields {

    /**
     * The ID of this plugin.
     *
     * @var      string    $plugin_name
     */
    private $plugin_name;

    /**
     * Constructor the the class
     *
     * @var      string    $plugin_name
     */
    public function __construct(string $plugin_name) {
        $this->plugin_name = $plugin_name;
    }

    /**
     * Return the settings fields
     *
     * @return  array  $settings
     */
    public function get_settings(): array {
        $settings = array();
        $settings['general'] = $this->get_settings_general();
        $settings['options'] = $this->get_settings_options();
        $settings['order_status'] = $this->get_settings_order_status();
        $settings = apply_filters( 'multisafepay_common_settings_fields', $settings );
        return $settings;
    }

    /**
     * Return the settings fields for general section
     *
     * @return  array  $settings
     */
    private function get_settings_general(): array {
        return array(
            'title'					=> '',
            'intro'			        => '',
            'fields'				=> array(
                array(
                    'id' 			=> $this->plugin_name . '_environment',
                    'label'			=> __( 'Environment' , $this->plugin_name ),
                    'description'	=> __( 'Environment', $this->plugin_name ),
                    'type'	        => 'select',
                    'options'		=> array(
                        '0' => __('Production Mode', $this->plugin_name),
                        '1' => __('Test Mode', $this->plugin_name),
                    ),
                    'default'		=> '0',
                    'placeholder'	=> __( 'Environment', $this->plugin_name ),
                    'tooltip'       => '',
                    'callback'      => '',
                    'setting_type'  => 'bool',
                    'sort_order'    => 1,
                ),
                array(
                    'id' 			=> $this->plugin_name . '_sandbox_api_key',
                    'label'			=> __( 'Sandbox API Key' , $this->plugin_name ),
                    'description'	=> __( 'Sandbox API Key ', $this->plugin_name ),
                    'type'			=> 'text',
                    'default'		=> '',
                    'placeholder'	=> __( 'Sandbox API Key', $this->plugin_name ),
                    'tooltip'       => '',
                    'callback'      => array($this, 'required_setting_field'),
                    'setting_type'  => 'string',
                    'sort_order'    => 2,
                ),
                array(
                    'id' 			=> $this->plugin_name . '_api_key',
                    'label'			=> __( 'API Key' , $this->plugin_name ),
                    'description'	=> __( 'API Key ', $this->plugin_name ),
                    'type'			=> 'text',
                    'default'		=> '',
                    'placeholder'	=> __( 'API Key', $this->plugin_name ),
                    'tooltip'       => '',
                    'callback'      => '',
                    'setting_type'  => 'string',
                    'sort_order'    => 3,
                ),
                array(
                    'id' 			=> $this->plugin_name . '_debug_mode',
                    'label'			=> __( 'Debug Mode' , $this->plugin_name ),
                    'description'	=> 'Logs additional information to the system log',
                    'type'			=> 'select',
                    'options'		=> array(
                        '0' => __('Disable', $this->plugin_name),
                        '1' => __('Enable', $this->plugin_name),
                    ),
                    'default'		=> '0',
                    'placeholder'	=> __( 'Debug Mode', $this->plugin_name ),
                    'tooltip'       => __('Logs additional information to the system log.', $this->plugin_name),
                    'callback'      => '',
                    'setting_type'  => 'bool',
                    'sort_order'    => 4,
                ),
            )
        );
    }

    /**
     * Return the settings fields for options section
     *
     * @return  array  $settings
     */
    private function get_settings_options(): array {
        return array(
            'title'					=> '',
            'intro'			        => '',
            'fields'		        => array()
        );
    }

    /**
     * Return the settings fields for order status section
     *
     * @return  array  $settings
     */
    private function get_settings_order_status(): array {
        $wc_order_statuses = $this->get_wc_order_statuses();
        $msp_order_statused = $this->get_msp_order_statused();
        $order_status_fields = array();
        foreach ($msp_order_statused as $key => $msp_order_status) {
            $order_status_fields[] = array(
                'id' 			=> $this->plugin_name . '_' . $key,
                'label'			=> __( $msp_order_status , $this->plugin_name ),
                'description'	=> '',
                'type'			=> 'select',
                'options'		=> $wc_order_statuses,
                'default'		=> '0',
                'placeholder'	=> __( 'Debug Mode', $this->plugin_name ),
                'tooltip'       => '',
                'callback'      => '',
                'setting_type'  => 'string',
                'sort_order'    => 1,
            );
        }

        return array(
            'title'					=> '',
            'intro'			        => 'Lorem ipsum dolor sit amet',
            'fields'		        => $order_status_fields
        );
    }

    /**
     * Validates the input field on submit
     *
     * @param   string   $input The input field to be validate or sanitize
     */
    public function required_setting_field( $input ) {
        if(empty($input)) {
            add_settings_error(
                '',
                '',
                __('You need to fill the API KEY', 'wpse'),
                'error',
            );
        }
        return $input;
    }

    private function get_wc_order_statuses() {
        $order_statuses = wc_get_order_statuses();
        return $order_statuses;
    }

    private function get_msp_order_statused() {
        return array (
            'initialized_status'        => __('Initialized', $this->plugin_name),
            'completed_status'          => __('Completed', $this->plugin_name),
            'uncleared_status'          => __('Uncleared', $this->plugin_name),
            'reserved_status'           => __('Reserved', $this->plugin_name),
            'void_status'               => __('Void', $this->plugin_name),
            'declined_status'           => __('Declined', $this->plugin_name),
            'expired_status'            => __('Expired', $this->plugin_name),
            'shipped_status'            => __('Shipped', $this->plugin_name),
            'refunded_status'           => __('Refunded', $this->plugin_name),
            'partial_refunded_status'   => __('Partial refunded', $this->plugin_name),
            'cancelled_status'          => __('Cancelled', $this->plugin_name),
        );
    }

}