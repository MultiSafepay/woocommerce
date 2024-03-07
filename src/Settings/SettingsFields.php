<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Settings;

use MultiSafepay\WooCommerce\Services\PaymentMethodService;

/**
 * Defines the settings fields properties
 *
 * Class SettingsFields
 *
 * @package MultiSafepay\WooCommerce\Settings
 */
class SettingsFields {

    /**
     * Return the settings fields
     *
     * @return  array
     */
    public function get_settings(): array {
        $settings                 = array();
        $settings['general']      = $this->get_settings_general();
        $settings['options']      = $this->get_settings_options();
        $settings['order_status'] = $this->get_settings_order_status();
        $settings                 = apply_filters( 'multisafepay_common_settings_fields', $settings );
        return $settings;
    }

    /**
     * Return the settings fields for general section
     *
     * @return  array
     */
    private function get_settings_general(): array {
        return array(
            'title'  => '',
            'intro'  => '',
            'fields' => array(
                array(
                    'id'           => 'multisafepay_testmode',
                    'label'        => __( 'Test Mode', 'multisafepay' ),
                    'description'  => '',
                    'type'         => 'checkbox',
                    'default'      => false,
                    'placeholder'  => __( 'Test Mode', 'multisafepay' ),
                    'tooltip'      => __( 'Check this option if you want to enable MultiSafepay in test mode.', 'multisafepay' ),
                    'callback'     => '',
                    'setting_type' => 'boolean',
                    'sort_order'   => 1,
                ),
                array(
                    'id'           => 'multisafepay_test_api_key',
                    'label'        => __( 'Test API Key', 'multisafepay' ),
                    'description'  => '',
                    'type'         => 'password',
                    'default'      => '',
                    'placeholder'  => __( 'Test API Key ', 'multisafepay' ),
                    'tooltip'      => __( 'Test API Key', 'multisafepay' ),
                    'callback'     => '',
                    'setting_type' => 'string',
                    'sort_order'   => 2,
                ),
                array(
                    'id'           => 'multisafepay_api_key',
                    'label'        => __( 'API Key', 'multisafepay' ),
                    'description'  => '',
                    'type'         => 'password',
                    'default'      => '',
                    'placeholder'  => __( 'API Key ', 'multisafepay' ),
                    'tooltip'      => __( 'API Key', 'multisafepay' ),
                    'callback'     => '',
                    'setting_type' => 'string',
                    'sort_order'   => 3,
                ),
            ),
        );
    }

