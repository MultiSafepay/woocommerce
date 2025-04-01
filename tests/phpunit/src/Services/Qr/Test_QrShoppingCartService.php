<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Services\Qr\QrShoppingCartService;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart;
use MultiSafepay\Exception\InvalidArgumentException;

class Test_QrShoppingCartService extends WP_UnitTestCase {


    public $cart_contents;

    /**
     * Create and set up a mock WC_Cart object
     *
     * @param float $total The cart total amount to return
     * @return object The mock WC_Cart object
     */
    private function setup_wc_cart_mock($total = 50.00) {
        $cart_mock = $this->getMockBuilder('WC_Cart')
            ->disableOriginalConstructor()
            ->getMock();

        $product_mock = $this->getMockBuilder('WC_Product')
            ->disableOriginalConstructor()
            ->getMock();

        $product_mock->method('get_name')->willReturn('Test Product');
        $product_mock->method('get_id')->willReturn(123);
        $product_mock->method('get_price')->willReturn(20.00);
        $product_mock->method('get_sku')->willReturn('SKU-123');

        $this->cart_contents = [
            '123-ABC' => [
                'product_id' => '123-ID',
                'quantity' => 2,
                'line_total' => 40.00,
                'line_subtotal' => 40.00,
                'line_tax' => 10.00,
                'data' => $product_mock
            ]
        ];
        $cart_mock->expects($this->any())
            ->method('get_total')
            ->willReturn($total);

        $cart_mock->expects($this->any())
            ->method('get_fees')
            ->willReturn([]);

        $cart_mock->expects($this->any())
            ->method('get_coupons')
            ->willReturn([]);

        WC()->cart = $cart_mock;

        return $cart_mock;
    }

    public function test_creates_shopping_cart_with_valid_data() {
        $cart = $this->setup_wc_cart_mock();

        $cart->expects($this->any())
            ->method('needs_shipping')
            ->willReturn(false);

        $cart->expects($this->any())
            ->method('get_cart')
            ->willReturn($this->cart_contents);

        $service = new QrShoppingCartService();
        $shopping_cart = $service->create_shopping_cart($cart, 'EUR');
        $this->assertInstanceOf(ShoppingCart::class, $shopping_cart);
        $this->assertCount(1, $shopping_cart->getItems());
    }

    public function test_creates_shopping_cart_with_shipping() {
        $cart = $this->setup_wc_cart_mock();
        $cart->expects($this->any())
            ->method('needs_shipping')
            ->willReturn(true);
        $cart->expects($this->any())
            ->method('get_cart')
            ->willReturn($this->cart_contents);
        $service = new QrShoppingCartService();
        $shopping_cart = $service->create_shopping_cart($cart, 'USD');
        $this->assertInstanceOf(ShoppingCart::class, $shopping_cart);
        $this->assertCount(2, $shopping_cart->getItems());
    }

    public function test_creates_shopping_cart_with_fees() {
        $cart = $this->setup_wc_cart_mock();
        $cart->expects($this->any())
            ->method('needs_shipping')
            ->willReturn(true);
        $cart->expects($this->any())
            ->method('get_fees')
            ->willReturn([(object) ['name' => 'Fee', 'id' => 'fee-1', 'amount' => 5.00]]);
        $cart->expects($this->any())
            ->method('get_cart')
            ->willReturn($this->cart_contents);
        $service = new QrShoppingCartService();
        $shopping_cart = $service->create_shopping_cart($cart, 'USD');
        $this->assertInstanceOf(ShoppingCart::class, $shopping_cart);
        $this->assertCount(2, $shopping_cart->getItems());
    }

    public function test_handles_empty_cart() {
        $cart = $this->setup_wc_cart_mock();
        $cart->expects($this->any())
            ->method('needs_shipping')
            ->willReturn(false);
        $cart->expects($this->any())
            ->method('get_cart')
            ->willReturn([]);
        $service = new QrShoppingCartService();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No items in cart');
        $service->create_shopping_cart($cart, 'EUR');
    }

    public function test_creates_shopping_cart_with_coupons() {
        $cart = $this->setup_wc_cart_mock();
        $cart->expects($this->any())
            ->method('needs_shipping')
            ->willReturn(true);
        $coupon = $this->getMockBuilder('WC_Coupon')
            ->disableOriginalConstructor()
            ->getMock();
        $cart->expects($this->any())
            ->method('get_cart')
            ->willReturn($this->cart_contents);
        $coupon->method('get_code')->willReturn('DISCOUNT');
        $coupon->method('get_id')->willReturn(1);
        $coupon->method('get_amount')->willReturn(10.00);
        $cart->expects($this->any())
            ->method('get_coupons')
            ->willReturn([$coupon]);
        $service = new QrShoppingCartService();
        $shopping_cart = $service->create_shopping_cart($cart, 'USD');
        $this->assertInstanceOf(ShoppingCart::class, $shopping_cart);
        $this->assertCount(2, $shopping_cart->getItems());
    }

    public function test_it_should_return_true_if_order_is_vat_exempt() {
        $cart = $this->createMock(WC_Cart::class);
        $customer = $this->createMock(WC_Customer::class);

        $cart->method('get_customer')->willReturn($customer);
        $customer->method('is_vat_exempt')->willReturn(true);

        $qrShoppingCartService = new QrShoppingCartService();
        $result = $qrShoppingCartService->is_order_vat_exempt($cart);

        $this->assertTrue($result);
    }

    public function test_it_should_return_false_if_order_is_not_vat_exempt() {
        $cart = $this->createMock(WC_Cart::class);
        $customer = $this->createMock(WC_Customer::class);

        $cart->method('get_customer')->willReturn($customer);
        $customer->method('is_vat_exempt')->willReturn(false);

        $qrShoppingCartService = new QrShoppingCartService();
        $result = $qrShoppingCartService->is_order_vat_exempt($cart);

        $this->assertFalse($result);
    }

    public function test_it_should_handle_null_customer() {
        $cart = $this->createMock(WC_Cart::class);

        $cart->method('get_customer')->willReturn(null);

        $qrShoppingCartService = new QrShoppingCartService();
        $result = $qrShoppingCartService->is_order_vat_exempt($cart);

        $this->assertFalse($result);
    }
}
