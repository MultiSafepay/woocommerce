<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Services\Qr\QrPaymentComponentService;

/**
 * Covers QR redirect outcomes handled by QrPaymentComponentService.
 *
 * @covers \MultiSafepay\WooCommerce\Services\Qr\QrPaymentComponentService::get_qr_order_redirect_url
 */
class Test_QrPaymentComponentService extends WP_UnitTestCase {

    /**
     * @var array
     */
    private $original_post = array();

    /**
     * @var QrPaymentComponentService
     */
    private $service;

    /**
     * @var mixed
     */
    private $previous_redirect_after_cancel_option_value;

    /**
     * @var bool
     */
    private $redirect_after_cancel_option_exists = false;

    /**
     * Clear WooCommerce notices when available.
     *
     * @return void
     */
    private function clear_wc_notices(): void {
        if ( function_exists( 'wc_clear_notices' ) && did_action( 'woocommerce_init' ) && function_exists( 'WC' ) && WC() && WC()->session ) {
            wc_clear_notices();
        }
    }

    /**
     * Safely return WooCommerce error notices.
     *
     * @return array
     */
    private function get_wc_error_notices(): array {
        if ( ! function_exists( 'wc_get_notices' ) || ! did_action( 'woocommerce_init' ) || ! function_exists( 'WC' ) || ! WC() || ! WC()->session ) {
            return array();
        }

        $error_notices = wc_get_notices( 'error' );
        return is_array( $error_notices ) ? $error_notices : array();
    }

    public function set_up() {
        parent::set_up();

        $this->original_post = $_POST;
        $this->service       = new QrPaymentComponentService();
        $this->previous_redirect_after_cancel_option_value = get_option( 'multisafepay_redirect_after_cancel', null );
        $this->redirect_after_cancel_option_exists         = null !== $this->previous_redirect_after_cancel_option_value;

        if ( function_exists( 'WC' ) && WC() && class_exists( 'WC_Session_Handler' ) && ! WC()->session ) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }

        if ( function_exists( 'WC' ) && WC() && class_exists( 'WC_Order_Factory' ) && ! WC()->order_factory ) {
            WC()->order_factory = new WC_Order_Factory();
        }

        if ( ! did_action( 'woocommerce_init' ) ) {
            do_action( 'woocommerce_init' );
        }

        $this->clear_wc_notices();
    }

    public function tear_down() {
        $_POST = $this->original_post;

        if ( $this->redirect_after_cancel_option_exists ) {
            update_option( 'multisafepay_redirect_after_cancel', $this->previous_redirect_after_cancel_option_value );
        } else {
            delete_option( 'multisafepay_redirect_after_cancel' );
        }

        $this->clear_wc_notices();

        parent::tear_down();
    }

    /**
     * Capture wp_send_json output from get_qr_order_redirect_url.
     *
     * @return array
     */
    private function run_get_redirect_url_and_get_response(): array {
        add_filter( 'wp_doing_ajax', '__return_true', PHP_INT_MAX );
        add_filter( 'wp_die_handler', array( $this, 'get_test_wp_die_handler' ) );
        add_filter( 'wp_die_ajax_handler', array( $this, 'get_test_wp_die_handler' ) );

        $raw = '';
        ob_start();
        try {
            $this->service->get_qr_order_redirect_url();
        } catch ( Throwable $throwable ) {
            // Expected: wp_send_json() ends in wp_die().
            if ( 'wp_die intercepted' !== $throwable->getMessage() ) {
                throw $throwable;
            }
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

    public function test_get_qr_order_redirect_url_adds_declined_notice_and_redirects_to_checkout_when_configured(): void {
        update_option( 'multisafepay_redirect_after_cancel', 'checkout' );

        $_POST = array(
            'nonce'     => wp_create_nonce( 'payment_component_arguments_nonce' ),
            'order_id'  => 'QR-DECLINED-001',
            'qr_status' => 'declined',
        );

        $response = $this->run_get_redirect_url_and_get_response();

        $this->assertIsArray( $response );
        $this->assertArrayHasKey( 'success', $response );
        $this->assertTrue( (bool) $response['success'] );
        $this->assertSame( wc_get_checkout_url(), $response['redirect_url'] );

        $error_notices = $this->get_wc_error_notices();
        $this->assertNotEmpty( $error_notices );
        $this->assertSame(
            __( 'Your payment was declined. Please choose another payment method or try again.', 'multisafepay' ),
            (string) $error_notices[0]['notice']
        );
    }

    public function test_get_qr_order_redirect_url_adds_cancelled_notice_and_redirects_to_cart_when_configured(): void {
        update_option( 'multisafepay_redirect_after_cancel', 'cart' );

        $_POST = array(
            'nonce'     => wp_create_nonce( 'payment_component_arguments_nonce' ),
            'order_id'  => 'QR-CANCELLED-001',
            'qr_status' => 'cancelled',
        );

        $response = $this->run_get_redirect_url_and_get_response();

        $this->assertIsArray( $response );
        $this->assertArrayHasKey( 'success', $response );
        $this->assertTrue( (bool) $response['success'] );
        $this->assertSame( wc_get_cart_url(), $response['redirect_url'] );

        $error_notices = $this->get_wc_error_notices();
        $this->assertNotEmpty( $error_notices );
        $this->assertSame(
            __( 'Your payment was cancelled. Please try again to complete your order.', 'multisafepay' ),
            (string) $error_notices[0]['notice']
        );
    }

    public function test_get_qr_order_redirect_url_redirects_to_order_received_for_completed_status(): void {
        $transaction_id = 'QR-COMPLETED-001';

        $order = wc_create_order();
        $order->update_meta_data( 'multisafepay_transaction_id', $transaction_id );
        $order->save();

        $_POST = array(
            'nonce'     => wp_create_nonce( 'payment_component_arguments_nonce' ),
            'order_id'  => $transaction_id,
            'qr_status' => 'completed',
        );

        $response = $this->run_get_redirect_url_and_get_response();

        $this->assertIsArray( $response );
        $this->assertArrayHasKey( 'success', $response );
        $this->assertTrue( (bool) $response['success'] );
        $this->assertSame( $order->get_checkout_order_received_url(), $response['redirect_url'] );

        $this->assertEmpty( $this->get_wc_error_notices() );
    }
}
