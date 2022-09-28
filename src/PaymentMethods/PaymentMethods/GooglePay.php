<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;

class GooglePay extends BasePaymentMethod {

    /**
     * ApplePay constructor.
     */
    public function __construct() {
        parent::__construct();
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
    }

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_googlepay';
    }

    /**
     * @return string
     */
    public function get_payment_method_code(): string {
        return 'GOOGLEPAY';
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
        return 'Google Pay';
    }

    /**
     * @return string
     */
    public function get_payment_method_description(): string {
        $method_description = sprintf(
        /* translators: %2$: The payment method title */
            __( 'Accept payments using Google Pay. <br />Read more about <a href="%1$s" target="_blank">%2$s</a> on MultiSafepay\'s Documentation Center.', 'multisafepay' ),
            'https://docs.multisafepay.com',
            $this->get_payment_method_title()
        );
        return $method_description;
    }

    /**
     * @return string
     */
    public function get_payment_method_icon(): string {
        return 'googlepay.png';
    }

    /**
     * Enqueue Javascript to check if browser supports Google Pay
     *
     * @return void
     */
    public function enqueue_script(): void {
        if ( is_checkout() ) {
            wp_enqueue_script( 'multisafepay-google-pay-js', MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-google-pay.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
            wp_enqueue_script( 'google-pay-js', 'https://pay.google.com/gp/p/js/pay.js', array( 'jquery' ), MULTISAFEPAY_PLUGIN_VERSION, true );
        }
    }

}
