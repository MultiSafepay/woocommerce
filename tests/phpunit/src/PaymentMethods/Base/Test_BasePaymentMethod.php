<?php declare(strict_types=1);

use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidDataInitializationException;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\Services\BlocksPaymentDataService;
use MultiSafepay\WooCommerce\Services\OrderService;
use MultiSafepay\WooCommerce\Tests\Fixtures\PaymentMethodFixture;

class Test_BasePaymentMethod extends WP_UnitTestCase {

    /**
     * @var PaymentMethod
     */
    public $payment_method;

    /**
     * @var BasePaymentMethod;
     */
    public $woocommerce_payment_gateway;

    /**
     * Snapshot of request globals to avoid cross-test leakage.
     *
     * @var bool
     */
    private $had_rest_route;

    /**
     * Snapshot of request globals to avoid cross-test leakage.
     *
     * @var string
     */
    private $original_rest_route;

    /**
     * Snapshot of request globals to avoid cross-test leakage.
     *
     * @var bool
     */
    private $had_request_uri;

    /**
     * Snapshot of request globals to avoid cross-test leakage.
     *
     * @var string
     */
    private $original_request_uri;

    /**
     * Toggle request globals to emulate Store API or classic checkout context.
     *
     * @param bool $enabled
     * @return void
     */
    private function set_store_api_context( bool $enabled ): void {
        if ( $enabled ) {
            $_GET['rest_route']     = '/wc/store/v1/checkout';
            $_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/checkout';
            return;
        }

        unset( $_GET['rest_route'] );
        $_SERVER['REQUEST_URI'] = '/checkout/';
    }

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
     * @throws InvalidDataInitializationException
     */
    public function set_up() {
        parent::set_up();

        $this->had_rest_route      = isset( $_GET['rest_route'] );
        $this->original_rest_route = $this->had_rest_route ? (string) $_GET['rest_route'] : '';
        $this->had_request_uri     = isset( $_SERVER['REQUEST_URI'] );
        $this->original_request_uri = $this->had_request_uri ? (string) $_SERVER['REQUEST_URI'] : '';

        if ( function_exists( 'WC' ) && WC() && class_exists( 'WC_Session_Handler' ) && ! WC()->session ) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }

        if ( function_exists( 'WC' ) && WC() && class_exists( 'WC_Order_Factory' ) && ! WC()->order_factory ) {
            WC()->order_factory = new WC_Order_Factory();
        }

        // Notice helpers (wc_add_notice/wc_get_notices) require this action to be fired.
        if ( ! did_action( 'woocommerce_init' ) ) {
            do_action( 'woocommerce_init' );
        }

        $_POST = array();
        $this->clear_wc_notices();

