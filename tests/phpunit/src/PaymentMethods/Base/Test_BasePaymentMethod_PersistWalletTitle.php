<?php declare(strict_types=1);

use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Exception\InvalidDataInitializationException;
use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\Tests\Fixtures\PaymentMethodFixture;

/**
 * Focused tests for BasePaymentMethod::persist_wallet_combined_payment_method_title_from_transaction().
 *
 * The production method talks to PaymentMethodService (hits the MultiSafepay API for the gateway
 * catalogue). The tests use a subclass that overrides resolve_wallet_woocommerce_payment_gateway()
 * to inject a fake wallet gateway, so the method can be exercised deterministically.
 *
 * @covers \MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod::persist_wallet_combined_payment_method_title_from_transaction
 */
class Test_BasePaymentMethod_PersistWalletTitle extends WP_UnitTestCase {

    public function set_up() {
        parent::set_up();

        if ( function_exists( 'WC' ) && WC() && class_exists( 'WC_Order_Factory' ) && ! WC()->order_factory ) {
            WC()->order_factory = new WC_Order_Factory();
        }
    }

    /**
     * Non-object transactions must short-circuit without touching the order.
     * @throws ReflectionException
     * @throws InvalidDataInitializationException
     */
    public function test_returns_false_when_transaction_is_not_an_object(): void {
        $order   = $this->create_persisted_order();
        $gateway = $this->build_gateway_with_stub_wallet_payment_gateway( null );

        $method = $this->get_persist_method();
        $result = $method->invoke( $gateway, $order, null );

        $this->assertFalse( $result );
        $this->assertNotSame( 'Google Pay (Visa)', $order->get_payment_method_title() );
    }

    /**
     * TransactionResponse without any payment_details data must short-circuit.
     * @throws ReflectionException
     * @throws InvalidDataInitializationException
     */
    public function test_returns_false_when_payment_details_has_no_wallet_or_type(): void {
        $order   = $this->create_persisted_order();
        $gateway = $this->build_gateway_with_stub_wallet_payment_gateway( null );

        $transaction = new TransactionResponse( array() );

        $method = $this->get_persist_method();
        $result = $method->invoke( $gateway, $order, $transaction );

        $this->assertFalse( $result );
    }

    /**
     * Unknown instrument gateway codes must not build a combined title.
     * @throws ReflectionException
     * @throws InvalidDataInitializationException
     */
    public function test_returns_false_when_instrument_is_not_combinable(): void {
        $order   = $this->create_persisted_order();
        $gateway = $this->build_gateway_with_stub_wallet_payment_gateway(
            $this->build_fake_wallet_gateway( 'Google Pay' )
        );

        $transaction = new TransactionResponse(
            array(
                'payment_details' => array(
                    'wallet' => 'GOOGLEPAY',
                    'type'   => 'UNKNOWN-BRAND',
                ),
            )
        );

        $method = $this->get_persist_method();
        $result = $method->invoke( $gateway, $order, $transaction );

        $this->assertFalse( $result );
    }

    /**
     * When the PaymentMethodService cannot resolve the wallet gateway, persistence is skipped.
     * @throws ReflectionException
     * @throws InvalidDataInitializationException
     */
    public function test_returns_false_when_wallet_gateway_cannot_be_resolved(): void {
        $order   = $this->create_persisted_order();
        $gateway = $this->build_gateway_with_stub_wallet_payment_gateway( null );

        $transaction = new TransactionResponse(
            array(
                'payment_details' => array(
                    'wallet' => 'GOOGLEPAY',
                    'type'   => 'VISA',
                ),
            )
        );

        $method = $this->get_persist_method();
        $result = $method->invoke( $gateway, $order, $transaction );

        $this->assertFalse( $result );
    }

    /**
     * Resolved wallet gateway with an empty title must short-circuit before writing the order.
     * @throws ReflectionException
     * @throws InvalidDataInitializationException
     */
    public function test_returns_false_when_wallet_title_is_empty(): void {
        $order   = $this->create_persisted_order();
        $gateway = $this->build_gateway_with_stub_wallet_payment_gateway(
            $this->build_fake_wallet_gateway( '' )
        );

        $transaction = new TransactionResponse(
            array(
                'payment_details' => array(
                    'wallet' => 'GOOGLEPAY',
                    'type'   => 'VISA',
                ),
            )
        );

        $method = $this->get_persist_method();
        $result = $method->invoke( $gateway, $order, $transaction );

        $this->assertFalse( $result );
    }

    /**
     * Success path: a known instrument combined with the resolved wallet title is persisted.
     * @throws WC_Data_Exception
     * @throws ReflectionException
     * @throws InvalidDataInitializationException
     */
    public function test_persists_combined_title_and_saves_order_on_success(): void {
        $order = $this->create_persisted_order();
        $order->set_payment_method_title( 'Google Pay' );
        $order->save();

        $gateway = $this->build_gateway_with_stub_wallet_payment_gateway(
            $this->build_fake_wallet_gateway( 'Google Pay' )
        );

        $transaction = new TransactionResponse(
            array(
                'payment_details' => array(
                    'wallet' => 'GOOGLEPAY',
                    'type'   => 'VISA',
                ),
            )
        );

        $method = $this->get_persist_method();
        $result = $method->invoke( $gateway, $order, $transaction );

        $this->assertTrue( $result );

        $fresh_order = wc_get_order( $order->get_id() );
        $this->assertSame( 'Google Pay (Visa)', $fresh_order->get_payment_method_title() );
    }

