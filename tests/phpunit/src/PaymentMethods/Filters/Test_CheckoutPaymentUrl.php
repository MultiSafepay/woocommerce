<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\PaymentMethods\Filters\CheckoutPaymentUrl;

class Test_CheckoutPaymentUrl extends WP_UnitTestCase {

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

        update_option( 'woocommerce_custom_orders_table_enabled', 'no' );
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
     * @return void
     */
    public function test_replaces_checkout_payment_url_when_payment_link_exists() {
        $order_post_id = self::factory()->post->create();
        update_post_meta( $order_post_id, 'send_payment_link', '1' );
        update_post_meta( $order_post_id, 'payment_url', 'https://example.com/payment-link' );

        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_id' ) )
            ->getMock();
        $order->method( 'get_id' )->willReturn( $order_post_id );

        $filter = new CheckoutPaymentUrl();

        $result = $filter->replace_checkout_payment_url( 'https://example.com/default', $order );

        $this->assertEquals( 'https://example.com/payment-link', $result );
    }

    /**
     * @return void
     */
    public function test_keeps_default_checkout_payment_url_when_payment_link_does_not_exist() {
        $order_post_id = self::factory()->post->create();

        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_id' ) )
            ->getMock();
        $order->method( 'get_id' )->willReturn( $order_post_id );

        $filter = new CheckoutPaymentUrl();

        $result = $filter->replace_checkout_payment_url( 'https://example.com/default', $order );

        $this->assertEquals( 'https://example.com/default', $result );
    }

    /**
     * @return void
     */
    public function test_keeps_default_checkout_payment_url_when_payment_url_is_empty() {
        $order_post_id = self::factory()->post->create();
        update_post_meta( $order_post_id, 'send_payment_link', '1' );
        update_post_meta( $order_post_id, 'payment_url', '' );

        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_id' ) )
            ->getMock();
        $order->method( 'get_id' )->willReturn( $order_post_id );

        $filter = new CheckoutPaymentUrl();

        $result = $filter->replace_checkout_payment_url( 'https://example.com/default', $order );

        $this->assertEquals( 'https://example.com/default', $result );
    }

    /**
     * @return void
     */
    public function test_keeps_default_checkout_payment_url_when_payment_url_is_not_string() {
        $order_post_id = self::factory()->post->create();
        update_post_meta( $order_post_id, 'send_payment_link', '1' );
        update_post_meta( $order_post_id, 'payment_url', array( 'not', 'a', 'string' ) );

        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_id' ) )
            ->getMock();
        $order->method( 'get_id' )->willReturn( $order_post_id );

        $filter = new CheckoutPaymentUrl();

        $result = $filter->replace_checkout_payment_url( 'https://example.com/default', $order );

        $this->assertEquals( 'https://example.com/default', $result );
    }
}