    /**
     * Return the settings fields for options section
     *
     * @return  array
     */
    private function get_settings_options(): array {
        return array(
            'title'  => '',
            'intro'  => '',
            'fields' => array(
                array(
                    'id'           => 'multisafepay_group_credit_cards',
                    'label'        => __( 'Group Credit Cards', 'multisafepay' ),
                    'description'  => __( 'If is enable, payment methods classified as credit cards (Amex, Maestro, Mastercard, and Visa) will shown grouped as a single payment method', 'multisafepay' ),
                    'type'         => 'checkbox',
                    'default'      => (bool) get_option(
                        'multisafepay_group_credit_cards',
                        PaymentMethodService::is_multisafepay_credit_card_woocommerce_payment_gateway_enabled()
                    ),
                    'placeholder'  => __( 'Group Credit Cards', 'multisafepay' ),
                    'tooltip'      => '',
                    'callback'     => '',
                    'setting_type' => 'boolean',
                    'sort_order'   => 3,
                ),
                array(
                    'id'           => 'multisafepay_debugmode',
                    'label'        => __( 'Debug Mode', 'multisafepay' ),
                    'description'  => 'Is recommended to keep debug mode disabled in live environment',
                    'type'         => 'checkbox',
                    'default'      => false,
                    'placeholder'  => __( 'Debug Mode', 'multisafepay' ),
                    'tooltip'      => __( 'Logs additional information to the system log', 'multisafepay' ),
                    'callback'     => '',
                    'setting_type' => 'boolean',
                    'sort_order'   => 1,
                ),
                array(
                    'id'           => 'multisafepay_disable_shopping_cart',
                    'label'        => __( 'Disable Shopping Cart on the MultiSafepay payment page', 'multisafepay' ),
                    'description'  => 'Enable this option to hide the cart items on the MultiSafepay payment page, leaving only the total order amount. Note: This behavior won\'t be adopted by the the payment methods which require shopping cart like Riverty, E-Invoicing, in3, Klarna and Pay After Delivery.',
                    'type'         => 'checkbox',
                    'default'      => false,
                    'placeholder'  => __( 'Disable Shopping Cart', 'multisafepay' ),
                    'tooltip'      => '',
                    'callback'     => '',
                    'setting_type' => 'boolean',
                    'sort_order'   => 100,
                ),
                array(
                    'id'           => 'multisafepay_order_request_description',
                    'label'        => __( 'Order Description', 'multisafepay' ),
                    'description'  => __( 'A text which will be shown with the order in MultiSafepay Control. If the customer’s bank supports it this description will also be shown on the customer’s bank statement', 'multisafepay' ),
                    'type'         => 'text',
                    'default'      => 'Payment for order: {order_number}',
                    'placeholder'  => __( 'Order Description.', 'multisafepay' ),
                    'tooltip'      => __( 'You can include the order number using {order_number}', 'multisafepay' ),
                    'callback'     => '',
                    'setting_type' => 'string',
                    'sort_order'   => 10,
                ),
                array(
                    'id'           => 'multisafepay_trigger_transaction_to_invoiced',
                    'label'        => __( 'Set transaction as invoiced', 'multisafepay' ),
                    'description'  => __( 'When the order reaches this status, we send the invoice id to MultiSafepay', 'multisafepay' ),
                    'type'         => 'select',
                    'options'      => array(
                        'wc-processing' => __( 'Processing', 'multisafepay' ),
                        'wc-completed'  => __( 'Completed', 'multisafepay' ),
                    ),
                    'default'      => 'wc-completed',
                    'placeholder'  => __( 'Set transaction as invoiced', 'multisafepay' ),
                    'tooltip'      => 'The invoice id will be added to financial reports and exports generated within MultiSafepay Control',
                    'callback'     => '',
                    'setting_type' => 'string',
                    'sort_order'   => 20,
                ),
                array(
                    'id'           => 'multisafepay_trigger_transaction_to_shipped',
                    'label'        => __( 'Set transaction as shipped', 'multisafepay' ),
                    'description'  => __( 'When the order reaches this status, a notification will be sent to MultiSafepay to set the transaction status as shipped', 'multisafepay' ),
                    'type'         => 'select',
                    'options'      => array(
                        'wc-processing' => __( 'Processing', 'multisafepay' ),
                        'wc-completed'  => __( 'Completed', 'multisafepay' ),
                    ),
                    'default'      => 'wc-completed',
                    'placeholder'  => __( 'Set transaction as shipped', 'multisafepay' ),
                    'tooltip'      => '',
                    'callback'     => '',
                    'setting_type' => 'string',
                    'sort_order'   => 25,
                ),
                array(
                    'id'           => 'multisafepay_redirect_after_cancel',
                    'label'        => __( 'After cancel redirect the customer to', 'multisafepay' ),
                    'description'  => __( 'When the order is cancelled by the customer, redirect the customer to the selected page', 'multisafepay' ),
                    'type'         => 'select',
                    'options'      => array(
                        'cart'     => __( 'Cart Page', 'multisafepay' ),
                        'checkout' => __( 'Checkout Page', 'multisafepay' ),
                    ),
                    'default'      => 'cart',
                    'placeholder'  => __( 'After cancel redirect the customer to', 'multisafepay' ),
                    'tooltip'      => '',
                    'callback'     => '',
                    'setting_type' => 'string',
                    'sort_order'   => 30,
                ),
                array(
                    'id'           => 'multisafepay_final_order_status',
                    'label'        => __( 'Is completed the final order status?', 'multisafepay' ),
                    'description'  => __( 'When the order reaches the completed status, the notification callback from MultiSafepay will not alter it.', 'multisafepay' ),
                    'type'         => 'checkbox',
                    'default'      => false,
                    'placeholder'  => __( 'Is completed the final order status?', 'multisafepay' ),
                    'tooltip'      => '',
                    'callback'     => '',
                    'setting_type' => 'boolean',
                    'sort_order'   => 35,
                ),
                array(
                    'id'           => 'multisafepay_time_active',
                    'label'        => __( 'Value lifetime of payment link', 'multisafepay' ),
                    'description'  => '',
                    'type'         => 'text',
                    'default'      => '30',
                    'placeholder'  => __( 'Value lifetime of payment link', 'multisafepay' ),
                    'tooltip'      => '',
                    'callback'     => '',
                    'setting_type' => 'int',
                    'sort_order'   => 40,
                ),
                array(
                    'id'           => 'multisafepay_time_unit',
                    'label'        => __( 'Unit lifetime of payment link', 'multisafepay' ),
                    'description'  => __( 'The lifetime of a payment link by default is 30 days. This means that the customer has 30 days to complete the transaction using the payment link', 'multisafepay' ),
                    'type'         => 'select',
                    'options'      => array(
                        'days'    => __( 'Days', 'multisafepay' ),
                        'hours'   => __( 'Hours', 'multisafepay' ),
                        'seconds' => __( 'Seconds', 'multisafepay' ),
                    ),
                    'default'      => 'days',
                    'placeholder'  => __( 'Unit lifetime of payment link', 'multisafepay' ),
                    'tooltip'      => '',
                    'callback'     => '',
                    'setting_type' => 'string',
                    'sort_order'   => 45,
                ),
                array(
                    'id'           => 'multisafepay_second_chance',
                    'label'        => __( 'Second Chance', 'multisafepay' ),
                    'description'  => __( 'More information about Second Chance on <a href="https://docs.multisafepay.com/docs/second-chance" target="_blank">MultiSafepay\'s Documentation Center</a>.', 'multisafepay' ),
                    'type'         => 'checkbox',
                    'default'      => false,
                    'placeholder'  => __( 'Second Chance', 'multisafepay' ),
                    'tooltip'      => __( 'MultiSafepay will send two Second Chance reminder emails. In the emails, MultiSafepay will include a link to allow the consumer to finalize the payment. The first Second Chance email is sent 1 hour after the transaction was initiated and the second after 24 hours. To receive second chance emails, this option must also be activated within your MultiSafepay account, otherwise it will not work.', 'multisafepay' ),
                    'callback'     => '',
                    'setting_type' => 'boolean',
                    'sort_order'   => 50,
                ),
                array(
                    'id'           => 'multisafepay_time_unit',
                    'label'        => __( 'Unit lifetime of payment link', 'multisafepay' ),
                    'description'  => __( 'The lifetime of a payment link by default is 30 days. This means that the customer has 30 days to complete the transaction using the payment link', 'multisafepay' ),
                    'type'         => 'select',
                    'options'      => array(
                        'days'    => __( 'Days', 'multisafepay' ),
                        'hours'   => __( 'Hours', 'multisafepay' ),
                        'seconds' => __( 'Seconds', 'multisafepay' ),
                    ),
                    'default'      => 'days',
                    'placeholder'  => __( 'Unit lifetime of payment link', 'multisafepay' ),
                    'tooltip'      => '',
                    'callback'     => '',
                    'setting_type' => 'string',
                    'sort_order'   => 40,
                ),
                array(
                    'id'           => 'multisafepay_payment_component_template_id',
                    'label'        => __( 'Payment Component Template ID', 'multisafepay' ),
                    'description'  => __( 'If empty, the default one will be used', 'multisafepay' ),
                    'type'         => 'text',
                    'default'      => '',
                    'placeholder'  => __( 'Payment Component Template ID', 'multisafepay' ),
                    'tooltip'      => '',
                    'callback'     => '',
                    'setting_type' => 'string',
                    'sort_order'   => 55,
                ),
            ),
        );
    }

