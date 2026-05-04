<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Blocks\BlocksController;

/**
 * Covers the WooCommerce Blocks integration controller.
 *
 * It registers the MultiSafepay Blocks payment method integration and ensures the
 * required scripts are added as dependencies for Checkout/Cart blocks.
 *
 * @covers \MultiSafepay\WooCommerce\Blocks\BlocksController
 */
class Test_BlocksController extends WP_UnitTestCase {

    /**
     * Simulates an admin and block editor screen without relying on real wp-admin bootstrapping.
     *
     *
     * @return void
     */
    private function set_fake_current_screen(): void
    {
        $screen = new class(true) {
            /** @var bool */
            private $is_block_editor;

            public function __construct( bool $is_block_editor ) {
                $this->is_block_editor = $is_block_editor;
            }

            public function in_admin(): bool {
                return true;
            }

            public function is_block_editor(): bool {
                return $this->is_block_editor;
            }
        };

        $GLOBALS['current_screen'] = $screen;

    }

    /**
     * Leaves dependencies unchanged for unrelated script handles.
     *
     * @covers \MultiSafepay\WooCommerce\Blocks\BlocksController::add_multisafepay_block_dependencies
     */
    public function test_add_multisafepay_block_dependencies_ignores_unknown_handle(): void {
        $controller   = new BlocksController();
        $dependencies = array( 'wp-element' );

        $this->assertSame(
            $dependencies,
            $controller->add_multisafepay_block_dependencies( $dependencies, 'some-other-handle' )
        );
    }

    /**
     * Adds MultiSafepay Blocks script handles for supported block handles.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\Blocks\BlocksController::add_multisafepay_block_dependencies
     */
    public function test_add_multisafepay_block_dependencies_adds_multisafepay_handles(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        // Seed a cached API response so initialization does not attempt any external calls.
        set_transient(
            'multisafepay_payment_methods',
            array(
                array(
                    'id'     => 'AMEX',
                    'name'   => 'American Express',
                    'type'   => 'payment-method',
                    'brands' => array(),
                ),
            ),
            60
        );

        $controller = new BlocksController();
        $deps       = $controller->add_multisafepay_block_dependencies( array(), 'wc-checkout-block' );

        $this->assertContains( 'multisafepay-payment-methods-blocks', $deps );
        $this->assertTrue( wp_script_is( 'multisafepay-payment-methods-blocks', 'registered' ) );
    }

    /**
     * Uses the admin script handles branch when running inside wp-admin.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\Blocks\BlocksController::add_multisafepay_block_dependencies
     */
    public function test_add_multisafepay_block_dependencies_uses_admin_handles_when_admin(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        $this->set_fake_current_screen();

        // Seed a cached API response so initialization does not attempt any external calls.
        set_transient(
            'multisafepay_payment_methods',
            array(
                array(
                    'id'     => 'AMEX',
                    'name'   => 'American Express',
                    'type'   => 'payment-method',
                    'brands' => array(),
                ),
            ),
            60
        );

        $controller = new BlocksController();
        $deps       = $controller->add_multisafepay_block_dependencies( array(), 'wc-checkout-block' );

        $this->assertContains( 'multisafepay-payment-methods-blocks', $deps );
        $this->assertTrue( wp_script_is( 'multisafepay-payment-methods-blocks', 'registered' ) );
    }

    /**
     * Adds dependencies for each supported Blocks handle.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\Blocks\BlocksController::add_multisafepay_block_dependencies
     */
    public function test_add_multisafepay_block_dependencies_adds_dependencies_for_each_supported_handle(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        set_transient(
            'multisafepay_payment_methods',
            array(
                array(
                    'id'     => 'AMEX',
                    'name'   => 'American Express',
                    'type'   => 'payment-method',
                    'brands' => array(),
                ),
            ),
            60
        );

        $controller = new BlocksController();

        $first = $controller->add_multisafepay_block_dependencies( array(), 'wc-checkout-block' );
        $this->assertContains( 'multisafepay-payment-methods-blocks', $first );

        $second = $controller->add_multisafepay_block_dependencies( array(), 'wc-cart-block' );
        $this->assertContains( 'multisafepay-payment-methods-blocks', $second );
    }

    /**
     * Enqueues scripts in the Checkout block editor when editing the Checkout page.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\Blocks\BlocksController::enqueue_checkout_block_editor_assets
     */
    public function test_enqueue_checkout_block_editor_assets_enqueues_when_checkout_page_in_block_editor(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        $this->set_fake_current_screen();

        // Seed a cached API response so initialization does not attempt any external calls.
        set_transient(
            'multisafepay_payment_methods',
            array(
                array(
                    'id'     => 'AMEX',
                    'name'   => 'American Express',
                    'type'   => 'payment-method',
                    'brands' => array(),
                ),
            ),
            60
        );

        $checkout_post_id = (int) $this->factory->post->create(
            array(
                'post_title'  => 'Checkout',
                'post_status' => 'publish',
                'post_type'   => 'page',
            )
        );

        update_option( 'woocommerce_checkout_page_id', $checkout_post_id );

        global $post;
        $post = get_post( $checkout_post_id );

        $controller = new BlocksController();
        $controller->enqueue_checkout_block_editor_assets();

        $this->assertTrue( wp_script_is( 'multisafepay-payment-methods-blocks' ) );
    }

    /**
     * Does not enqueue when not editing the configured Checkout page.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\Blocks\BlocksController::enqueue_checkout_block_editor_assets
     */
    public function test_enqueue_checkout_block_editor_assets_bails_when_not_checkout_page(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        $this->set_fake_current_screen();

        $checkout_post_id = (int) $this->factory->post->create( array( 'post_type' => 'page' ) );
        $other_post_id    = (int) $this->factory->post->create( array( 'post_type' => 'page' ) );

        update_option( 'woocommerce_checkout_page_id', $checkout_post_id );

        global $post;
        $post = get_post( $other_post_id );

        $controller = new BlocksController();
        $controller->enqueue_checkout_block_editor_assets();

        $this->assertFalse( wp_script_is( 'multisafepay-payment-methods-blocks' ) );
    }

    /**
     * Registers hooks for Blocks payment method registration when Blocks is available.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\Blocks\BlocksController::register_multisafepay_payment_methods_blocks
     */
    public function test_register_multisafepay_payment_methods_blocks_registers_hooks(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\PaymentMethodRegistry' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        $controller = new BlocksController();
        $controller->register_multisafepay_payment_methods_blocks();

        $this->assertNotFalse( has_action( 'woocommerce_blocks_payment_method_type_registration' ) );
        $this->assertNotFalse( has_filter( 'woocommerce_blocks_register_script_dependencies' ) );
        $this->assertNotFalse( has_action( 'enqueue_block_editor_assets' ) );
    }

    /**
     * Covers the early-return guard when registration already happened.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\Blocks\BlocksController::register_multisafepay_payment_methods_blocks
     */
    public function test_register_multisafepay_payment_methods_blocks_is_idempotent(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\PaymentMethodRegistry' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        $controller = new BlocksController();
        $controller->register_multisafepay_payment_methods_blocks();
        $controller->register_multisafepay_payment_methods_blocks();

        $this->assertNotFalse( has_action( 'woocommerce_blocks_payment_method_type_registration' ) );
    }
}
