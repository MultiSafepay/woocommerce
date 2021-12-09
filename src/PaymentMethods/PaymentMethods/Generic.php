<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;

class Generic extends BasePaymentMethod {

    /**
     * @var bool
     */
    protected $require_shopping_cart;

    /**
     * @var string
     */
    protected $icon_url;

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_generic';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return $this->get_option( 'payment_method_code' );
    }

    /**
     * @return string
     */
    public function get_payment_method_type(): string {
        return 'redirect';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return __( 'Generic Gateway', 'multisafepay' );
    }

    /**
     * Generic constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->icon                  = $this->get_option( 'icon_url', '' );
        $this->require_shopping_cart = ( 'yes' === strtolower( $this->get_option( 'require_shopping_cart', 'no' ) ) );
    }

    /**
     * Process the payment and return the result.
     *
     * @param integer $order_id Order ID.
     *
     * @return  array|mixed|void
     */
    public function process_payment( $order_id ): array {
        update_post_meta( $order_id, 'order_require_shopping_cart', $this->require_shopping_cart );
        return parent::process_payment( $order_id );
    }

    /**
     * Define the form option - settings fields.
     *
     * @return  array
     */
    public function add_form_fields(): array {
        $form_fields = parent::add_form_fields();

        $generic_form_fields = array(
            'payment_method_code'   => array(
                'title'    => __( 'Gateway code', 'multisafepay' ),
                'desc_tip' => __( 'The gateway code of the payment method or gift card you would like to use.', 'multisafepay' ),
                'type'     => 'text',
            ),
            'require_shopping_cart' => array(
                'title'    => __( 'Require shopping cart', 'multisafepay' ),
                'label'    => __( 'Require shopping cart for this payment method.', 'multisafepay' ),
                'desc_tip' => __( 'Enable this option of the payment method or gift card you would like to use, require the shopping cart in the refund request.', 'multisafepay' ),
                'type'     => 'checkbox',
                'default'  => 'no',
            ),
            'icon_url'              => array(
                'title'       => __( 'Icon', 'multisafepay' ),
                'description' => __( 'Save the icon url in this field. Icons can be uploaded in: Media -> Library -> Add new.', 'multisafepay' ),
                'type'        => 'text',
            ),
        );

        return array_merge( $form_fields, $generic_form_fields );

    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        $method_description = sprintf(
        /* translators: %2$: The payment method title */
            __(
                'This generic payment method gives you the option to add your own MultiSafepay supported payment method. <br />Read more about <a href="%1$s" target="_blank">%2$s</a> on MultiSafepay\'s Documentation Center.',
                'multisafepay'
            ),
            'https://docs.multisafepay.com/integrations/ecommerce-integrations/woocommerce/faq/generic-gateways/?utm_source=woocommerce&utm_medium=woocommerce-cms&utm_campaign=woocommerce-cms',
            $this->get_payment_method_title()
        );

        return $method_description;
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return '';
    }

}
