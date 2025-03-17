<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Blocks;

use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethodBlocks;

/**
 * Defines all the methods needed to register the MultiSafepay payment methods in WooCommerce checkout block.
 */
class BlocksController {

    /**
     * Register the MultiSafepay payment methods in WooCommerce Blocks.
     *
     * @return void
     */
    public function register_multisafepay_payment_methods_blocks(): void {
        if ( class_exists( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry::class ) ) {
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function ( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                    $payment_method_registry->register( new BasePaymentMethodBlocks() );
                }
            );
        }
    }
}
