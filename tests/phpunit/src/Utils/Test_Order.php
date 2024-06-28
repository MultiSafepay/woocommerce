<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Utils\Order;

class OrderTest extends WP_UnitTestCase {

    public function test_is_multisafepay_order_returns_true_when_payment_method_contains_multisafepay(): void {
        $order = $this->createMock(WC_Order::class);
        $order->method('get_payment_method')->willReturn('multisafepay_example');

        $this->assertTrue(Order::is_multisafepay_order($order));
    }

    public function test_is_multisafepay_order_returns_false_when_payment_method_does_not_contain_multisafepay(): void {
        $order = $this->createMock(WC_Order::class);
        $order->method('get_payment_method')->willReturn('other_payment_method');

        $this->assertFalse(Order::is_multisafepay_order($order));
    }

    public function test_is_multisafepay_order_returns_false_when_payment_method_is_empty(): void {
        $order = $this->createMock(WC_Order::class);
        $order->method('get_payment_method')->willReturn('');

        $this->assertFalse(Order::is_multisafepay_order($order));
    }

    public function test_order_note_when_debug_mode_is_true_and_on_debug_is_false(): void {
        $order = $this->createMock(WC_Order::class);
        $order->expects($this->once())->method('add_order_note');

        update_option('multisafepay_debugmode', true);

        Order::add_order_note($order, 'Test message', false);
    }

    public function test_order_note_when_debug_mode_is_true_and_on_debug_is_true(): void {
        $order = $this->createMock(WC_Order::class);
        $order->expects($this->once())->method('add_order_note');

        update_option('multisafepay_debugmode', true);

        Order::add_order_note($order, 'Test message', true);
    }

    public function test_order_note_when_debug_mode_is_false_and_on_debug_is_true(): void {
        $order = $this->createMock(WC_Order::class);
        $order->expects($this->never())->method('add_order_note');

        update_option('multisafepay_debugmode', false);

        Order::add_order_note($order, 'Test message', true);
    }

    public function test_order_note_when_debug_mode_is_false_and_on_debug_is_false(): void {
        $order = $this->createMock(WC_Order::class);
        $order->expects($this->once())->method('add_order_note');

        update_option('multisafepay_debugmode', false);

        Order::add_order_note($order, 'Test message', false);
    }
}
