<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Blocks;

use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use MultiSafepay\Exception\InvalidDataInitializationException;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethodBlocks;
use MultiSafepay\WooCommerce\Services\BlocksPaymentDataService;
use MultiSafepay\WooCommerce\Utils\Logger;
use WP_Post;

/**
 * Defines all the methods needed to register the MultiSafepay payment methods in WooCommerce checkout block.
 */
class BlocksController {

    /**
     * Map MultiSafepay Blocks payment component data into order meta.
     *
     * @param PaymentContext $context Holds context for the payment.
     * @param PaymentResult  $result  Result of the payment.
     * @return void
     */
    public function map_blocks_payment_data_to_order_meta( PaymentContext $context, PaymentResult $result ): void {
        ( new BlocksPaymentDataService() )->save_blocks_payment_data_to_order_meta( $context, $result );
    }

    /**
     * Add MultiSafepay payment method script dependency for blocks.
     *
     * @param array  $dependencies Script dependencies.
     * @param string $handle Script handle.
     *
     * @return array
     */
    public function add_multisafepay_block_dependencies( array $dependencies, string $handle ): array {
        $supported_handles = array( 'wc-checkout-block', 'wc-checkout-block-frontend', 'wc-cart-block', 'wc-cart-block-frontend' );

        if ( ! in_array( $handle, $supported_handles, true ) ) {
            return $dependencies;
        }

        static $script_handles = null;

        if ( null === $script_handles ) {
            try {
                $payment_method_blocks = new BasePaymentMethodBlocks();
                $script_handles        = is_admin()
                    ? $payment_method_blocks->get_payment_method_script_handles_for_admin()
                    : $payment_method_blocks->get_payment_method_script_handles();
            } catch ( InvalidDataInitializationException $invalid_data_initialization_exception ) {
                ( new Logger() )->log_warning(
                    'Failed to load MultiSafepay Blocks script handles: ' . $invalid_data_initialization_exception->getMessage()
                );

                return $dependencies;
            }
        }
        $dependencies = array_merge( $dependencies, $script_handles );

        return array_values( array_unique( $dependencies ) );
    }

    /**
     * Enqueue MultiSafepay assets in the Checkout block editor.
     *
     * @return void
     * @throws InvalidDataInitializationException
     */
    public function enqueue_checkout_block_editor_assets(): void {
        if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
            return;
        }

        $screen = get_current_screen();

        if ( ! $screen || ! $screen->is_block_editor() ) {
            return;
        }

        global $post;
        $post_id     = ( $post instanceof WP_Post ) ? (int) $post->ID : 0;
        $checkout_id = (int) get_option( 'woocommerce_checkout_page_id', 0 );

        if ( ! $post_id || $post_id !== $checkout_id ) {
            return;
        }

        try {
            $payment_method_blocks = new BasePaymentMethodBlocks();
            $handles               = $payment_method_blocks->get_payment_method_script_handles_for_admin();

            foreach ( $handles as $handle ) {
                wp_enqueue_script( $handle );
            }
        } catch ( InvalidDataInitializationException $invalid_data_initialization_exception ) {
            ( new Logger() )->log_warning(
                'Failed to enqueue MultiSafepay Blocks assets in editor: ' . $invalid_data_initialization_exception->getMessage()
            );
        }
    }

    /**
     * Register the MultiSafepay payment methods in WooCommerce Blocks.
     *
     * @return void
     */
    public function register_multisafepay_payment_methods_blocks(): void {
        static $registered = false;

        if ( $registered ) {
            return;
        }

        if ( class_exists( PaymentMethodRegistry::class ) ) {
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function ( PaymentMethodRegistry $payment_method_registry ) {
                    $payment_method_registry->register( new BasePaymentMethodBlocks() );
                }
            );

            add_filter(
                'woocommerce_blocks_register_script_dependencies',
                array( $this, 'add_multisafepay_block_dependencies' ),
                10,
                2
            );

            add_action(
                'enqueue_block_editor_assets',
                array( $this, 'enqueue_checkout_block_editor_assets' )
            );

            $registered = true;
        }
    }
}
