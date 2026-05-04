<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\Exception\InvalidDataInitializationException;
use MultiSafepay\WooCommerce\Services\PaymentComponentService;
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
     * Track initialization to avoid duplicate API calls.
     *
     * @var bool
     */
    private $initialized = false;

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
        if ( $this->initialized ) {
            return;
        }

        $payment_method_service       = new PaymentMethodService();
        $multisafepay_payment_methods = $payment_method_service->get_multisafepay_payment_methods_from_api();
        $gateways                     = array();
        foreach ( $multisafepay_payment_methods as $multisafepay_payment_method ) {
            $woocommerce_payment_gateways = array();

            if ( isset( $multisafepay_payment_method['type'] ) && ( 'coupon' === $multisafepay_payment_method['type'] ) ) {
                $woocommerce_payment_gateways[] = new BaseGiftCardPaymentMethod( new PaymentMethod( $multisafepay_payment_method ) );
            }

            if ( isset( $multisafepay_payment_method['type'] ) && ( 'payment-method' === $multisafepay_payment_method['type'] ) ) {
                $woocommerce_payment_gateways[] = new BasePaymentMethod( new PaymentMethod( $multisafepay_payment_method ) );
                foreach ( $multisafepay_payment_method['brands'] as $brand ) {
                    if ( ! empty( $brand['allowed_countries'] ) && ! get_option( 'multisafepay_group_credit_cards', false ) ) {
                        $brand['id']                   .= '_' . $multisafepay_payment_method['id'];
                        $brand['name']                 .= ' - ' . $multisafepay_payment_method['name'];
                        $woocommerce_payment_gateways[] = new BaseBrandedPaymentMethod( new PaymentMethod( $multisafepay_payment_method ), $brand );
                    }
                }
            }

            foreach ( $woocommerce_payment_gateways as $woocommerce_payment_gateway ) {
                $is_editing_checkout_page = $woocommerce_payment_gateway->admin_editing_checkout_page();

                // Blocks support both redirect and direct flows.
                // In the frontend use runtime availability; in the checkout editor rely on settings.
                $can_be_displayed = $is_editing_checkout_page
                    ? $this->is_gateway_enabled_in_settings( $woocommerce_payment_gateway )
                    : $woocommerce_payment_gateway->is_available();

                if ( $can_be_displayed ) {
                    $gateways[] = $woocommerce_payment_gateway;
                }
            }
        }

        $this->gateways    = $gateways;
        $this->initialized = true;
    }

    /**
     * Checks whether a gateway is enabled in WooCommerce settings.
     *
     * In the Checkout block editor we want to list only gateways that are actively enabled,
     * but without relying on runtime availability checks that depend on cart/customer context.
     *
     * @param BasePaymentMethod $gateway
     *
     * @return bool
     */
    private function is_gateway_enabled_in_settings( BasePaymentMethod $gateway ): bool {
        $settings = get_option( 'woocommerce_' . $gateway->id . '_settings', array() );
        if ( ! is_array( $settings ) || ! isset( $settings['enabled'] ) ) {
            return false;
        }

        return 'yes' === $settings['enabled'];
    }

    /**
     * Returns an array of script handles to enqueue for
     * this payment method in the frontend context
     *
     * @return string[]
     * @throws InvalidDataInitializationException
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
                'multisafepay-payment-component-script',
                BasePaymentMethod::MULTISAFEPAY_COMPONENT_JS_URL,
                array(),
                MULTISAFEPAY_PLUGIN_VERSION,
                true
            );

            wp_enqueue_style(
                'multisafepay-payment-component-style',
                BasePaymentMethod::MULTISAFEPAY_COMPONENT_CSS_URL,
                array(),
                MULTISAFEPAY_PLUGIN_VERSION,
                'all'
            );

            if ( ! in_array( 'multisafepay-payment-component-script', $dependencies, true ) ) {
                $dependencies[] = 'multisafepay-payment-component-script';
            }

            $gateways_data = $this->get_payment_method_data();

            $needs_google_pay_js = false;
            foreach ( $gateways_data as $gateway_data ) {
                if ( ! is_array( $gateway_data ) ) {
                    continue;
                }

                if ( ( $gateway_data['gateway_code'] ?? '' ) !== 'GOOGLEPAY' ) {
                    continue;
                }

                if ( ! empty( $gateway_data['direct_button_enabled'] ) ) {
                    $needs_google_pay_js = true;
                    break;
                }
            }

            if ( $needs_google_pay_js ) {
                // Needed for Google Pay Direct button in Checkout Blocks.
                wp_register_script(
                    'google-pay-js',
                    'https://pay.google.com/gp/p/js/pay.js',
                    array(),
                    MULTISAFEPAY_PLUGIN_VERSION,
                    true
                );

                if ( ! in_array( 'google-pay-js', $dependencies, true ) ) {
                    $dependencies[] = 'google-pay-js';
                }
            }

            wp_register_script(
                'multisafepay-payment-methods-blocks',
                MULTISAFEPAY_PLUGIN_URL . '/assets/public/js/multisafepay-blocks/build/index.js',
                $dependencies,
                MULTISAFEPAY_PLUGIN_VERSION,
                true
            );

            wp_localize_script( 'multisafepay-payment-methods-blocks', 'multisafepay_gateways', $gateways_data );
            $was_printed = true;
        }

        return array( 'multisafepay-payment-methods-blocks' );
    }

    /**
     * Returns an array of script handles to enqueue for this payment method in the admin context.
     *
     * WooCommerce Blocks calls a separate method for the editor context. We alia it to
     * get_payment_method_script_handles(), so the script registration and localization
     * also happen in the Checkout page editor.
     *
     * @return string[]
     * @throws InvalidDataInitializationException
     */
    public function get_payment_method_script_handles_for_admin(): array {
        return $this->get_payment_method_script_handles();
    }

    /**
     * Returns an array of key=>value pairs of data
     * made available to the payment methods script.
     *
     * @return array
     * @throws InvalidDataInitializationException
     */
    public function get_payment_method_data(): array {
        if ( empty( $this->gateways ) ) {
            $this->initialize();
        }

        $payment_methods_data = array();
        $show_icons           = (bool) get_option( 'multisafepay_checkout_block_payment_icons', false );
        foreach ( $this->gateways as $gateway ) {
            $has_payment_component = $gateway->is_payment_component_enabled();
            // In Blocks, avoid blocking page render on remote API calls (api_token, recurring tokens).
            // The frontend will refresh full config via AJAX when needed.
            $payment_component_config = $has_payment_component ? ( new PaymentComponentService() )->get_payment_component_arguments( $gateway, false, false ) : array();

            $gateway_code = $gateway->get_payment_method_gateway_code();

            $direct_button_enabled = false;
            $wallet_config         = array();

            if ( 'APPLEPAY' === $gateway_code ) {
                $direct_button_enabled = ( BasePaymentMethod::TRANSACTION_TYPE_DIRECT === $gateway->get_google_apple_pay_use_button( 'applepay' ) );
                if ( $direct_button_enabled ) {
                    $wallet_config = $gateway->get_applepay_wallet_config() ?? array();
                }
            }

            if ( 'GOOGLEPAY' === $gateway_code ) {
                $direct_button_enabled = ( BasePaymentMethod::TRANSACTION_TYPE_DIRECT === $gateway->get_google_apple_pay_use_button( 'googlepay' ) );
                if ( $direct_button_enabled ) {
                    $wallet_config = $gateway->get_googlepay_wallet_config() ?? array();
                }
            }

            $payment_methods_data[] = array(
                'id'                       => $gateway->get_payment_method_id(),
                'gateway_code'             => $gateway_code,
                'title'                    => $gateway->get_title(),
                'description'              => $gateway->get_description(),
                'icon'                     => $show_icons ? $gateway->get_payment_method_icon() : '',
                'is_admin'                 => is_admin(),
                'supports'                 => $gateway->supports,
                'has_payment_component'    => $has_payment_component,
                'payment_component_config' => $payment_component_config,
                'direct_button_enabled'    => $direct_button_enabled,
                'wallet_config'            => $wallet_config,
                'ajax_url'                 => admin_url( 'admin-ajax.php' ),
                'nonce'                    => wp_create_nonce( 'total_price_nonce' ),
            );
        }

        return $payment_methods_data;
    }
}
