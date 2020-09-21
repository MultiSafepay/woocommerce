<?php


namespace MultiSafepay\WooCommerce\PaymentMethods;

use WC_Payment_Gateway;


abstract class Core extends WC_Payment_Gateway implements PaymentMethodInterface
{
    /**
     * Class constructor, more about it in Step 3
     */
    public function __construct()
    {
        $this->supports = ['products'];

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function setMethodDescription(string $value = '')
    {
        $this->method_description = $value;
    }

    /**
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => 'Enable/Disable',
                'label' => 'Enable ' . $this->get_method_title() . ' Gateway',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ],
            'title' => [
                'title' => 'Title',
                'type' => 'text',
                'default' => $this->get_method_title(),
            ]
        ];
    }

    public function setId(string $value)
    {
        $this->id = $value;
        return $this;
    }

    public function setMethodTitle(string $value)
    {
        $this->method_title = $value;
        return $this;
    }

    public function process_payment($order_id)
    {

    }

}
