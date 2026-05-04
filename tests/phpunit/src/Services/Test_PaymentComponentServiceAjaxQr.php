<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\Services\ApiTokenService;
use MultiSafepay\WooCommerce\Services\PaymentComponentService;
use MultiSafepay\WooCommerce\Services\PaymentMethodService;
use MultiSafepay\WooCommerce\Services\SdkService;

/**
 * Covers the QR configuration injection via the refresh_payment_component_config AJAX endpoint.
 *
 * This is the critical backend contract used by both classic checkout and block-based checkout:
 * QR settings are only injected when nonce + required checkout fields are present in form_data.
 *
 * @covers \MultiSafepay\WooCommerce\Services\PaymentComponentService::refresh_payment_component_config
 */
class Test_PaymentComponentServiceAjaxQr extends WP_UnitTestCase {

    /**
     * @var array
     */
    private $original_post;

    /**
     * @var PaymentComponentService
     */
    private $service;

    /**
     * @var callable|null
     */
    private $original_wp_die_handler;

    /**
     * @var callable|null
     */
    private $original_wp_die_ajax_handler;

    public function set_up() {
        parent::set_up();

        $this->original_post = $_POST;

        $user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $user_id );

        $this->service = new PaymentComponentService();

        $sdk_service = $this->getMockBuilder( SdkService::class )
            ->disableOriginalConstructor()
            ->onlyMethods( array( 'get_test_mode' ) )
            ->getMock();
        $sdk_service->method( 'get_test_mode' )->willReturn( true );

        $api_token_service = $this->getMockBuilder( ApiTokenService::class )
            ->disableOriginalConstructor()
            ->onlyMethods( array( 'get_api_token' ) )
            ->getMock();
        $api_token_service->method( 'get_api_token' )->willReturn( 'fake-api-token' );

