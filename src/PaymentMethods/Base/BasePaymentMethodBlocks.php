<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\Exception\InvalidDataInitializationException;
use MultiSafepay\WooCommerce\Services\PaymentMethodService;

/**
 * Class BasePaymentMethodBlocks
 *
 * @package MultiSafepay\WooCommerce\PaymentMethods\Base
 */
final class BasePaymentMethodBlocks extends AbstractPaymentMethodType {

    /**
     *  Payment methods.
     *
     * @var array
     */
    private $gateways = array();

    /**
     * Payment method name.
     *
     * @var string
     */
    protected $name = 'multisafepay';

    /**
     * Initializes the array of payment methods
     *
     * @return void
     * @throws InvalidDataInitializationException
     */
    public function initialize(): void {
        static $was_printed = false;

        if ( ! $was_printed ) {
            $payment_method_service       = new PaymentMethodService();
            $multisafepay_payment_methods = $payment_method_service->get_multisafepay_payment_methods_from_api();
            foreach ( $multisafepay_payment_methods as $multisafepay_payment_method ) {
                $woocommerce_payment_gateways = array();

                if ( isset( $multisafepay_payment_method['type'] ) && ( 'coupon' === $multisafepay_payment_method['type'] ) ) {
                    $woocommerce_payment_gateways[] = new BaseGiftCardPaymentMethod( new PaymentMethod( $multisafepay_payment_method ) );
                }

                if ( isset( $multisafepay_payment_method['type'] ) && ( 'payment-method' === $multisafepay_payment_method['type'] ) ) {
                    $woocommerce_payment_gateways[] = new BasePaymentMethod( new PaymentMethod( $multisafepay_payment_method ) );
                    foreach ( $multisafepay_payment_method['brands'] as $brand ) {
                        if ( ! empty( $brand['allowed_countries'] ) ) {
                            $woocommerce_payment_gateways[] = new BaseBrandedPaymentMethod( new PaymentMethod( $multisafepay_payment_method ), $brand );
                        }
                    }
                }

                foreach ( $woocommerce_payment_gateways as $woocommerce_payment_gateway ) {
                    // Include direct payment methods without components just in the checkout page of the frontend context
                    if (
                        $woocommerce_payment_gateway->check_direct_payment_methods_without_components() &&
                        ! $woocommerce_payment_gateway->admin_editing_checkout_page()
                    ) {
                        $this->gateways[] = $woocommerce_payment_gateway;
                    }

                    if ( ( 'redirect' === $woocommerce_payment_gateway->get_payment_method_type() ) && $woocommerce_payment_gateway->is_available() ) {
                        $this->gateways[] = $woocommerce_payment_gateway;
                    }
                }
            }
            $was_printed = true;
        }
    }

    /**
     * Returns an array of script handles to enqueue for
     * this payment method in the frontend context
     *
     * @return string[]
     */
    public function get_payment_method_script_handles(): array {
        static $was_printed = false;

        if ( ! $was_printed ) {
            $asset_path   = MULTISAFEPAY_PLUGIN_DIR_PATH . '/assets/public/js/multisafepay-blocks/build/index.asset.php';
            $dependencies = array();

            if ( is_file( $asset_path ) ) {
                $asset        = require $asset_path;
                $dependencies = is_array( $asset ) && isset( $asset['dependencies'] ) ? $asset['dependencies'] : $dependencies;
            }

            wp_register_script(
                'multisafepay-payment-methods-blocks',
                MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-blocks/build/index.js',
                $dependencies,
                MULTISAFEPAY_PLUGIN_VERSION,
                true
            );

            wp_localize_script( 'multisafepay-payment-methods-blocks', 'multisafepay_gateways', $this->get_payment_method_data() );
            $was_printed = true;
        }

        return array( 'multisafepay-payment-methods-blocks' );
    }

    /**
     * Returns an array of key=>value pairs of data
     * made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data(): array {
        $payment_methods_data = array();
        foreach ( $this->gateways as $gateway ) {
            $payment_methods_data[] = array(
                'id'          => $gateway->get_payment_method_id(),
                'title'       => $gateway->get_title(),
                'description' => $gateway->get_description(),
                'is_admin'    => is_admin(),
            );
        }

        return $payment_methods_data;
    }
}
