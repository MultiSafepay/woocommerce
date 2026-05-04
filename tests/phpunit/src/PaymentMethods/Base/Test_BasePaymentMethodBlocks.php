<?php declare(strict_types=1);

use MultiSafepay\Exception\InvalidDataInitializationException;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethodBlocks;
use MultiSafepay\WooCommerce\Tests\Fixtures\PaymentMethodFixture;

/**
 * Covers the WooCommerce Blocks payment method integration wrapper.
 *
 * It ensures the Blocks integration registers scripts/styles and exposes gateway data
 * for the frontend Checkout block without requiring external API calls.
 *
 * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethodBlocks
 */
class Test_BasePaymentMethodBlocks extends WP_UnitTestCase {

    /**
     * Creates an asset file so get_payment_method_script_handles() loads dependencies from disk.
     *
     *
     * @return string Full path to the created asset file.
     */
    private function create_blocks_asset_file(): string {
        $dependencies = array('some-dep');
        $asset_path = MULTISAFEPAY_PLUGIN_DIR_PATH . '/assets/public/js/multisafepay-blocks/build/index.asset.php';
        $asset_dir  = dirname( $asset_path );

        if ( ! is_dir( $asset_dir ) ) {
            wp_mkdir_p( $asset_dir );
        }

        $content = "<?php\nreturn array(\n\t'dependencies' => " . var_export( array_values( $dependencies ), true ) . ",\n\t'version' => 'test',\n);\n";
        file_put_contents( $asset_path, $content );

        return $asset_path;
    }

    /**
     * Deletes the created asset file (best-effort).
     *
     * @param string $asset_path Asset file path.
     *
     * @return void
     */
    private function delete_blocks_asset_file( string $asset_path ): void {
        if ( is_file( $asset_path ) ) {
            unlink( $asset_path );
        }
    }

    /**
     * Registers required scripts/styles and returns the Blocks script handle.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethodBlocks::get_payment_method_script_handles
     * @throws InvalidDataInitializationException
     */
    public function test_get_payment_method_script_handles_registers_assets(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        // Create an asset file so the dependency-loading branch is covered.
        $asset_path = $this->create_blocks_asset_file();

        $gateway = $this->getMockBuilder( '\\MultiSafepay\\WooCommerce\\PaymentMethods\\Base\\BasePaymentMethod' )
            ->disableOriginalConstructor()
            ->onlyMethods( array( 'is_payment_component_enabled', 'get_payment_method_id', 'get_payment_method_gateway_code', 'get_title', 'get_description', 'get_payment_method_icon' ) )
            ->getMock();

        $gateway->method( 'is_payment_component_enabled' )->willReturn( false );
        $gateway->method( 'get_payment_method_id' )->willReturn( 'multisafepay_amex' );
        $gateway->method( 'get_payment_method_gateway_code' )->willReturn( 'AMEX' );
        $gateway->method( 'get_title' )->willReturn( 'AMEX' );
        $gateway->method( 'get_description' )->willReturn( 'Desc' );
        $gateway->method( 'get_payment_method_icon' )->willReturn( 'icon.svg' );

        $blocks = new BasePaymentMethodBlocks();

        // Avoid initialize() by injecting a known gateway list.
        $reflection = new ReflectionClass( BasePaymentMethodBlocks::class );
        $property   = $reflection->getProperty( 'gateways' );
        $property->setAccessible( true );
        $property->setValue( $blocks, array( $gateway ) );

        $handles = $blocks->get_payment_method_script_handles();

        $this->assertSame( array( 'multisafepay-payment-methods-blocks' ), $handles );
        $this->assertTrue( wp_script_is( 'multisafepay-payment-methods-blocks', 'registered' ) );
        $this->assertTrue( wp_script_is( 'multisafepay-payment-component-script', 'registered' ) );
        $this->assertTrue( wp_style_is( 'multisafepay-payment-component-style' ) );

        $scripts = wp_scripts();
        $data    = $scripts->get_data( 'multisafepay-payment-methods-blocks', 'data' );
        $this->assertIsString( $data );
        $this->assertStringContainsString( 'multisafepay_gateways', $data );

        $registered = $scripts->registered['multisafepay-payment-methods-blocks'] ?? null;
        $this->assertNotNull( $registered );
        $this->assertContains( 'some-dep', $registered->deps );
        $this->assertContains( 'multisafepay-payment-component-script', $registered->deps );
        $this->assertNotContains( 'wc-blocks', $registered->deps );

        $this->delete_blocks_asset_file( $asset_path );
    }

