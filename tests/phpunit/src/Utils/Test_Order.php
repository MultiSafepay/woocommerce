<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Utils\Order;

class OrderTest extends WP_UnitTestCase {

    public function testIsMultisafepayOrderReturnsTrueWhenPaymentMethodContainsMultisafepay(): void {
        $order = $this->createMock(WC_Order::class);
        $order->method('get_payment_method')->willReturn('multisafepay_example');

        $this->assertTrue(Order::is_multisafepay_order($order));
    }

    public function testIsMultisafepayOrderReturnsFalseWhenPaymentMethodDoesNotContainMultisafepay(): void {
        $order = $this->createMock(WC_Order::class);
        $order->method('get_payment_method')->willReturn('other_payment_method');

        $this->assertFalse(Order::is_multisafepay_order($order));
    }

    public function testIsMultisafepayOrderReturnsFalseWhenPaymentMethodIsEmpty(): void {
        $order = $this->createMock(WC_Order::class);
        $order->method('get_payment_method')->willReturn('');

        $this->assertFalse(Order::is_multisafepay_order($order));
    }

    public function orderNoteIsAddedWhenDebugModeIsOnAndOnlyOnDebugIsFalse(): void {
        $order = $this->createMock(WC_Order::class);
        $order->expects($this->once())->method('add_order_note');

        update_option('multisafepay_debugmode', true);

        Order::add_order_note($order, 'Test message', false);
    }

    public function orderNoteIsNotAddedWhenDebugModeIsOffAndOnlyOnDebugIsFalse(): void {
        $order = $this->createMock(WC_Order::class);
        $order->expects($this->never())->method('add_order_note');

        update_option('multisafepay_debugmode', false);

        Order::add_order_note($order, 'Test message', false);
    }

    public function orderNoteIsAddedWhenDebugModeIsOnAndOnlyOnDebugIsTrue(): void {
        $order = $this->createMock(WC_Order::class);
        $order->expects($this->once())->method('add_order_note');

        update_option('multisafepay_debugmode', true);

        Order::add_order_note($order, 'Test message', true);
    }

    public function orderNoteIsNotAddedWhenDebugModeIsOffAndOnlyOnDebugIsTrue(): void {
        $order = $this->createMock(WC_Order::class);
        $order->expects($this->never())->method('add_order_note');

        update_option('multisafepay_debugmode', false);

        Order::add_order_note($order, 'Test message', true);
    }
}