        $this->service->sdk_service       = $sdk_service;
        $this->service->api_token_service = $api_token_service;
    }

    public function tear_down() {
        $_POST = $this->original_post;
        parent::tear_down();
    }

    /**
     * Capture wp_send_json() output without relying on WP_Ajax_UnitTestCase.
     *
     * This avoids the WordPress ajax test bootstrap, which can trigger WooCommerce install/update hooks
     * (and fail in some CI setups due to missing optional callbacks like wc_set_hooked_blocks_version).
     *
     * @return array
     */
    private function run_refresh_and_get_response(): array {
        $this->original_wp_die_handler      = $GLOBALS['wp_die_handler'] ?? null;
        $this->original_wp_die_ajax_handler = $GLOBALS['wp_die_ajax_handler'] ?? null;

        add_filter( 'wp_doing_ajax', '__return_true', PHP_INT_MAX );
        add_filter( 'wp_die_handler', array( $this, 'get_test_wp_die_handler' ) );
        add_filter( 'wp_die_ajax_handler', array( $this, 'get_test_wp_die_handler' ) );

        $raw = '';
        ob_start();
        try {
            $this->service->refresh_payment_component_config();
        } catch ( Throwable $throwable ) {
            // Expected: wp_send_json() ends in wp_die().
        } finally {
            $raw = (string) ob_get_clean();

            remove_filter( 'wp_doing_ajax', '__return_true', PHP_INT_MAX );
            remove_filter( 'wp_die_handler', array( $this, 'get_test_wp_die_handler' ) );
            remove_filter( 'wp_die_ajax_handler', array( $this, 'get_test_wp_die_handler' ) );
        }

        $decoded = json_decode( $raw, true );
        return is_array( $decoded ) ? $decoded : array();
    }

    /**
     * Filter callback to supply a custom wp_die handler.
     *
     * @param mixed $handler
     * @return callable
     */
    public function get_test_wp_die_handler( $handler = null ): callable {
        return array( $this, 'intercept_wp_die' );
    }

    /**
     * Custom wp_die handler for tests.
     *
     * @throws Exception
     */
    public function intercept_wp_die( $message = '', $title = '', $args = array() ): void {
        throw new Exception( 'wp_die intercepted' );
    }

    /**
     * When nonce and all required checkout fields are present in form_data, connect.qr must be included.
     */
    public function test_refresh_payment_component_config_includes_qr_when_checkout_valid(): void {
        $gateway = $this->get_payment_gateway_mock( false );

        $payment_method_service = $this->getMockBuilder( PaymentMethodService::class )
            ->disableOriginalConstructor()
            ->onlyMethods( array( 'get_woocommerce_payment_gateway_by_id' ) )
            ->getMock();
        $payment_method_service->method( 'get_woocommerce_payment_gateway_by_id' )->willReturn( $gateway );

        $this->service->payment_method_service = $payment_method_service;

        $_POST = array(
            'action'    => 'refresh_payment_component_config',
            'nonce'     => wp_create_nonce( 'payment_component_arguments_nonce' ),
            'gateway_id' => 'fake_gateway',
            'form_data'  => http_build_query(
                array(
                    'billing_first_name' => 'John',
                    'billing_last_name'  => 'Doe',
                    'billing_address_1'  => '123 Main St',
                    'billing_city'       => 'Beverly Hills',
                    'billing_postcode'   => '90210',
                    'billing_country'    => 'US',
                    'billing_email'      => 'john.doe@example.com',
                    'billing_phone'      => '5551234567',
                ),
                '',
                '&'
            ),
        );

        $response = $this->run_refresh_and_get_response();

        $this->assertIsArray( $response );
        $this->assertArrayHasKey( 'orderData', $response );
        $this->assertArrayHasKey( 'payment_options', $response['orderData'] );
        $this->assertArrayHasKey( 'settings', $response['orderData']['payment_options'] );
        $this->assertArrayHasKey( 'connect', $response['orderData']['payment_options']['settings'] );
        $this->assertArrayHasKey( 'qr', $response['orderData']['payment_options']['settings']['connect'] );

        $this->assertSame( 1, $response['orderData']['payment_options']['settings']['connect']['qr']['enabled'] );
        $this->assertSame( 206, $response['orderData']['payment_options']['settings']['connect']['qr']['size'] );
        $this->assertArrayNotHasKey( 'qr_only', $response['orderData']['payment_options']['settings']['connect']['qr'] );
    }

    /**
     * When QR-only is enabled on the gateway and checkout is valid, connect.qr.qr_only must be set.
     */
    public function test_refresh_payment_component_config_includes_qr_only_when_enabled(): void {
        $gateway = $this->get_payment_gateway_mock( true );

        $payment_method_service = $this->getMockBuilder( PaymentMethodService::class )
            ->disableOriginalConstructor()
            ->onlyMethods( array( 'get_woocommerce_payment_gateway_by_id' ) )
            ->getMock();
        $payment_method_service->method( 'get_woocommerce_payment_gateway_by_id' )->willReturn( $gateway );

        $this->service->payment_method_service = $payment_method_service;

        $_POST = array(
            'action'     => 'refresh_payment_component_config',
            'nonce'      => wp_create_nonce( 'payment_component_arguments_nonce' ),
            'gateway_id' => 'fake_gateway',
            'form_data'  => http_build_query(
                array(
                    'billing_first_name' => 'John',
                    'billing_last_name'  => 'Doe',
                    'billing_address_1'  => '123 Main St',
                    'billing_city'       => 'Beverly Hills',
                    'billing_postcode'   => '90210',
                    'billing_country'    => 'US',
                    'billing_email'      => 'john.doe@example.com',
                    'billing_phone'      => '5551234567',
                ),
                '',
                '&'
            ),
        );

        $response = $this->run_refresh_and_get_response();

        $this->assertIsArray( $response );
        $this->assertArrayHasKey( 'orderData', $response );
        $this->assertArrayHasKey( 'payment_options', $response['orderData'] );
        $this->assertArrayHasKey( 'settings', $response['orderData']['payment_options'] );
        $this->assertArrayHasKey( 'connect', $response['orderData']['payment_options']['settings'] );
        $this->assertArrayHasKey( 'qr', $response['orderData']['payment_options']['settings']['connect'] );

        $this->assertSame( 1, $response['orderData']['payment_options']['settings']['connect']['qr']['enabled'] );
        $this->assertSame( 1, $response['orderData']['payment_options']['settings']['connect']['qr']['qr_only'] );
    }

    /**
     * When required fields are missing, connect.qr must not be injected.
     */
    public function test_refresh_payment_component_config_excludes_qr_when_checkout_invalid(): void {
        $gateway = $this->get_payment_gateway_mock( false );

        $payment_method_service = $this->getMockBuilder( PaymentMethodService::class )
            ->disableOriginalConstructor()
            ->onlyMethods( array( 'get_woocommerce_payment_gateway_by_id' ) )
            ->getMock();
        $payment_method_service->method( 'get_woocommerce_payment_gateway_by_id' )->willReturn( $gateway );

        $this->service->payment_method_service = $payment_method_service;

        $_POST = array(
            'action'     => 'refresh_payment_component_config',
            'nonce'      => wp_create_nonce( 'payment_component_arguments_nonce' ),
            'gateway_id' => 'fake_gateway',
            'form_data'  => http_build_query(
                array(
                    'billing_first_name' => 'John',
                    'billing_last_name'  => 'Doe',
                    'billing_address_1'  => '123 Main St',
                    'billing_city'       => 'Beverly Hills',
                    'billing_postcode'   => '90210',
                    'billing_country'    => 'US',
                    'billing_email'      => 'john.doe@example.com',
                    // billing_phone intentionally missing.
                ),
                '',
                '&'
            ),
        );

        $response = $this->run_refresh_and_get_response();

        $this->assertIsArray( $response );
        $this->assertArrayHasKey( 'orderData', $response );
        $this->assertArrayHasKey( 'payment_options', $response['orderData'] );
        $this->assertArrayHasKey( 'settings', $response['orderData']['payment_options'] );
        $this->assertArrayHasKey( 'connect', $response['orderData']['payment_options']['settings'] );
        $this->assertArrayNotHasKey( 'qr', $response['orderData']['payment_options']['settings']['connect'] );
    }

    /**
     * @param bool $qr_only_enabled
     * @return BasePaymentMethod
     */
    private function get_payment_gateway_mock( bool $qr_only_enabled ): BasePaymentMethod
    {
        $gateway = $this->getMockBuilder( BasePaymentMethod::class )
            ->disableOriginalConstructor()
            ->onlyMethods(
                array(
                    'is_payment_component_enabled',
                    'is_qr_enabled',
                    'is_qr_only_enabled',
                    'get_payment_method_gateway_code',
                    'is_tokenization_enabled',
                    'get_qr_width',
                )
            )
            ->getMock();

        $gateway->method( 'is_payment_component_enabled' )->willReturn( true );
        $gateway->method( 'is_qr_enabled' )->willReturn( ! $qr_only_enabled );
        $gateway->method( 'is_qr_only_enabled' )->willReturn( $qr_only_enabled );
        $gateway->method( 'get_payment_method_gateway_code' )->willReturn( 'FAKE' );
        $gateway->method( 'is_tokenization_enabled' )->willReturn( false );
        $gateway->method( 'get_qr_width' )->willReturn( '' );

        return $gateway;
    }
}