    /**
     * Return the settings fields for order status section
     *
     * @return  array
     */
    private function get_settings_order_status(): array {
        $wc_order_statuses = $this->get_wc_get_order_statuses();

        // Complete status is manage by $order->complete_payment() in the notification;
        // but still is important get a default value for this in case is required somewhere else as a fallback.
        $multisafepay_order_statuses = $this->get_multisafepay_order_statuses();
        unset( $multisafepay_order_statuses['completed_status'] );

        $order_status_fields = array();
        $sort_order          = 1;
        foreach ( $multisafepay_order_statuses as $key => $multisafepay_order_status ) {
            $order_status_fields[] = array(
                'id'           => 'multisafepay_' . $key,
                'label'        => $multisafepay_order_status['label'],
                'description'  => '',
                'type'         => 'select',
                'options'      => $wc_order_statuses,
                'default'      => $multisafepay_order_status['default'],
                'placeholder'  => __( 'Select order status', 'multisafepay' ),
                'tooltip'      => '',
                'callback'     => '',
                'setting_type' => 'string',
                'sort_order'   => $sort_order,
            );
            $sort_order++;
        }

        return array(
            'title'  => '',
            'intro'  => '',
            'fields' => $order_status_fields,
        );
    }


    /**
     * Returns the WooCommerce registered order statuses
     *
     * @see     http://hookr.io/functions/wc_get_order_statuses/
     *
     * @return  array
     */
    private function get_wc_get_order_statuses(): array {
        $order_statuses = wc_get_order_statuses();
        return $order_statuses;
    }