        $this->payment_method = new PaymentMethod( ( new PaymentMethodFixture() )->get_amex_payment_method_fixture() );
        $this->woocommerce_payment_gateway = new BasePaymentMethod( $this->payment_method );
    }

    /**
     * Restore request globals modified by tests.
     *
     * @return void
     */
    public function tear_down() {
        $_POST = array();

        if ( $this->had_rest_route ) {
            $_GET['rest_route'] = $this->original_rest_route;
        } else {
            unset( $_GET['rest_route'] );
        }

        if ( $this->had_request_uri ) {
            $_SERVER['REQUEST_URI'] = $this->original_request_uri;
        } else {
            unset( $_SERVER['REQUEST_URI'] );
        }

        $this->clear_wc_notices();

        parent::tear_down();
    }

    public function test_is_base_payment_method() {
        $this->assertInstanceOf( BasePaymentMethod::class, $this->woocommerce_payment_gateway );
    }

    public function test_is_wc_payment_gateway() {
        $this->assertInstanceOf( WC_Payment_Gateway::class, $this->woocommerce_payment_gateway );
    }

    public function test_get_payment_method_id() {
        $this->assertEquals( 'multisafepay_amex', $this->woocommerce_payment_gateway->get_payment_method_id() );
    }

    public function test_get_payment_method_gateway_code() {
        $this->assertEquals( 'AMEX', $this->woocommerce_payment_gateway->get_payment_method_gateway_code() );
    }

    public function test_get_payment_method_description() {
        $this->assertNotEmpty( $this->woocommerce_payment_gateway->get_payment_method_description() );
    }

    public function test_get_payment_method_icon() {
        $this->assertEquals( 'https://testmedia.multisafepay.com/img/methods/3x/amex.png', $this->woocommerce_payment_gateway->get_payment_method_icon() );
    }

    public function test_has_fields() {
        $this->assertTrue( $this->woocommerce_payment_gateway->has_fields() );
    }

    public function test_has_payment_component_setting_field() {
        $setting_fields = $this->woocommerce_payment_gateway->add_form_fields();
        $this->assertArrayHasKey( 'payment_component', $setting_fields );
    }

    public function test_has_tokenization_setting_field() {
        $setting_fields = $this->woocommerce_payment_gateway->add_form_fields();
        $this->assertArrayHasKey( 'tokenization', $setting_fields );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::validate_fields
     */
    public function test_validate_fields_requires_payment_component_payload_in_classic_checkout(): void {
        update_option(
            'woocommerce_multisafepay_amex_settings',
            array(
                'payment_component' => 'yes',
            )
        );

        $this->set_store_api_context( false );
        $_POST = array();

        $this->clear_wc_notices();

        $this->assertFalse( $this->woocommerce_payment_gateway->validate_fields() );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::validate_fields
     */
    public function test_validate_fields_allows_missing_payment_component_payload_in_store_api(): void {
        update_option(
            'woocommerce_multisafepay_amex_settings',
            array(
                'payment_component' => 'yes',
            )
        );

        $this->set_store_api_context( true );
        $_POST = array();

        $this->clear_wc_notices();

        $this->assertTrue( $this->woocommerce_payment_gateway->validate_fields() );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_payment
     */
    public function test_process_payment_returns_invalid_order_failure_in_classic_checkout(): void {
        $this->set_store_api_context( false );
        $this->clear_wc_notices();

        $result = $this->woocommerce_payment_gateway->process_payment( 999999 );

        $this->assertSame(
            array(
                'result'  => 'failure',
                'message' => 'Invalid order. Please try again.',
            ),
            $result
        );
        $this->assertNotEmpty( wc_get_notices( 'error' ) );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_payment
     */
    public function test_process_payment_returns_invalid_order_failure_without_notice_in_store_api(): void {
        $this->set_store_api_context( true );
        $this->clear_wc_notices();

        $result = $this->woocommerce_payment_gateway->process_payment( 999999 );

        $this->assertSame(
            array(
                'result'  => 'failure',
                'message' => 'Invalid order. Please try again.',
            ),
            $result
        );
        $this->assertEmpty( wc_get_notices( 'error' ) );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_payment
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_classic_payment
     * @throws InvalidDataInitializationException
     */
    public function test_process_payment_returns_success_with_payment_url_in_classic_checkout(): void {
        $this->set_store_api_context( false );
        $this->clear_wc_notices();

        $order_id = $this->create_persisted_order_id();
        $gateway  = $this->build_testable_gateway(
            new TransactionResponse( array( 'payment_url' => 'https://pay.example.test/classic' ) )
        );

        $result = $gateway->process_payment( $order_id );

        $this->assertSame(
            array(
                'result'   => 'success',
                'redirect' => 'https://pay.example.test/classic',
            ),
            $result
        );
        $this->assertEmpty( wc_get_notices( 'error' ) );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_payment
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_classic_payment
     * @throws InvalidDataInitializationException
     */
    public function test_process_payment_adds_notice_and_returns_failure_on_exception_in_classic_checkout(): void {
        $this->set_store_api_context( false );
        $this->clear_wc_notices();

        $order_id = $this->create_persisted_order_id();
        $gateway  = $this->build_testable_gateway( null, new ApiException( 'api down' ) );

        $result = $gateway->process_payment( $order_id );

        $this->assertSame(
            array(
                'result'  => 'failure',
                'message' => 'There was a problem processing your payment. Please try again later or contact with us.',
            ),
            $result
        );
        $this->assertNotEmpty( wc_get_notices( 'error' ) );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_payment
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_blocks_payment
     * @throws InvalidDataInitializationException
     */
    public function test_process_payment_returns_success_with_payment_url_in_store_api(): void {
        $this->set_store_api_context( true );
        $this->clear_wc_notices();

        $order_id = $this->create_persisted_order_id();
        $gateway  = $this->build_testable_gateway(
            new TransactionResponse( array( 'payment_url' => 'https://pay.example.test/blocks' ) )
        );

        $result = $gateway->process_payment( $order_id );

        $this->assertSame(
            array(
                'result'   => 'success',
                'redirect' => 'https://pay.example.test/blocks',
            ),
            $result
        );
        $this->assertEmpty( wc_get_notices( 'error' ) );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_payment
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_blocks_payment
     * @throws InvalidDataInitializationException
     */
    public function test_process_payment_returns_failure_without_notice_on_exception_in_store_api(): void {
        $this->set_store_api_context( true );
        $this->clear_wc_notices();

        $order_id = $this->create_persisted_order_id();
        $gateway  = $this->build_testable_gateway( null, new ApiException( 'api down' ) );

        $result = $gateway->process_payment( $order_id );

        $this->assertSame(
            array(
                'result'  => 'failure',
                'message' => 'There was a problem processing your payment. Please try again later or contact with us.',
            ),
            $result
        );
        $this->assertEmpty( wc_get_notices( 'error' ) );
    }

    /**
     * In wallet-direct (Apple Pay / Google Pay) the redirect must go to the 'Thank You' page
     * when the combined wallet title has been persisted, instead of the payment URL.
     *
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_payment
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_blocks_payment
     * @throws InvalidDataInitializationException
     */
    public function test_process_payment_blocks_wallet_redirects_to_return_url_when_combined_title_persisted(): void {
        $this->set_store_api_context( true );
        $this->clear_wc_notices();

        $order_id = $this->create_persisted_order_id();
        $order    = wc_get_order( $order_id );
        // Persist the wallet token in Blocks order meta so get_wallet_payment_token() returns non-empty.
        $order->update_meta_data(
            BlocksPaymentDataService::META_KEY,
            array(
                'payment_token' => 'wallet-token',
            )
        );
        $order->save();

        $gateway = $this->build_testable_gateway(
            new TransactionResponse( array( 'payment_url' => 'https://pay.example.test/blocks-wallet' ) ),
            null,
            'GOOGLEPAY',
            true
        );

        $result = $gateway->process_payment( $order_id );

        $this->assertSame( 'success', $result['result'] );
        $this->assertStringNotContainsString( 'pay.example.test', $result['redirect'] );
        $this->assertSame( $gateway->get_return_url( wc_get_order( $order_id ) ), $result['redirect'] );
    }

    /**
     * When MultiSafepay returns an empty payment_url, Blocks must redirect to the 'Thank You' page.
     *
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_payment
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_blocks_payment
     * @throws InvalidDataInitializationException
     */
    public function test_process_payment_blocks_redirects_to_return_url_when_payment_url_is_empty(): void {
        $this->set_store_api_context( true );
        $this->clear_wc_notices();

        $order_id = $this->create_persisted_order_id();
        $gateway  = $this->build_testable_gateway( new TransactionResponse( array() ) );

        $result = $gateway->process_payment( $order_id );

        $this->assertSame( 'success', $result['result'] );
        $this->assertSame( $gateway->get_return_url( wc_get_order( $order_id ) ), $result['redirect'] );
    }

    /**
     * Blocks must best-effort clear request-lifecycle meta on the success path.
     *
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_payment
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_blocks_payment
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::clear_blocks_process_payment_data
     * @throws InvalidDataInitializationException
     */
    public function test_process_payment_blocks_clears_request_lifecycle_meta_on_success(): void {
        $this->set_store_api_context( true );
        $this->clear_wc_notices();

        $order_id = $this->create_persisted_order_id();
        $order    = wc_get_order( $order_id );
        $order->update_meta_data(
            BlocksPaymentDataService::META_KEY,
            array(
                'browser'        => '{"fake":true}',
                'persistent_key' => 'keep-me',
            )
        );
        $order->save();

        $gateway = $this->build_testable_gateway(
            new TransactionResponse( array( 'payment_url' => 'https://pay.example.test/blocks' ) )
        );

        $gateway->process_payment( $order_id );

        $fresh_order = wc_get_order( $order_id );
        $meta_value  = $fresh_order->get_meta( BlocksPaymentDataService::META_KEY );
        $this->assertIsArray( $meta_value );
        $this->assertArrayNotHasKey( 'browser', $meta_value );
        $this->assertSame( 'keep-me', $meta_value['persistent_key'] ?? null );
    }

    /**
     * Blocks must best-effort clear request-lifecycle meta even when the transaction fails.
     *
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_payment
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_blocks_payment
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::clear_blocks_process_payment_data
     * @throws InvalidDataInitializationException
     */
    public function test_process_payment_blocks_clears_request_lifecycle_meta_on_failure(): void {
        $this->set_store_api_context( true );
        $this->clear_wc_notices();

        $order_id = $this->create_persisted_order_id();
        $order    = wc_get_order( $order_id );
        $order->update_meta_data(
            BlocksPaymentDataService::META_KEY,
            array(
                'browser' => '{"fake":true}',
            )
        );
        $order->save();

        $gateway = $this->build_testable_gateway( null, new ApiException( 'api down' ) );

        $gateway->process_payment( $order_id );

        $fresh_order = wc_get_order( $order_id );
        $meta_value  = $fresh_order->get_meta( BlocksPaymentDataService::META_KEY );
        $this->assertTrue( empty( $meta_value ) || ( is_array( $meta_value ) && ! array_key_exists( 'browser', $meta_value ) ) );
    }

    /**
     * Classic checkout must NOT touch Blocks request-lifecycle meta (avoids unnecessary overhead).
     *
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_payment
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::process_classic_payment
     * @throws InvalidDataInitializationException
     */
    public function test_process_payment_classic_does_not_clear_blocks_meta(): void {
        $this->set_store_api_context( false );
        $this->clear_wc_notices();

        $order_id = $this->create_persisted_order_id();
        $order    = wc_get_order( $order_id );
        $order->update_meta_data(
            BlocksPaymentDataService::META_KEY,
            array(
                'browser' => '{"fake":true}',
            )
        );
        $order->save();

        $gateway = $this->build_testable_gateway(
            new TransactionResponse( array( 'payment_url' => 'https://pay.example.test/classic' ) )
        );

        $gateway->process_payment( $order_id );

        $fresh_order = wc_get_order( $order_id );
        $meta_value  = $fresh_order->get_meta( BlocksPaymentDataService::META_KEY );
        $this->assertIsArray( $meta_value );
        $this->assertSame( '{"fake":true}', $meta_value['browser'] ?? null );
    }

    /**
     * Creates a persisted WC_Order and returns its ID, so it is resolvable through wc_get_order().
     *
     * @return int
     */
    private function create_persisted_order_id(): int {
        $order = new WC_Order();
        $order->save();

        return (int) $order->get_id();
    }

    /**
     * Build a BasePaymentMethod subclass that bypasses the real SDK call.
     *
     * When $exception is provided, create_transaction_for_payment() throws it;
     * otherwise, it returns the given $transaction. $gateway_code_override allows
     * forcing a wallet gateway without loading a different fixture, and
     * $stub_persist_wallet_title forces persist_wallet_combined_payment_method_title_from_transaction()
     * to return true so the wallet-direct redirect branch can be exercised.
     *
     * @param TransactionResponse|null $transaction
     * @param Throwable|null $exception
     * @param string|null $gateway_code_override
     * @param bool $stub_persist_wallet_title
     * @return BasePaymentMethod
     * @throws InvalidDataInitializationException
     */
    private function build_testable_gateway(
        ?TransactionResponse $transaction = null,
        ?Throwable           $exception = null,
        ?string              $gateway_code_override = null,
        bool                 $stub_persist_wallet_title = false
    ): BasePaymentMethod {
        $payment_method = new PaymentMethod( ( new PaymentMethodFixture() )->get_amex_payment_method_fixture() );

        return new class( $payment_method, $transaction, $exception, $gateway_code_override, $stub_persist_wallet_title ) extends BasePaymentMethod {

            /**
             * @var TransactionResponse|null
             */
            private $stub_transaction;

            /**
             * @var Throwable|null
             */
            private $stub_exception;

            /**
             * @var bool
             */
            private $stub_persist_wallet_title;

            public function __construct(
                PaymentMethod        $payment_method,
                ?TransactionResponse $transaction,
                ?Throwable           $exception,
                ?string              $gateway_code_override,
                bool                 $stub_persist_wallet_title
            ) {
                $this->stub_transaction          = $transaction;
                $this->stub_exception            = $exception;
                $this->stub_persist_wallet_title = $stub_persist_wallet_title;
                parent::__construct( $payment_method );

                if ( null !== $gateway_code_override ) {
                    $this->gateway_code = $gateway_code_override;
                }
            }

            protected function create_transaction_for_payment( WC_Order $order, OrderService $order_service ): TransactionResponse {
                if ( null !== $this->stub_exception ) {
                    throw $this->stub_exception;
                }

                return $this->stub_transaction ?? new TransactionResponse();
            }

            protected function persist_wallet_combined_payment_method_title_from_transaction( WC_Order $order, $transaction ): bool {
                return $this->stub_persist_wallet_title;
            }
        };
    }

    /**
     * Keeps a direct transaction type for payment-component gateways
     * when Checkout Blocks is active.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::get_payment_method_type
     * @throws InvalidDataInitializationException
     */
    public function test_get_payment_method_type_returns_direct_for_payment_component_when_blocks_active(): void {
        $checkout_post_id = (int) $this->factory->post->create(
            array(
                'post_title'   => 'Checkout',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '<!-- wp:woocommerce/checkout /-->',
            )
        );

        update_option( 'woocommerce_checkout_page_id', $checkout_post_id );

        $gateway = new BasePaymentMethod( new PaymentMethod( ( new PaymentMethodFixture() )->get_amex_payment_method_fixture() ) );

        $this->assertSame( BasePaymentMethod::TRANSACTION_TYPE_DIRECT, $gateway->get_payment_method_type() );
    }
}