    /**
     * The success path is idempotent: when the combined title is already persisted, no extra save is needed.
     * @throws WC_Data_Exception
     * @throws ReflectionException
     * @throws InvalidDataInitializationException
     */
    public function test_returns_true_without_rewriting_when_title_already_matches(): void {
        $order = $this->create_persisted_order();
        $order->set_payment_method_title( 'Google Pay (Visa)' );
        $order->save();

        $gateway = $this->build_gateway_with_stub_wallet_payment_gateway(
            $this->build_fake_wallet_gateway( 'Google Pay' )
        );

        $transaction = new TransactionResponse(
            array(
                'payment_details' => array(
                    'wallet' => 'GOOGLEPAY',
                    'type'   => 'VISA',
                ),
            )
        );

        $method = $this->get_persist_method();
        $result = $method->invoke( $gateway, $order, $transaction );

        $this->assertTrue( $result );
        $fresh_order = wc_get_order( $order->get_id() );
        $this->assertSame( 'Google Pay (Visa)', $fresh_order->get_payment_method_title() );
    }

    /**
     * Throwable raised by resolve_wallet_woocommerce_payment_gateway() must be swallowed,
     * logged, and the method must return false.
     * @throws ReflectionException
     * @throws InvalidDataInitializationException
     */
    public function test_returns_false_when_resolver_throws(): void {
        $order   = $this->create_persisted_order();
        $gateway = $this->build_gateway_with_throwing_resolver();

        $transaction = new TransactionResponse(
            array(
                'payment_details' => array(
                    'wallet' => 'GOOGLEPAY',
                    'type'   => 'VISA',
                ),
            )
        );

        $method = $this->get_persist_method();
        $result = $method->invoke( $gateway, $order, $transaction );

        $this->assertFalse( $result );
    }

    /**
     * @return WC_Order
     */
    private function create_persisted_order(): WC_Order {
        $order = new WC_Order();
        $order->save();

        return $order;
    }

    /**
     * Build an invokable ReflectionMethod for the protected persist method.
     *
     * @return ReflectionMethod
     */
    private function get_persist_method(): ReflectionMethod {
        $method = new ReflectionMethod( BasePaymentMethod::class, 'persist_wallet_combined_payment_method_title_from_transaction' );
        $method->setAccessible( true );

        return $method;
    }

    /**
     * Fake BasePaymentMethod used as the "wallet gateway" returned by the resolver.
     *
     * @param string $title
     * @return BasePaymentMethod
     * @throws InvalidDataInitializationException
     */
    private function build_fake_wallet_gateway( string $title ): BasePaymentMethod {
        $payment_method = new PaymentMethod( ( new PaymentMethodFixture() )->get_amex_payment_method_fixture() );

        return new class( $payment_method, $title ) extends BasePaymentMethod {

            /**
             * @var string
             */
            private $forced_title;

            public function __construct( PaymentMethod $payment_method, string $title ) {
                $this->forced_title = $title;
                parent::__construct( $payment_method );
            }

            public function get_payment_method_title(): string {
                return $this->forced_title;
            }
        };
    }

    /**
     * Build a gateway whose resolve_wallet_woocommerce_payment_gateway() returns the given stub.
     *
     * @param BasePaymentMethod|null $wallet_gateway
     * @return BasePaymentMethod
     * @throws InvalidDataInitializationException
     */
    private function build_gateway_with_stub_wallet_payment_gateway( ?BasePaymentMethod $wallet_gateway ): BasePaymentMethod {
        $payment_method = new PaymentMethod( ( new PaymentMethodFixture() )->get_amex_payment_method_fixture() );

        return new class( $payment_method, $wallet_gateway ) extends BasePaymentMethod {

            /**
             * @var BasePaymentMethod|null
             */
            private $stub_wallet_gateway;

            public function __construct( PaymentMethod $payment_method, ?BasePaymentMethod $wallet_gateway ) {
                $this->stub_wallet_gateway = $wallet_gateway;
                parent::__construct( $payment_method );
            }

            protected function resolve_wallet_woocommerce_payment_gateway( string $wallet_code ): ?BasePaymentMethod {
                return $this->stub_wallet_gateway;
            }
        };
    }

    /**
     * Build a gateway whose resolve_wallet_woocommerce_payment_gateway() throws.
     *
     * @return BasePaymentMethod
     * @throws InvalidDataInitializationException
     */
    private function build_gateway_with_throwing_resolver(): BasePaymentMethod {
        $payment_method = new PaymentMethod( ( new PaymentMethodFixture() )->get_amex_payment_method_fixture() );

        return new class( $payment_method ) extends BasePaymentMethod {

            protected function resolve_wallet_woocommerce_payment_gateway( string $wallet_code ): ?BasePaymentMethod {
                throw new RuntimeException( 'boom' );
            }
        };
    }
}
