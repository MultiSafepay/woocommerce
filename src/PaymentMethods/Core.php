<?php declare(strict_types=1);


namespace MultiSafepay\WooCommerce\PaymentMethods;

use WC_Payment_Gateway;


abstract class Core extends WC_Payment_Gateway implements PaymentMethodInterface
{
    /**
     * Core constructor.
     */
    public function __construct()
    {
        $this->supports = ['products'];

        // Method with all the options fields
        $this->addFormFields();

        // Load the settings.
        $this->init_settings();
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * @param string $value
     * @return mixed|void
     */
    public function setMethodDescription(string $value = '')
    {
        $this->method_description = $value;
    }

    /**
     * @return mixed|void
     */
    public function addFormFields()
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

    /**
     * @param string $value
     * @return $this|mixed
     */
    public function setId(string $value)
    {
        $this->id = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this|mixed
     */
    public function setMethodTitle(string $value)
    {
        $this->method_title = $value;
        return $this;
    }

    /**
     * @param $order_id
     * @return array|mixed|void
     */
    public function process_payment($order_id)
    {

    }

}
