<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\PaymentMethods\Filters\PaymentMethodTitle;

class Test_PaymentMethodTitle extends WP_UnitTestCase {

    /**
     * @var PaymentMethodTitle
     */
    private $payment_method_title_filter;

    /**
     * @return void
     */
    public function set_up() {
        parent::set_up();
        $this->payment_method_title_filter = new PaymentMethodTitle();
    }

    /**
     * @return void
     */
    public function tear_down() {
        global $theorder;
        $theorder = null;
        parent::tear_down();
    }

    /**
     * Verifies that the original title is returned when no order is set.
     *
     * @return void
     */
    public function test_returns_original_title_when_order_is_not_set() {
        $result = $this->payment_method_title_filter->filter_gateway_title_by_order_payment_method_title( 'Google Pay', 'multisafepay_googlepay' );
        $this->assertEquals( 'Google Pay', $result );
    }

    /**
     * Verifies that the original title is returned in a non-admin context.
     *
     * @return void
     */
    public function test_returns_original_title_in_non_admin_context() {
        set_current_screen( 'front' );

        $order_mock = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_payment_method', 'get_payment_method_title' ) )
            ->getMock();

        $order_mock->method( 'get_payment_method' )->willReturn( 'multisafepay_googlepay' );
        $order_mock->method( 'get_payment_method_title' )->willReturn( 'Google Pay (Visa)' );

        global $theorder;
        $theorder = $order_mock;

        $result = $this->payment_method_title_filter->filter_gateway_title_by_order_payment_method_title( 'Google Pay', 'multisafepay_googlepay' );

        $this->assertEquals( 'Google Pay', $result );
    }

    /**
     * @return void
     */
    public function test_returns_order_payment_method_title_in_admin_for_matching_gateway() {
        set_current_screen( 'edit-post' );

        $order_mock = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_payment_method', 'get_payment_method_title' ) )
            ->getMock();

        $order_mock->method( 'get_payment_method' )->willReturn( 'multisafepay_googlepay' );
        $order_mock->method( 'get_payment_method_title' )->willReturn( 'Google Pay (Visa)' );

        global $theorder;
        $theorder = $order_mock;

        $result = $this->payment_method_title_filter->filter_gateway_title_by_order_payment_method_title( 'Google Pay', 'multisafepay_googlepay' );

        $this->assertEquals( 'Google Pay (Visa)', $result );
    }

    /**
     * @return void
     */
    public function test_returns_original_title_if_gateway_does_not_match() {
        set_current_screen( 'edit-post' );

        $order_mock = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_payment_method', 'get_payment_method_title' ) )
            ->getMock();

        $order_mock->method( 'get_payment_method' )->willReturn( 'multisafepay_ideal' );
        $order_mock->method( 'get_payment_method_title' )->willReturn( 'iDEAL' );

        global $theorder;
        $theorder = $order_mock;

        $result = $this->payment_method_title_filter->filter_gateway_title_by_order_payment_method_title( 'Google Pay', 'multisafepay_googlepay' );

        $this->assertEquals( 'Google Pay', $result );
    }
}
