<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Settings;

/**
 * Defines all the methods to declare compatibility with third party plugins
 *
 * Class ThirdPartyCompatibility
 *
 * @package MultiSafepay\WooCommerce\Settings
 */
class ThirdPartyCompatibility {
    /**
     *  Declare all the compatibilities with third party plugins
     *
     * @return void
     */
    public function declare_all_compatibilities(): void {
        $this->declare_hpos_compatibility();
        $this->declare_blocks_compatibility();
    }

    /**
     * Declare compatibility with high performance order storage features
     *
     * @return void
     */
    public function declare_hpos_compatibility(): void {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                MULTISAFEPAY_PLUGIN_DIR_PATH . 'multisafepay.php'
            );
        }
    }

    /**
     * Declare compatibility with block-based checkout features
     *
     * @return void
     */
    public function declare_blocks_compatibility(): void {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                MULTISAFEPAY_PLUGIN_DIR_PATH . 'multisafepay.php'
            );
        }
    }
}