    /**
     * Returns the MultiSafepay order statuses to create settings fields
     * and match them with WooCommerce order statuses
     *
     * @return  array
     */
    public static function get_multisafepay_order_statuses(): array {
        return array(
            'initialized_status' => array(
                'label'   => __( 'Initialized', 'multisafepay' ),
                'default' => 'wc-pending',
            ),
            'completed_status'   => array(
                'label'   => __( 'Completed', 'multisafepay' ),
                'default' => 'wc-processing',
            ),
            'uncleared_status'   => array(
                'label'   => __( 'Uncleared', 'multisafepay' ),
                'default' => 'wc-on-hold',
            ),
            'reserved_status'    => array(
                'label'   => __( 'Reserved', 'multisafepay' ),
                'default' => 'wc-on-hold',
            ),
            'void_status'        => array(
                'label'   => __( 'Void', 'multisafepay' ),
                'default' => 'wc-cancelled',
            ),
            'declined_status'    => array(
                'label'   => __( 'Declined', 'multisafepay' ),
                'default' => 'wc-failed',
            ),
            'expired_status'     => array(
                'label'   => __( 'Expired', 'multisafepay' ),
                'default' => 'wc-cancelled',
            ),
            'shipped_status'     => array(
                'label'   => __( 'Shipped', 'multisafepay' ),
                'default' => 'wc-completed',
            ),
            'refunded_status'    => array(
                'label'   => __( 'Refunded', 'multisafepay' ),
                'default' => 'wc-refunded',
            ),
            'cancelled_status'   => array(
                'label'   => __( 'Cancelled', 'multisafepay' ),
                'default' => 'wc-cancelled',
            ),
            'chargedback_status' => array(
                'label'   => __( 'Chargedback', 'multisafepay' ),
                'default' => 'wc-on-hold',
            ),
        );
    }
}
