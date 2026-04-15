<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\PaymentMethods\Filters\CancelOrderStatuses;
use MultiSafepay\WooCommerce\Services\PaymentMethodService;
use MultiSafepay\WooCommerce\Utils\Logger;

class Test_CancelOrderStatuses extends WP_UnitTestCase {

    /**
     * @return void
     */
    public function test_adds_on_hold_status_for_multisafepay_gateway_with_on_hold_initial_status() {
        $payment_method_service = $this->getMockBuilder( PaymentMethodService::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_woocommerce_payment_gateway_by_id' ) )
            ->getMock();

        $gateway = $this->getMockBuilder( BasePaymentMethod::class )
            ->disableOriginalConstructor()
            ->getMock();
        $gateway->initial_order_status = 'wc-on-hold';

        $payment_method_service->method( 'get_woocommerce_payment_gateway_by_id' )
            ->willReturn( $gateway );

        $logger = $this->getMockBuilder( Logger::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'log_error' ) )
            ->getMock();

        $filter = new CancelOrderStatuses( $payment_method_service, $logger );

        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_payment_method' ) )
            ->getMock();
        $order->method( 'get_payment_method' )->willReturn( 'multisafepay_ideal' );

        $result = $filter->allow_cancel_multisafepay_orders_with_on_hold_status( array( 'pending' ), $order );

        $this->assertContains( 'on-hold', $result );
    }

    /**
     * @return void
     */
    public function test_does_not_add_duplicate_on_hold_status() {
        $payment_method_service = $this->getMockBuilder( PaymentMethodService::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_woocommerce_payment_gateway_by_id' ) )
            ->getMock();

        $gateway = $this->getMockBuilder( BasePaymentMethod::class )
            ->disableOriginalConstructor()
            ->getMock();
        $gateway->initial_order_status = 'wc-on-hold';

        $payment_method_service->method( 'get_woocommerce_payment_gateway_by_id' )
            ->willReturn( $gateway );

        $logger = $this->getMockBuilder( Logger::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'log_error' ) )
            ->getMock();

        $filter = new CancelOrderStatuses( $payment_method_service, $logger );

        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_payment_method' ) )
            ->getMock();
        $order->method( 'get_payment_method' )->willReturn( 'multisafepay_ideal' );

        $result = $filter->allow_cancel_multisafepay_orders_with_on_hold_status( array( 'pending', 'on-hold' ), $order );

        $this->assertSame( array( 'pending', 'on-hold' ), $result );
    }

    /**
     * @return void
     */
    public function test_logs_actionable_context_when_gateway_not_found() {
        $payment_method_service = $this->getMockBuilder( PaymentMethodService::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_woocommerce_payment_gateway_by_id' ) )
            ->getMock();

        $payment_method_service->method( 'get_woocommerce_payment_gateway_by_id' )
            ->willReturn( null );

        $logger = $this->getMockBuilder( Logger::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'log_error' ) )
            ->getMock();

        $logger->expects( $this->once() )
            ->method( 'log_error' )
            ->with( 'CancelOrderStatuses: gateway not found for order_id=123 payment_method=multisafepay_ideal' );

        $filter = new CancelOrderStatuses( $payment_method_service, $logger );

        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_payment_method', 'get_id' ) )
            ->getMock();
        $order->method( 'get_payment_method' )->willReturn( 'multisafepay_ideal' );
        $order->method( 'get_id' )->willReturn( 123 );

        $result = $filter->allow_cancel_multisafepay_orders_with_on_hold_status( array( 'pending' ), $order );

        $this->assertSame( array( 'pending' ), $result );
    }
}
