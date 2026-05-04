<?php declare(strict_types=1);

require_once dirname( __DIR__, 2 ) . '/stubs/StoreApiPaymentsStubs.php';

use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use MultiSafepay\WooCommerce\Services\BlocksPaymentDataService;
use MultiSafepay\WooCommerce\Utils\Logger;

/**
 * Covers persisting MultiSafepay Blocks/Store API payment data into order meta for downstream services.
 *
 * @covers \MultiSafepay\WooCommerce\Services\BlocksPaymentDataService
 */
class Test_BlocksPaymentDataService extends WP_UnitTestCase {

    /**
     * @var mixed
     */
    private $previous_hpos_enabled_option_value;

    /**
     * @var bool
     */
    private $hpos_enabled_option_exists = false;

    /**
     * @return void
     */
    public function set_up() {
        parent::set_up();

        $this->previous_hpos_enabled_option_value = get_option( 'woocommerce_custom_orders_table_enabled', null );
        $this->hpos_enabled_option_exists         = null !== $this->previous_hpos_enabled_option_value;

        update_option( 'woocommerce_custom_orders_table_enabled', 'yes' );
    }

    /**
     * @return void
     */
    public function tear_down() {
        if ( $this->hpos_enabled_option_exists ) {
            update_option( 'woocommerce_custom_orders_table_enabled', $this->previous_hpos_enabled_option_value );
        } else {
            delete_option( 'woocommerce_custom_orders_table_enabled' );
        }

        parent::tear_down();
    }

    /**
     * Returns an empty string when a requested meta-value is non-scalar.
     *
     * @covers \MultiSafepay\WooCommerce\Services\BlocksPaymentDataService::get_blocks_payment_data_value
     * @return void
     */
    public function test_get_blocks_payment_data_value_returns_empty_string_for_non_scalar_values(): void {
        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_meta' ) )
            ->getMock();

        $order->method( 'get_meta' )->willReturn(
            array(
                'multisafepay_amex_payment_component_payload' => array( 'unexpected' => 'shape' ),
            )
        );

        $service = new BlocksPaymentDataService();
        $value   = $service->get_blocks_payment_data_value( $order, 'multisafepay_amex_payment_component_payload' );

        $this->assertSame( '', $value );
    }

    /**
     * Creates a Store API payment context instance compatible with both the local stubs and real Blocks classes.
     *
     * @param array<string, mixed> $data
     * @return PaymentContext
     * @throws ReflectionException
     */
    private function create_payment_context( array $data ): PaymentContext {
        try {
            $context = new PaymentContext( $data );

            if ( isset( $data['payment_method'] ) && $context->__get( 'payment_method' ) !== $data['payment_method'] ) {
                throw new RuntimeException( 'PaymentContext does not expose expected payment_method value.' );
            }

            return $context;
        } catch ( Throwable $exception ) {
            unset( $exception );
        }

        $reflection = new ReflectionClass( PaymentContext::class );

        /** @var PaymentContext $context */
        $context = $reflection->newInstanceWithoutConstructor();

        foreach ( $data as $key => $value ) {
            if ( ! is_string( $key ) || ! $reflection->hasProperty( $key ) ) {
                continue;
            }

            $property = $reflection->getProperty( $key );
            $property->setAccessible( true );
            $property->setValue( $context, $value );
        }

        return $context;
    }

    /**
     * Creates a Store API payment result instance compatible with both the local stubs and real Blocks classes.
     *
     * @return PaymentResult
     * @throws ReflectionException
     */
    private function create_payment_result(): PaymentResult {
        try {
            return new PaymentResult();
        } catch ( Throwable $exception ) {
            unset( $exception );
        }

        $reflection = new ReflectionClass( PaymentResult::class );

        /** @var PaymentResult $result */
        $result = $reflection->newInstanceWithoutConstructor();
        return $result;
    }

