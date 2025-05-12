<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Services\Qr\QrOrderService;
use MultiSafepay\Api\Transactions\OrderRequest;

class Test_QrOrderService extends WP_UnitTestCase {

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

        $cart_contents = [
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
            ->method('get_cart')
            ->willReturn($cart_contents);
        $cart_mock->expects($this->any())
            ->method('get_fees')
            ->willReturn([]);
        $cart_mock->expects($this->any())
            ->method('get_coupons')
            ->willReturn([]);
        $cart_mock->expects($this->any())
            ->method('needs_shipping')
            ->willReturn(true);
        WC()->cart = $cart_mock;
        return $cart_mock;
    }

    public function test_order_request_is_created_with_valid_data() {

        $this->setup_wc_cart_mock();

        $service = new QrOrderService();
        $checkout_fields = [
            'customer' => [
                'billing' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'example@multisafepay.com',
                    'phone' => '555-123-4567',
                    'address_1' => 'Kranspoor, 39',
                    'address_2' => '',
                    'city' => 'Amsterdam',
                    'state' => 'Noord Holland',
                    'postcode' => '12345',
                    'country' => 'NL',
                    'company' => 'MultiSafepay'
                ]
            ]
        ];
        $order_request = $service->get_order_request('QR-1234567890123', 'MISTERCASH', 'payload', $checkout_fields);
        $this->assertInstanceOf(OrderRequest::class, $order_request);
        $this->assertEquals('QR-1234567890123', $order_request->getOrderId());
    }

    public function test_unique_order_id_is_generated_correctly() {
        $service = new QrOrderService();
        $order_id = $service->generate_unique_order_id();
        $this->assertMatchesRegularExpression('/^QR-[a-zA-Z0-9_.]{13,}$/', $order_id);
    }

    public function test_unique_order_id_is_unique() {
        $service = new QrOrderService();
        $order_id1 = $service->generate_unique_order_id();
        $order_id2 = $service->generate_unique_order_id();
        $this->assertNotEquals($order_id1, $order_id2);
    }

    public function test_qr_data_is_valid_when_all_fields_are_present() {
        $service = new QrOrderService();
        $qr_data = [
            'qr' => [
                'image' => 'some_image_data',
                'params' => [
                    'token' => 'some_token',
                ],
            ],
            'order_id' => 'QR-1234567890123',
        ];
        $order_id = 'QR-1234567890123';
        $this->assertTrue($service->is_valid_qr_data($qr_data, $order_id));
    }

    public function test_qr_data_is_invalid_when_image_is_missing() {
        $service = new QrOrderService();
        $qr_data = [
            'qr' => [
                'params' => [
                    'token' => 'some_token',
                ],
            ],
            'order_id' => 'QR-1234567890123',
        ];
        $order_id = 'QR-1234567890123';
        $this->assertFalse($service->is_valid_qr_data($qr_data, $order_id));
    }

    public function test_qr_data_is_invalid_when_token_is_missing() {
        $service = new QrOrderService();
        $qr_data = [
            'qr' => [
                'image' => 'some_image_data',
            ],
            'order_id' => 'QR-1234567890123',
        ];
        $order_id = 'QR-1234567890123';
        $this->assertFalse($service->is_valid_qr_data($qr_data, $order_id));
    }

    public function test_qr_data_is_invalid_when_order_id_is_missing() {
        $service = new QrOrderService();
        $qr_data = [
            'qr' => [
                'image' => 'some_image_data',
                'params' => [
                    'token' => 'some_token',
                ],
            ],
        ];
        $order_id = 'QR-1234567890123';
        $this->assertFalse($service->is_valid_qr_data($qr_data, $order_id));
    }

    public function test_qr_data_is_invalid_when_order_id_does_not_match() {
        $service = new QrOrderService();
        $qr_data = [
            'qr' => [
                'image' => 'some_image_data',
                'params' => [
                    'token' => 'some_token',
                ],
            ],
            'order_id' => 'QR-1234567890123',
        ];
        $order_id = 'QR-9876543210987';
        $this->assertFalse($service->is_valid_qr_data($qr_data, $order_id));
    }

    public function test_payment_options_are_created() {
        $service = new QrOrderService();
        $payment_options = $service->create_payment_options('fake-token');

        $this->assertEquals(get_rest_url(get_current_blog_id(), 'multisafepay/v1/qr-notification'), $payment_options->getNotificationUrl());
        $redirect_cancel_url = add_query_arg('token', 'fake-token', get_rest_url(get_current_blog_id(), 'multisafepay/v1/qr-balancer'));
        $this->assertEquals($redirect_cancel_url, $payment_options->getCancelUrl());
        $this->assertEquals($redirect_cancel_url, $payment_options->getRedirectUrl());
        $this->assertTrue($payment_options->getSettings()['qr']['enabled']);
    }

    public function test_shipping_address_is_filled_when_address_1_is_present() {
        $service = new QrOrderService();
        $checkout_fields = [
            'customer' => [
                'shipping' => [
                    'address_1' => '123 Main St',
                    'address_2' => '',
                ],
            ],
        ];
        $this->assertTrue($service->is_filled_shipping_address($checkout_fields));
    }

    public function test_shipping_address_is_filled_when_address_2_is_present() {
        $service = new QrOrderService();
        $checkout_fields = [
            'customer' => [
                'shipping' => [
                    'address_1' => '',
                    'address_2' => 'Apt 4B',
                ],
            ],
        ];
        $this->assertTrue($service->is_filled_shipping_address($checkout_fields));
    }

    public function test_shipping_address_is_not_filled_when_both_addresses_are_empty() {
        $service = new QrOrderService();
        $checkout_fields = [
            'customer' => [
                'shipping' => [
                    'address_1' => '',
                    'address_2' => '',
                ],
            ],
        ];
        $this->assertFalse($service->is_filled_shipping_address($checkout_fields));
    }

    public function test_shipping_address_is_not_filled_when_shipping_field_is_missing() {
        $service = new QrOrderService();
        $checkout_fields = [
            'customer' => [],
        ];
        $this->assertFalse($service->is_filled_shipping_address($checkout_fields));
    }

    public function test_shipping_address_is_not_filled_when_customer_field_is_missing() {
        $service = new QrOrderService();
        $checkout_fields = [];
        $this->assertFalse($service->is_filled_shipping_address($checkout_fields));
    }

    public function test_generates_token_with_correct_length() {
        $order_id = '12345';
        $qr_order_service = new QrOrderService();

        $token = $qr_order_service->generate_token($order_id);

        $this->assertEquals(32, strlen($token));
    }

    public function test_stores_token_in_transient() {
        $order_id = '12345';
        $qr_order_service = new QrOrderService();

        $token = $qr_order_service->generate_token($order_id);

        $stored_token = get_transient('multisafepay_token_' . $order_id);
        $this->assertEquals($token, $stored_token);
    }

    public function test_generates_unique_tokens_for_different_order_ids() {
        $order_id_1 = '12345';
        $order_id_2 = '67890';
        $qr_order_service = new QrOrderService();

        $token_1 = $qr_order_service->generate_token($order_id_1);
        $token_2 = $qr_order_service->generate_token($order_id_2);

        $this->assertNotEquals($token_1, $token_2);
    }

    public function test_overwrites_existing_token_for_same_order_id() {
        $order_id = '12345';
        $qr_order_service = new QrOrderService();

        $token_1 = $qr_order_service->generate_token($order_id);
        $token_2 = $qr_order_service->generate_token($order_id);

        $this->assertNotEquals($token_1, $token_2);
        $this->assertEquals($token_2, get_transient('multisafepay_token_' . $order_id));
    }
}