    /**
     * Maps configured gateways to the JS-facing data structure and respects the icon toggle.
     *
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethodBlocks::get_payment_method_data
     * @throws InvalidDataInitializationException
     */
    public function test_get_payment_method_data_maps_gateways_and_icon_setting(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        $gateway = $this->getMockBuilder( '\\MultiSafepay\\WooCommerce\\PaymentMethods\\Base\\BasePaymentMethod' )
            ->disableOriginalConstructor()
            ->onlyMethods( array( 'is_payment_component_enabled', 'get_payment_method_id', 'get_payment_method_gateway_code', 'get_title', 'get_description', 'get_payment_method_icon' ) )
            ->getMock();

        $gateway->method( 'is_payment_component_enabled' )->willReturn( false );
        $gateway->method( 'get_payment_method_id' )->willReturn( 'multisafepay_amex' );
        $gateway->method( 'get_payment_method_gateway_code' )->willReturn( 'AMEX' );
        $gateway->method( 'get_title' )->willReturn( 'AMEX' );
        $gateway->method( 'get_description' )->willReturn( 'Desc' );
        $gateway->method( 'get_payment_method_icon' )->willReturn( 'icon.svg' );

        $blocks = new BasePaymentMethodBlocks();

        $reflection = new ReflectionClass( BasePaymentMethodBlocks::class );
        $property   = $reflection->getProperty( 'gateways' );
        $property->setAccessible( true );
        $property->setValue( $blocks, array( $gateway ) );

        update_option( 'multisafepay_checkout_block_payment_icons', '1' );
        $data = $blocks->get_payment_method_data();

        $this->assertIsArray( $data );
        $this->assertCount( 1, $data );
        $this->assertSame( 'multisafepay_amex', $data[0]['id'] );
        $this->assertSame( 'AMEX', $data[0]['title'] );
        $this->assertSame( 'Desc', $data[0]['description'] );
        $this->assertSame( 'icon.svg', $data[0]['icon'] );
        $this->assertFalse( $data[0]['has_payment_component'] );
        $this->assertSame( array(), $data[0]['payment_component_config'] );

        update_option( 'multisafepay_checkout_block_payment_icons', '0' );
        $data = $blocks->get_payment_method_data();
        $this->assertSame( '', $data[0]['icon'] );
    }

    /**
     * Includes gateways enabled in settings when the Checkout block editor is being used.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethodBlocks::initialize
     * @throws InvalidDataInitializationException
     */
    public function test_initialize_includes_direct_gateway_without_components(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        // Ensure PaymentMethodService uses the transient cache (and does not attempt an API call).
        unset( $_GET['page'], $_GET['tab'], $_GET['post'], $_GET['action'] );

        $payment_method = ( new PaymentMethodFixture() )->get_amex_payment_method_fixture();
        $payment_method['id']   = 'BANKTRANS';
        $payment_method['name'] = 'Bank transfer';

        // Force the transient read to return our fixture regardless of the environment's transient storage.
        add_filter(
            'pre_transient_multisafepay_payment_methods',
            function () use ( $payment_method ) {
                return array( $payment_method );
            }
        );

        // Seed API response via transient to avoid real SDK calls.
        set_transient(
            'multisafepay_payment_methods',
            array(
                $payment_method,
            ),
            60
        );

        // Configure gateway settings so BANKTRANS becomes a direct method without components.
        update_option(
            'woocommerce_multisafepay_banktrans_settings',
            array(
                'enabled'            => 'yes',
                'direct_transaction' => '1',
            )
        );

        $blocks = new BasePaymentMethodBlocks();
        $blocks->initialize();

        $reflection = new ReflectionClass( BasePaymentMethodBlocks::class );

        $initialized_property = $reflection->getProperty( 'initialized' );
        $initialized_property->setAccessible( true );
        $this->assertTrue( $initialized_property->getValue( $blocks ) );

        $property   = $reflection->getProperty( 'gateways' );
        $property->setAccessible( true );
        $gateways = $property->getValue( $blocks );

        $this->assertIsArray( $gateways );

        $gateway_ids = array_map(
            static function ( $gateway ) {
                return method_exists( $gateway, 'get_payment_method_id' )
                    ? (string) $gateway->get_payment_method_id()
                    : '';
            },
            $gateways
        );
        $this->assertContains( 'multisafepay_banktrans', $gateway_ids );

        // The second call should be a no-op (covers the early-return guard).
        $blocks->initialize();
        $this->assertTrue( $initialized_property->getValue( $blocks ) );
    }