    /**
     * Persists only the allowed MultiSafepay Blocks keys into order meta.
     *
     * @covers \MultiSafepay\WooCommerce\Services\BlocksPaymentDataService::save_blocks_payment_data_to_order_meta
     * @throws ReflectionException
     * @throws Exception
     */
    public function test_save_blocks_payment_data_to_order_meta_saves_filtered_data_to_order_meta(): void {
        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'update_meta_data', 'save' ) )
            ->getMock();

        $expected_payload_key  = 'multisafepay_amex_payment_component_payload';
        $expected_tokenize_key = 'multisafepay_amex_payment_component_tokenize';

        $order->expects( $this->once() )
            ->method( 'update_meta_data' )
            ->with(
                '_multisafepay_blocks_payment_data',
                array(
                    $expected_payload_key  => '{"foo":"bar"}',
                    $expected_tokenize_key => '1',
                )
            );

        $order->expects( $this->once() )
            ->method( 'save' );

        $context = $this->create_payment_context(
            array(
                'payment_method' => 'multisafepay_amex',
                'payment_data'   => array(
                    $expected_payload_key                              => '{"foo":"bar"}',
                    $expected_tokenize_key                             => 1,
                    'multisafepay_amex_other'                           => 'ignored',
                    'other'                                             => 'ignored',
                    'multisafepay_amex_extra_payment_component_payload' => array( 'not-scalar' ),
                ),
                'order'          => $order,
            )
        );

        $result  = $this->create_payment_result();
        $service = new BlocksPaymentDataService();
        $service->save_blocks_payment_data_to_order_meta( $context, $result );
    }

    /**
     * Skips saving when the Store API payment method is not a MultiSafepay gateway.
     *
     * @covers \MultiSafepay\WooCommerce\Services\BlocksPaymentDataService::save_blocks_payment_data_to_order_meta
     * @throws ReflectionException
     * @throws Exception
     */
    public function test_save_blocks_payment_data_to_order_meta_ignores_non_multisafepay_methods(): void {
        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'update_meta_data', 'save' ) )
            ->getMock();

        $order->expects( $this->never() )->method( 'update_meta_data' );
        $order->expects( $this->never() )->method( 'save' );

        $context = $this->create_payment_context(
            array(
                'payment_method' => 'bacs',
                'payment_data'   => array(
                    'multisafepay_amex_payment_component_payload' => 'should-not-be-saved',
                ),
                'order'          => $order,
            )
        );

        $result  = $this->create_payment_result();
        $service = new BlocksPaymentDataService();
        $service->save_blocks_payment_data_to_order_meta( $context, $result );
    }

    /**
     * Skips saving when payment_data contains no allowed keys.
     *
     * @covers \MultiSafepay\WooCommerce\Services\BlocksPaymentDataService::save_blocks_payment_data_to_order_meta
     * @throws ReflectionException
     * @throws Exception
     */
    public function test_save_blocks_payment_data_to_order_meta_does_not_save_when_no_allowed_keys_present(): void {
        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'update_meta_data', 'save' ) )
            ->getMock();

        $order->expects( $this->never() )->method( 'update_meta_data' );
        $order->expects( $this->never() )->method( 'save' );

        $context = $this->create_payment_context(
            array(
                'payment_method' => 'multisafepay_amex',
                'payment_data'   => array(
                    'multisafepay_amex_other' => 'ignored',
                    'other'                  => 'ignored',
                ),
                'order'          => $order,
            )
        );

        $result  = $this->create_payment_result();
        $service = new BlocksPaymentDataService();
        $service->save_blocks_payment_data_to_order_meta( $context, $result );
    }

    /**
     * Skips saving when the payment context does not contain a valid WC_Order instance.
     *
     * @covers \MultiSafepay\WooCommerce\Services\BlocksPaymentDataService::save_blocks_payment_data_to_order_meta
     * @throws ReflectionException
     * @throws Exception
     */
    public function test_save_blocks_payment_data_to_order_meta_does_not_save_when_order_is_invalid(): void {
        $context = $this->create_payment_context(
            array(
                'payment_method' => 'multisafepay_amex',
                'payment_data'   => array(
                    'multisafepay_amex_payment_component_payload' => 'payload',
                ),
                'order'          => null,
            )
        );

        $result  = $this->create_payment_result();
        $service = new BlocksPaymentDataService();
        $service->save_blocks_payment_data_to_order_meta( $context, $result );

        $this->assertTrue( true );
    }

    /**
     * Throws a controlled error when an allowed Blocks payment value is too large to persist.
     *
     * @covers \MultiSafepay\WooCommerce\Services\BlocksPaymentDataService::save_blocks_payment_data_to_order_meta
     * @throws ReflectionException
     * @throws Exception
     */
    public function test_save_blocks_payment_data_to_order_meta_throws_when_value_exceeds_limit(): void {
        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_id', 'update_meta_data', 'save' ) )
            ->getMock();

        $order->method( 'get_id' )->willReturn( 123 );
        $order->expects( $this->never() )->method( 'update_meta_data' );
        $order->expects( $this->never() )->method( 'save' );

        $logger = $this->getMockBuilder( Logger::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'log_warning' ) )
            ->getMock();

        $logger->expects( $this->once() )
            ->method( 'log_warning' )
            ->with( $this->stringContains( 'Rejected oversized Blocks payment data for order ID 123, key multisafepay_amex_payment_component_payload' ) );

        $context = $this->create_payment_context(
            array(
                'payment_method' => 'multisafepay_amex',
                'payment_data'   => array(
                    'multisafepay_amex_payment_component_payload' => str_repeat( 'a', 20001 ),
                ),
                'order'          => $order,
            )
        );

        $this->expectException( Exception::class );
        $this->expectExceptionMessage( 'We could not process your payment details. Please try again.' );

        $result  = $this->create_payment_result();
        $service = new BlocksPaymentDataService( $logger );
        $service->save_blocks_payment_data_to_order_meta( $context, $result );
    }

    /**
     * Removes request-lifecycle keys while keeping unrelated non-sensitive fields.
     *
     * @covers \MultiSafepay\WooCommerce\Services\BlocksPaymentDataService::clear_blocks_payment_data_from_order_meta_by_pattern
     * @return void
     */
    public function test_clear_request_lifecycle_blocks_payment_data_from_order_meta_keeps_non_sensitive_keys(): void {
        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_meta', 'update_meta_data', 'delete_meta_data', 'save' ) )
            ->getMock();

        $order->method( 'get_meta' )->willReturn(
            array(
                'multisafepay_amex_other'                    => 'keep-me',
                'multisafepay_amex_payment_component_payload'  => '{"foo":"bar"}',
                'multisafepay_amex_payment_component_tokenize' => '1',
                'multisafepay_amex_payment_token'              => '{"signature":"abc"}',
                'browser'                                      => '{"javaEnabled":true}',
            )
        );

        $order->expects( $this->once() )
            ->method( 'update_meta_data' )
            ->with(
                '_multisafepay_blocks_payment_data',
                array(
                    'multisafepay_amex_other' => 'keep-me',
                )
            );

        $order->expects( $this->never() )
            ->method( 'delete_meta_data' );

        $order->expects( $this->once() )
            ->method( 'save' );

        $service = new BlocksPaymentDataService();
        $service->clear_blocks_payment_data_from_order_meta_by_pattern( $order, BlocksPaymentDataService::REQUEST_LIFECYCLE_KEY_PATTERN );
    }

    /**
     * Deletes the full Blocks payment data meta when it only contains request-lifecycle keys.
     *
        * @covers \MultiSafepay\WooCommerce\Services\BlocksPaymentDataService::clear_blocks_payment_data_from_order_meta_by_pattern
     * @return void
     */
    public function test_clear_request_lifecycle_blocks_payment_data_from_order_meta_deletes_meta_when_only_request_lifecycle_keys_exist(): void {
        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_meta', 'update_meta_data', 'delete_meta_data', 'save' ) )
            ->getMock();

        $order->method( 'get_meta' )->willReturn(
            array(
                'multisafepay_amex_payment_component_payload'  => '{"foo":"bar"}',
                'multisafepay_amex_payment_component_tokenize' => '1',
                'payment_token'                                => '{"signature":"abc"}',
                'multisafepay_amex_browser'                    => '{"javaEnabled":true}',
            )
        );

        $order->expects( $this->never() )
            ->method( 'update_meta_data' );

        $order->expects( $this->once() )
            ->method( 'delete_meta_data' )
            ->with( '_multisafepay_blocks_payment_data' );

        $order->expects( $this->once() )
            ->method( 'save' );

        $service = new BlocksPaymentDataService();
        $service->clear_blocks_payment_data_from_order_meta_by_pattern( $order, BlocksPaymentDataService::REQUEST_LIFECYCLE_KEY_PATTERN );
    }
}
