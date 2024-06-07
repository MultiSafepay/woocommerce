<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;

/**
 * Class PaymentComponentService
 *
 * @package MultiSafepay\WooCommerce\Services
 */
class PaymentComponentService {

    /**
     * @var SdkService
     */
    public $sdk_service;

    /**
     * @var ApiTokenService
     */
    public $api_token_service;

    /**
     * @var PaymentMethodService
     */
    public $payment_method_service;

    /**
     * ApiTokenService constructor.
     */
    public function __construct() {
        $this->sdk_service            = new SdkService();
        $this->api_token_service      = new ApiTokenService();
        $this->payment_method_service = new PaymentMethodService();
    }

    /**
     * Return the arguments required when payment component needs to be initialized
     *
     * @param BasePaymentMethod $woocommerce_payment_gateway
     * @return array
     */
    public function get_payment_component_arguments( BasePaymentMethod $woocommerce_payment_gateway ): array {
        $payment_component_arguments = array(
            'debug'     => (bool) get_option( 'multisafepay_debugmode', false ),
            'env'       => $this->sdk_service->get_test_mode() ? 'test' : 'live',
            'ajax_url'  => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'payment_component_arguments_nonce' ),
            'api_token' => $this->api_token_service->get_api_token(),
            'orderData' => array(
                'currency'        => get_woocommerce_currency(),
                'amount'          => ( $this->get_total_amount() * 100 ),
                'customer'        => array(
                    'locale'  => strtoupper( substr( ( new CustomerService() )->get_locale(), 0, 2 ) ),
                    'country' => ( WC()->customer )->get_billing_country(),
                ),
                'payment_options' => array(
                    'template' => array(
                        'settings' => array(
                            'embed_mode' => 1,
                        ),
                        'merge'    => true,
                    ),
                    'settings' => array(
                        'connect' => array(
                            'issuers_display_mode' => 'select',
                        ),
                    ),
                ),
            ),
            'gateway'   => $woocommerce_payment_gateway->get_payment_method_gateway_code(),
        );

        // Payment Component Template ID.
        $template_id = get_option( 'multisafepay_payment_component_template_id', false );
        if ( ! empty( $template_id ) ) {
            $payment_component_arguments['orderData']['payment_options']['template_id'] = $template_id;
        }

        // Tokenization and recurring model
        if ( $woocommerce_payment_gateway->is_tokenization_enabled() ) {
            $payment_component_arguments['recurring'] = array(
                'model'  => 'cardOnFile',
                'tokens' => $this->sdk_service->get_payment_tokens(
                    (string) get_current_user_id(),
                    sanitize_text_field( $woocommerce_payment_gateway->get_payment_method_gateway_code() )
                ),
            );
        }

        return $payment_component_arguments;
    }

    /**
     * Return the arguments required when payment component needs to be initialized via a WP AJAX request
     *
     * @return void
     */
    public function ajax_get_payment_component_arguments() {
        $payment_component_arguments_nonce = sanitize_key( $_POST['nonce'] ?? '' );
        if ( ! wp_verify_nonce( wp_unslash( $payment_component_arguments_nonce ), 'payment_component_arguments_nonce' ) ) {
            wp_send_json( array() );
        }
        $gateway_id                  = sanitize_key( $_POST['gateway_id'] ?? '' );
        $woocommerce_payment_gateway = $this->payment_method_service->get_woocommerce_payment_gateway_by_id( $gateway_id );
        $payment_component_arguments = $this->get_payment_component_arguments( $woocommerce_payment_gateway );
        wp_send_json( $payment_component_arguments );
    }

    /**
     * Return the total amount of the cart or order
     *
     * @return float
     */
    private function get_total_amount(): float {
        $total_amount = ( WC()->cart ) ? (float) WC()->cart->get_total( '' ) : null;

        if ( is_wc_endpoint_url( 'order-pay' ) ) {
            $order_id = absint( get_query_var( 'order-pay' ) );
            if ( 0 < $order_id ) {
                $order = wc_get_order( $order_id );
                if ( $order ) {
                    $total_amount = (float) $order->get_total();
                }
            }
        }

        return $total_amount;
    }
}