    /**
     * Includes direct-without-components gateways in the Checkout editor when enabled.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethodBlocks::initialize
     * @throws InvalidDataInitializationException
     */
    public function test_initialize_includes_direct_gateway_without_components_in_checkout_editor(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        if ( ! function_exists( 'set_current_screen' ) ) {
            $this->markTestSkipped( 'set_current_screen() is not available in this test environment.' );
        }

        $admin_user_id = (int) $this->factory->user->create(
            array(
                'role' => 'administrator',
            )
        );
        wp_set_current_user( $admin_user_id );
        set_current_screen( 'post.php' );

        $checkout_post_id = (int) $this->factory->post->create(
            array(
                'post_title'   => 'Checkout',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '<!-- wp:woocommerce/checkout /-->',
            )
        );
        update_option( 'woocommerce_checkout_page_id', $checkout_post_id );

        $_GET['post']   = (string) $checkout_post_id;
        $_GET['action'] = 'edit';

        $payment_method = ( new PaymentMethodFixture() )->get_amex_payment_method_fixture();
        $payment_method['id']   = 'BANKTRANS';
        $payment_method['name'] = 'Bank transfer';

        $pre_transient_filter = static function () use ( $payment_method ) {
            return array( $payment_method );
        };

        add_filter( 'pre_transient_multisafepay_payment_methods', $pre_transient_filter );
        set_transient( 'multisafepay_payment_methods', array( $payment_method ), 60 );

        update_option(
            'woocommerce_multisafepay_banktrans_settings',
            array(
                'enabled'            => 'yes',
                'direct_transaction' => '1',
            )
        );

        $blocks = new BasePaymentMethodBlocks();
        $blocks->initialize();

        remove_filter( 'pre_transient_multisafepay_payment_methods', $pre_transient_filter );

        $reflection = new ReflectionClass( BasePaymentMethodBlocks::class );
        $property   = $reflection->getProperty( 'gateways' );
        $property->setAccessible( true );

        $gateways = $property->getValue( $blocks );

        $gateway_ids = array_map(
            static function ( $gateway ) {
                return method_exists( $gateway, 'get_payment_method_id' )
                    ? (string) $gateway->get_payment_method_id()
                    : '';
            },
            $gateways
        );

        $this->assertContains( 'multisafepay_banktrans', $gateway_ids );
    }

