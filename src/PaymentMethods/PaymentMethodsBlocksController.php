<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods;

/**
 * Class PaymentMethodsBlocksController
 *
 * @package MultiSafepay\WooCommerce\PaymentMethods
 */
class PaymentMethodsBlocksController {
    const WOOCOMMERCE_BLOCKS_PLUGIN_NAME = 'woo-gutenberg-products-block/woocommerce-gutenberg-products-block.php';

    /**
     * @return bool
     */
    public function is_blocks_plugin_active(): bool {
        return is_plugin_active( self::WOOCOMMERCE_BLOCKS_PLUGIN_NAME );
    }

    /**
     * Enqueue Javascript related with WooCommerce Blocks
     *
     * @return void
     */
    public function enqueue_woocommerce_blocks_script(): void {
        $gateways        = $this->get_blocks_payment_methods();
        $blocks_js_route = MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-blocks.js';
        wp_enqueue_script( 'multisafepay-blocks-js', $blocks_js_route, array( 'wc-blocks-registry' ), MULTISAFEPAY_PLUGIN_VERSION, true );
        wp_localize_script( 'multisafepay-blocks-js', 'multisafepay_gateways', $gateways );
    }

    /**
     * @return array
     */
    private function get_blocks_payment_methods(): array {
        $payment_methods_data = array();
        $gateways             = WC()->payment_gateways()->get_available_payment_gateways();
        foreach ( $gateways as $gateway_id => $gateway ) {
            if ( ! str_contains( $gateway_id, 'multisafepay' ) ) {
                continue;
            }

            if ( $gateway->get_payment_method_type() !== 'redirect' ) {
                continue;
            }

            $payment_methods_data[] = array(
                'paymentMethodId' => $gateway_id,
                'title'           => $gateway->method_title,
                'description'     => $gateway->description,
            );
        }

        return $payment_methods_data;
    }

}
