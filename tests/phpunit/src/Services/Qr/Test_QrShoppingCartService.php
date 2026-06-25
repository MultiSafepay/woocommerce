<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Services\Qr\QrShoppingCartService;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\WooCommerce\Tests\Fixtures\TaxesFixture;

class Test_QrShoppingCartService extends WP_UnitTestCase {


    public $cart_contents;

    public function set_up() {
        parent::set_up();
        update_option( 'woocommerce_calc_taxes', 'yes');
        if ( null === WC()->countries ) {
            WC()->countries = new WC_Countries();
        }
    }

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

    public function test_creates_shopping_cart_handles_free_product_with_multiple_tax_rates() {
        $tax_class_name      = 'Tax Class Name';
        $tax_class_sanitized = sanitize_title( $tax_class_name );

        $tax_fixture = new TaxesFixture( 'Tax Rate Name 21', 21, $tax_class_name );
        $tax_fixture->register_tax_rate();
        $tax_fixture_2 = new TaxesFixture( 'Tax Rate Name 10', 10, $tax_class_name, 2, true );
        $tax_fixture_2->register_tax_rate();

        $cart = $this->getMockBuilder('WC_Cart')
            ->disableOriginalConstructor()
            ->getMock();

        $product_mock = $this->getMockBuilder('WC_Product')
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_name', 'get_id', 'get_price', 'get_sku', 'get_tax_status', 'get_tax_class', 'is_taxable' ) )
            ->getMock();
        $product_mock->method('get_name')->willReturn('Free taxable product');
        $product_mock->method('get_id')->willReturn(123);
        $product_mock->method('get_price')->willReturn(0.00);
        $product_mock->method('get_sku')->willReturn('SKU-123');
        $product_mock->method('get_tax_status')->willReturn('taxable');
        $product_mock->method('get_tax_class')->willReturn($tax_class_sanitized);
        $product_mock->method('is_taxable')->willReturn(true);

        $cart->expects($this->any())
            ->method('get_customer')
            ->willReturn(null);
        $cart->expects($this->any())
            ->method('needs_shipping')
            ->willReturn(false);
        $cart->expects($this->any())
            ->method('get_fees')
            ->willReturn([]);
        $cart->expects($this->any())
            ->method('get_coupons')
            ->willReturn([]);
        $cart->expects($this->any())
            ->method('get_cart')
            ->willReturn(
                array(
                    '123-ABC' => array(
                        'product_id'    => '123-ID',
                        'quantity'      => 1,
                        'line_total'    => 0.00,
                        'line_subtotal' => 0.00,
                        'line_tax'      => 0.00,
                        'data'          => $product_mock,
                    ),
                )
            );

        $service = new QrShoppingCartService();
        $shopping_cart = $service->create_shopping_cart($cart, 'EUR');
        $output = $shopping_cart->getData();

        $product_item = $output['items'][0];

        $this->assertEquals( 'Free taxable product', $product_item['name'] );
        $this->assertEquals( '0.00', $product_item['unit_price'] );
        $this->assertEquals( '33.1', $product_item['tax_table_selector'] );
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

    public function tear_down() {
        TaxesFixture::delete_tax_classes();
        parent::tear_down();
    }
}