    /**
     * Includes payment-component gateways in the Checkout editor when enabled.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethodBlocks::initialize
     * @throws InvalidDataInitializationException
     */
    public function test_initialize_includes_payment_component_gateway_in_checkout_editor_when_enabled(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        if ( ! function_exists( 'set_current_screen' ) ) {
            $this->markTestSkipped( 'set_current_screen() is not available in this test environment.' );
        }

        $admin_user_id = (int) $this->factory->user->create(
            array(
                'role' => 'administrator',
            )
        );
        wp_set_current_user( $admin_user_id );
        set_current_screen( 'post.php' );

        $checkout_post_id = (int) $this->factory->post->create(
            array(
                'post_title'   => 'Checkout',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '<!-- wp:woocommerce/checkout /-->',
            )
        );
        update_option( 'woocommerce_checkout_page_id', $checkout_post_id );

        $_GET['post']   = (string) $checkout_post_id;
        $_GET['action'] = 'edit';

        $payment_method = ( new PaymentMethodFixture() )->get_amex_payment_method_fixture();

        $pre_transient_filter = static function () use ( $payment_method ) {
            return array( $payment_method );
        };

        add_filter( 'pre_transient_multisafepay_payment_methods', $pre_transient_filter );
        set_transient( 'multisafepay_payment_methods', array( $payment_method ), 60 );

        update_option(
            'woocommerce_multisafepay_amex_settings',
            array(
                'enabled'           => 'yes',
                'payment_component' => 'yes',
            )
        );

        $blocks = new BasePaymentMethodBlocks();
        $blocks->initialize();

        remove_filter( 'pre_transient_multisafepay_payment_methods', $pre_transient_filter );

        $reflection = new ReflectionClass( BasePaymentMethodBlocks::class );
        $property   = $reflection->getProperty( 'gateways' );
        $property->setAccessible( true );

        $gateways = $property->getValue( $blocks );

        $gateway_ids = array_map(
            static function ( $gateway ) {
                return method_exists( $gateway, 'get_payment_method_id' )
                    ? (string) $gateway->get_payment_method_id()
                    : '';
            },
            $gateways
        );

        $this->assertContains( 'multisafepay_amex', $gateway_ids );
    }

    /**
     * Keeps the instance uninitialized when payment method data is invalid.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethodBlocks::initialize
     */
    public function test_initialize_keeps_uninitialized_state_when_data_is_invalid(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        add_filter(
            'pre_transient_multisafepay_payment_methods',
            function () {
                return array(
                    array(
                        'id'   => 'BROKEN_METHOD',
                        'name' => 'Broken method',
                        'type' => 'payment-method',
                    ),
                );
            }
        );

        $blocks = new BasePaymentMethodBlocks();

        try {
            $blocks->initialize();
            $this->fail( 'Expected InvalidDataInitializationException was not thrown.' );
        } catch ( InvalidDataInitializationException $exception ) {
            $this->assertStringContainsString( 'No Allowed Amounts', $exception->getMessage() );
        }

        $reflection = new ReflectionClass( BasePaymentMethodBlocks::class );

        $initialized_property = $reflection->getProperty( 'initialized' );
        $initialized_property->setAccessible( true );
        $this->assertFalse( $initialized_property->getValue( $blocks ) );

        $gateways_property = $reflection->getProperty( 'gateways' );
        $gateways_property->setAccessible( true );
        $this->assertSame( array(), $gateways_property->getValue( $blocks ) );
    }

    /**
     * Aliases the admin script handles method to the frontend one.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethodBlocks::get_payment_method_script_handles_for_admin
     * @throws InvalidDataInitializationException
     */
    public function test_get_payment_method_script_handles_for_admin_aliases_frontend(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        $blocks = new BasePaymentMethodBlocks();

        $this->assertSame(
            $blocks->get_payment_method_script_handles(),
            $blocks->get_payment_method_script_handles_for_admin()
        );
    }

    /**
     * Detects enabled gateways in settings without relying on frontend availability.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethodBlocks::initialize
     * @throws ReflectionException
     */
    public function test_initialize_checks_gateway_enabled_in_settings(): void {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
            $this->markTestSkipped( 'WooCommerce Blocks is not available in this test environment.' );
        }

        $gateway = $this->getMockBuilder( '\\MultiSafepay\\WooCommerce\\PaymentMethods\\Base\\BasePaymentMethod' )
            ->disableOriginalConstructor()
            ->getMock();

        $gateway->id = 'multisafepay_amex';

        update_option( 'woocommerce_multisafepay_amex_settings', array( 'enabled' => 'yes' ) );

        $blocks     = new BasePaymentMethodBlocks();
        $reflection = new ReflectionClass( BasePaymentMethodBlocks::class );
        $method     = $reflection->getMethod( 'is_gateway_enabled_in_settings' );
        $method->setAccessible( true );

        $this->assertTrue( $method->invoke( $blocks, $gateway ) );

        update_option( 'woocommerce_multisafepay_amex_settings', array( 'enabled' => 'no' ) );
        $this->assertFalse( $method->invoke( $blocks, $gateway ) );
    }
}
