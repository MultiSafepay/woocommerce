<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Utils\QrCheckoutManager;

class Test_QrCheckoutManager extends WP_UnitTestCase {

    public function test_returns_true_when_shipping_to_different_address() {
        $posted_data = ['ship_to_different_address' => '1'];
        $manager = new QrCheckoutManager();
        $this->assertTrue($manager->is_shipping_to_different_address($posted_data));
    }

    public function test_returns_false_when_not_shipping_to_different_address() {
        $posted_data = ['ship_to_different_address' => '0'];
        $manager = new QrCheckoutManager();
        $this->assertFalse($manager->is_shipping_to_different_address($posted_data));
    }

    public function test_returns_false_when_ship_to_different_address_not_set() {
        $posted_data = [];
        $manager = new QrCheckoutManager();
        $this->assertFalse($manager->is_shipping_to_different_address($posted_data));
    }

    public function test_returns_false_when_ship_to_different_address_is_invalid() {
        $posted_data = ['ship_to_different_address' => 'invalid'];
        $manager = new QrCheckoutManager();
        $this->assertFalse($manager->is_shipping_to_different_address($posted_data));
    }

    public function test_returns_all_order_fields() {
        $manager = new QrCheckoutManager();
        $expected_fields = [
            'payment_method',
            'shipping_method',
            'order_comments',
            'ip_address',
            'user_agent',
            'wc_order_attribution_source_type',
            'wc_order_attribution_referrer',
            'wc_order_attribution_utm_campaign',
            'wc_order_attribution_utm_source',
            'wc_order_attribution_utm_medium',
            'wc_order_attribution_utm_content',
            'wc_order_attribution_utm_id',
            'wc_order_attribution_utm_term',
            'wc_order_attribution_utm_source_platform',
            'wc_order_attribution_utm_creative_format',
            'wc_order_attribution_utm_marketing_tactic',
            'wc_order_attribution_session_entry',
            'wc_order_attribution_session_start_time',
            'wc_order_attribution_session_pages',
            'wc_order_attribution_session_count',
            'wc_order_attribution_user_agent',
        ];
        $this->assertEquals($expected_fields, $manager->get_order_fields());
        $this->assertCount(21, $manager->get_order_fields());
    }

    public function test_returns_extra_fields() {
        $manager = new QrCheckoutManager();
        $expected_fields = [
            'billing_company',
            'billing_address_2',
            'billing_state',
        ];
        $this->assertEquals($expected_fields, $manager->get_extra_fields());
        $this->assertCount(3, $manager->get_extra_fields());
    }

    public function test_returns_get_required_fields() {
        $manager = new QrCheckoutManager();
        $expected_fields = [
            'billing_first_name',
            'billing_last_name',
            'billing_address_1',
            'billing_city',
            'billing_postcode',
            'billing_country',
            'billing_email',
            'billing_phone',
        ];
        $this->assertEquals($expected_fields, $manager->get_required_fields());
        $this->assertCount(8, $manager->get_required_fields());
    }

    public function returns_posted_data_when_form_data_is_present() {
        $_POST['form_data'] = 'billing_first_name=John&billing_last_name=Doe';
        $manager = new QrCheckoutManager();
        $expected_data = [
            'billing_first_name' => 'John',
            'billing_last_name' => 'Doe',
        ];
        $this->assertEquals($expected_data, $manager->get_posted_data());
    }

    public function returns_empty_array_when_form_data_is_empty() {
        $_POST['form_data'] = '';
        $manager = new QrCheckoutManager();
        $this->assertEmpty($manager->get_posted_data());
    }

    public function test_returns_empty_array_when_form_data_is_not_set() {
        unset($_POST['form_data']);
        $manager = new QrCheckoutManager();
        $this->assertEmpty($manager->get_posted_data());
    }

    public function test_returns_sanitized_posted_data() {
        $_POST['form_data'] = 'billing_first_name=<script>alert("xss")</script>&billing_last_name=Doe';
        $manager = new QrCheckoutManager();
        $expected_data = [
            'billing_first_name' => 'alert("xss")',
            'billing_last_name' => 'Doe',
        ];
        $this->assertEquals($expected_data, $manager->get_posted_data());
    }

    public function test_returns_true_when_nonce_is_valid() {
        $_POST['nonce'] = wp_create_nonce('payment_component_arguments_nonce');
        $manager = new QrCheckoutManager();
        $this->assertTrue($manager->verify_nonce());
    }

    public function test_returns_false_when_nonce_is_invalid() {
        $_POST['nonce'] = 'invalid_nonce';
        $manager = new QrCheckoutManager();
        $this->assertFalse($manager->verify_nonce());
    }

    public function test_returns_false_when_nonce_is_not_set() {
        unset($_POST['nonce']);
        $manager = new QrCheckoutManager();
        $this->assertFalse($manager->verify_nonce());
    }

    public function test_returns_false_when_nonce_is_empty() {
        $_POST['nonce'] = '';
        $manager = new QrCheckoutManager();
        $this->assertFalse($manager->verify_nonce());
    }

    public function test_returns_shipping_rate_when_needed() {
        $cart = $this->getMockBuilder(WC_Cart::class)
            ->setMethods(['calculate_totals', 'needs_shipping', 'get_shipping_methods'])
            ->getMock();
        $cart->method('needs_shipping')->willReturn(true);
        $cart->method('get_shipping_methods')->willReturn([new WC_Shipping_Rate()]);

        WC()->cart = $cart;

        $manager = new QrCheckoutManager();
        $this->assertInstanceOf(WC_Shipping_Rate::class, $manager->get_shipping());
    }

    public function test_returns_null_when_no_shipping_needed() {
        $cart = $this->getMockBuilder(WC_Cart::class)
            ->setMethods(['calculate_totals', 'needs_shipping'])
            ->getMock();
        $cart->method('needs_shipping')->willReturn(false);

        WC()->cart = $cart;

        $manager = new QrCheckoutManager();
        $this->assertNull($manager->get_shipping());
    }

    public function test_returns_null_when_no_shipping_methods() {
        $cart = $this->getMockBuilder(WC_Cart::class)
            ->setMethods(['calculate_totals', 'needs_shipping', 'get_shipping_methods'])
            ->getMock();
        $cart->method('needs_shipping')->willReturn(true);
        $cart->method('get_shipping_methods')->willReturn([]);

        WC()->cart = $cart;

        $manager = new QrCheckoutManager();
        $this->assertNull($manager->get_shipping());
    }

    public function test_returns_cart_items_correctly() {
        $cart = $this->getMockBuilder(WC_Cart::class)
            ->setMethods(['calculate_totals', 'get_cart'])
            ->getMock();
        $cart->method('get_cart')->willReturn([
            'item_key_1' => [
                'product_id'        => 1,
                'variation_id'      => 0,
                'variation'         => [],
                'quantity'          => 2,
                'line_tax_data'     => [],
                'line_subtotal'     => 100.00,
                'line_subtotal_tax' => 10.00,
                'line_tax'          => 10.00,
                'line_total'        => 110.00,
                'data'              => new stdClass(),
            ],
        ]);

        WC()->cart = $cart;

        $manager = new QrCheckoutManager();
        $expected_cart_items = [
            'item_key_1' => [
                'product_id'        => 1,
                'variation_id'      => 0,
                'variation'         => [],
                'quantity'          => 2,
                'line_tax_data'     => [],
                'line_subtotal'     => 100.00,
                'line_subtotal_tax' => 10.00,
                'line_tax'          => 10.00,
                'line_total'        => 110.00,
                'data'              => new stdClass(),
            ],
        ];
        $this->assertEquals($expected_cart_items, $manager->get_cart());
    }

    public function test_returns_empty_cart_when_no_items() {
        $cart = $this->getMockBuilder(WC_Cart::class)
            ->setMethods(['calculate_totals', 'get_cart'])
            ->getMock();
        $cart->method('get_cart')->willReturn([]);

        WC()->cart = $cart;

        $manager = new QrCheckoutManager();
        $this->assertEmpty($manager->get_cart());
    }

    public function test_returns_shipping_fields_excluding_email_and_phone() {
        $manager = new QrCheckoutManager();
        $required_fields = [
            'billing_first_name',
            'billing_last_name',
            'billing_email',
            'billing_phone',
        ];
        $extra_fields = [
            'billing_company',
            'billing_address_2',
        ];

        $expected_fields = [
            'shipping_first_name',
            'shipping_last_name',
            'shipping_company',
            'shipping_address_2',
        ];

        $this->assertEquals($expected_fields, array_values($manager->get_shipping_fields($required_fields, $extra_fields)));
    }

    public function test_returns_empty_array_when_no_fields_provided() {
        $manager = new QrCheckoutManager();
        $required_fields = [];
        $extra_fields = [];
        $this->assertEmpty($manager->get_shipping_fields($required_fields, $extra_fields));
    }

    public function test_returns_only_shipping_fields_from_required_fields() {
        $manager = new QrCheckoutManager();
        $required_fields = [
            'billing_first_name',
            'billing_last_name',
        ];
        $extra_fields = [];
        $expected_fields = [
            'shipping_first_name',
            'shipping_last_name',
        ];
        $this->assertEquals($expected_fields, $manager->get_shipping_fields($required_fields, $extra_fields));
    }

    public function test_returns_only_shipping_fields_from_extra_fields() {
        $manager = new QrCheckoutManager();
        $required_fields = [];
        $extra_fields = [
            'billing_company',
            'billing_address_2',
        ];
        $expected_fields = [
            'shipping_company',
            'shipping_address_2',
        ];
        $this->assertEquals($expected_fields, $manager->get_shipping_fields($required_fields, $extra_fields));
    }

    public function test_returns_false_when_required_fields_are_missing() {
        $_POST['nonce'] = wp_create_nonce('payment_component_arguments_nonce');
        $_POST['form_data'] = 'billing_first_name=&billing_last_name=';
        $manager = new QrCheckoutManager();
        $this->assertFalse($manager->validate_checkout_fields());
    }

    public function test_returns_true_when_all_fields_are_valid() {
        $_POST['nonce'] = wp_create_nonce('payment_component_arguments_nonce');
        $_POST['form_data'] = 'billing_first_name=John&billing_last_name=Doe&billing_address_1=123+Main+St&billing_city=Anytown&billing_postcode=12345&billing_country=NL&billing_email=example%40multisafepay.com&billing_phone=1234567890';
        $manager = new QrCheckoutManager();
        $this->assertTrue($manager->validate_checkout_fields());
    }

    public function test_returns_true_when_shipping_fields_are_missing_but_billing_are_valid() {
        $_POST['nonce'] = wp_create_nonce('payment_component_arguments_nonce');
        $_POST['form_data'] = 'billing_first_name=John&billing_last_name=Doe&billing_address_1=123+Main+St&billing_city=Anytown&billing_postcode=12345&billing_country=NL&billing_email=example%40multisafepay.com&billing_phone=1234567890&ship_to_different_address=1&shipping_first_name=&shipping_last_name=';
        $manager = new QrCheckoutManager();
        $this->assertTrue($manager->validate_checkout_fields());
    }

    public function test_returns_checkout_data_when_validated() {
        $manager = $this->getMockBuilder(QrCheckoutManager::class)
            ->setMethods(['validate_checkout_fields', 'get_cart', 'get_shipping'])
            ->getMock();
        $manager->method('validate_checkout_fields')->willReturn(true);
        $manager->method('get_cart')->willReturn(['cart_item']);
        $manager->method('get_shipping')->willReturn(new WC_Shipping_Rate());

        $manager->validate_checkout_fields();

        $expected_data = [
            'customer' => [
                'billing'  => [],
                'shipping' => [],
            ],
            'order'    => [],
            'cart'     => ['cart_item'],
            'shipping' => new WC_Shipping_Rate(),
            'fees'     => [],
            'coupons'  => [],
            'other'    => [],
        ];

        $this->assertEquals($expected_data, $manager->get_checkout_data());
    }

    public function test_returns_checkout_data_when_not_validated() {
        $manager = $this->getMockBuilder(QrCheckoutManager::class)
            ->setMethods(['validate_checkout_fields', 'get_cart', 'get_shipping'])
            ->getMock();
        $manager->method('validate_checkout_fields')->willReturn(false);
        $manager->method('get_cart')->willReturn(['cart_item']);
        $manager->method('get_shipping')->willReturn(new WC_Shipping_Rate());

        $expected_data = [
            'customer' => [
                'billing'  => [],
                'shipping' => [],
            ],
            'order'    => [],
            'cart'     => ['cart_item'],
            'shipping' => new WC_Shipping_Rate(),
            'fees'     => [],
            'coupons'  => [],
            'other'    => [],
        ];

        $this->assertEquals($expected_data, $manager->get_checkout_data());
    }

    public function test_returns_empty_array_when_no_coupons_applied() {
        $manager = new QrCheckoutManager();

        // Mock WC_Cart to return empty coupons
        WC()->cart = $this->getMockBuilder('WC_Cart')
            ->disableOriginalConstructor()
            ->setMethods(['calculate_totals', 'get_coupons'])
            ->getMock();

        WC()->cart->expects($this->once())
            ->method('calculate_totals');

        WC()->cart->expects($this->once())
            ->method('get_coupons')
            ->willReturn([]);

        $result = $manager->get_coupons();

        $this->assertEquals([], $result);
        $this->assertIsArray($result);
    }

    public function test_returns_coupons_when_coupons_are_applied() {
        $manager = new QrCheckoutManager();

        // Create mock coupons
        $coupon1 = $this->getMockBuilder('WC_Coupon')
            ->disableOriginalConstructor()
            ->getMock();
        $coupon2 = $this->getMockBuilder('WC_Coupon')
            ->disableOriginalConstructor()
            ->getMock();

        // Mock WC_Cart to return coupons
        WC()->cart = $this->getMockBuilder('WC_Cart')
            ->disableOriginalConstructor()
            ->setMethods(['calculate_totals', 'get_coupons'])
            ->getMock();

        WC()->cart->expects($this->once())
            ->method('calculate_totals');

        WC()->cart->expects($this->once())
            ->method('get_coupons')
            ->willReturn(['coupon1' => $coupon1, 'coupon2' => $coupon2]);

        $result = $manager->get_coupons();

        $this->assertEquals([$coupon1, $coupon2], $result);
        $this->assertCount(2, $result);
    }

    public function test_returns_empty_array_when_no_fees_applied() {
        $manager = new QrCheckoutManager();

        // Mock WC_Cart to return empty fees
        WC()->cart = $this->getMockBuilder('WC_Cart')
            ->disableOriginalConstructor()
            ->setMethods(['calculate_totals', 'get_fees'])
            ->getMock();

        WC()->cart->expects($this->once())
            ->method('calculate_totals');

        WC()->cart->expects($this->once())
            ->method('get_fees')
            ->willReturn([]);

        $result = $manager->get_fees();

        $this->assertEquals([], $result);
        $this->assertIsArray($result);
    }

    public function test_returns_fees_when_fees_are_applied() {
        $manager = new QrCheckoutManager();

        // Create mock fees
        $fee1 = $this->getMockBuilder('WC_Cart_Fee')
            ->disableOriginalConstructor()
            ->getMock();
        $fee2 = $this->getMockBuilder('WC_Cart_Fee')
            ->disableOriginalConstructor()
            ->getMock();

        // Mock WC_Cart to return fees
        WC()->cart = $this->getMockBuilder('WC_Cart')
            ->disableOriginalConstructor()
            ->setMethods(['calculate_totals', 'get_fees'])
            ->getMock();

        WC()->cart->expects($this->once())
            ->method('calculate_totals');

        WC()->cart->expects($this->once())
            ->method('get_fees')
            ->willReturn(['fee1' => $fee1, 'fee2' => $fee2]);

        $result = $manager->get_fees();

        $this->assertEquals([$fee1, $fee2], $result);
        $this->assertCount(2, $result);
    }

    public function test_processes_all_fields_correctly() {
        $manager = new QrCheckoutManager();

        $all_fields = ['billing_first_name', 'billing_last_name', 'shipping_address_1'];
        $required_fields = ['billing_first_name', 'billing_last_name'];
        $order_fields = ['payment_method'];

        $_POST['form_data'] = 'billing_first_name=John&billing_last_name=Doe&shipping_address_1=123+Main+St&payment_method=multisafepay_bancontact';
        $manager->posted_data = $manager->get_posted_data();

        $manager->process_checkout_data($all_fields, $required_fields, $order_fields);

        $expected_customer_data = [
            'billing' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
            'shipping' => [
                'address_1' => '123 Main St',
            ],
        ];
        $expected_order_data = [
            'payment_method' => 'multisafepay_bancontact',
        ];

        $this->assertTrue($manager->is_validated);
        $this->assertEquals($expected_customer_data, $manager->customer_data);
        $this->assertEquals($expected_order_data, $manager->order_data);
    }

    public function test_fails_validation_when_required_field_is_empty() {
        $manager = new QrCheckoutManager();

        $all_fields = ['billing_first_name', 'billing_last_name', 'shipping_address_1'];
        $required_fields = ['billing_first_name', 'billing_last_name'];
        $order_fields = ['payment_method'];

        $_POST['form_data'] = 'billing_first_name=John&billing_last_name=&shipping_address_1=123+Main+St&payment_method=multisafepay_bancontact';
        $manager->posted_data = $manager->get_posted_data();

        $manager->process_checkout_data($all_fields, $required_fields, $order_fields);

        $this->assertFalse($manager->is_validated);
    }

    public function test_processes_order_fields_correctly() {
        $manager = new QrCheckoutManager();

        $all_fields = ['billing_first_name', 'billing_last_name'];
        $required_fields = ['billing_first_name', 'billing_last_name'];
        $order_fields = ['payment_method', 'order_comments'];

        $_POST['form_data'] = 'billing_first_name=John&billing_last_name=Doe&payment_method=multisafepay_bancontact&order_comments=Please+deliver+after+5pm';
        $manager->posted_data = $manager->get_posted_data();

        $manager->process_checkout_data($all_fields, $required_fields, $order_fields);

        $expected_order_data = [
            'payment_method' => 'multisafepay_bancontact',
            'order_comments' => 'Please deliver after 5pm',
        ];

        $this->assertTrue($manager->is_validated);
        $this->assertEquals($expected_order_data, $manager->order_data);
    }

    public function test_skips_already_processed_fields() {
        $manager = new QrCheckoutManager();

        $all_fields = ['billing_first_name', 'billing_last_name'];
        $required_fields = ['billing_first_name', 'billing_last_name'];
        $order_fields = ['payment_method'];

        $_POST['form_data'] = 'billing_first_name=John&billing_last_name=Doe&payment_method=multisafepay_bancontact&billing_email=john.doe%40example.com';
        $manager->posted_data = $manager->get_posted_data();

        $manager->process_checkout_data($all_fields, $required_fields, $order_fields);

        $expected_customer_data = [
            'billing' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
            'shipping' => [],
        ];

        $expected_order_data = [
            'payment_method' => 'multisafepay_bancontact',
        ];

        $this->assertTrue($manager->is_validated);
        $this->assertEquals($expected_customer_data, $manager->customer_data);
        $this->assertEquals($expected_order_data, $manager->order_data);
    }
    
    public function test_it_should_return_other_data_with_custom_fields() {
        $posted_data = [
            'billing_first_name' => 'John',
            'billing_last_name' => 'Doe',
            'billing_vat_id' => 'VAT123456',
            'custom_field' => 'Custom Value'
        ];
        $order_data = [];
        $qrCheckoutManager = $this->getMockBuilder(QrCheckoutManager::class)
            ->setMethods(['get_required_fields', 'get_extra_fields', 'is_shipping_to_different_address', 'get_shipping_fields'])
            ->getMock();
        $qrCheckoutManager->posted_data = $posted_data;
        $qrCheckoutManager->order_data = $order_data;

        $qrCheckoutManager->method('get_required_fields')->willReturn(['billing_first_name', 'billing_last_name']);
        $qrCheckoutManager->method('get_extra_fields')->willReturn([]);
        $qrCheckoutManager->method('is_shipping_to_different_address')->willReturn(false);

        $result = $qrCheckoutManager->get_other();

        $this->assertArrayHasKey('billing_vat_id', $result);
        $this->assertArrayHasKey('custom_field', $result);
        $this->assertEquals('VAT123456', $result['billing_vat_id']);
        $this->assertEquals('Custom Value', $result['custom_field']);
    }

    public function test_it_should_exclude_already_processed_fields() {
        $posted_data = [
            'billing_first_name' => 'John',
            'billing_last_name' => 'Doe',
            'billing_vat_id' => 'VAT123456',
            'custom_field' => 'Custom Value'
        ];
        $order_data = ['billing_vat_id' => 'VAT123456'];
        $qrCheckoutManager = $this->getMockBuilder(QrCheckoutManager::class)
            ->setMethods(['get_required_fields', 'get_extra_fields', 'is_shipping_to_different_address', 'get_shipping_fields'])
            ->getMock();
        $qrCheckoutManager->posted_data = $posted_data;
        $qrCheckoutManager->order_data = $order_data;

        $qrCheckoutManager->method('get_required_fields')->willReturn(['billing_first_name', 'billing_last_name']);
        $qrCheckoutManager->method('get_extra_fields')->willReturn([]);
        $qrCheckoutManager->method('is_shipping_to_different_address')->willReturn(false);

        $result = $qrCheckoutManager->get_other();

        $this->assertArrayNotHasKey('billing_vat_id', $result);
        $this->assertArrayHasKey('custom_field', $result);
        $this->assertEquals('Custom Value', $result['custom_field']);
    }

    public function test_it_should_exclude_common_woocommerce_fields() {
        $posted_data = [
            'billing_first_name' => 'John',
            'billing_last_name' => 'Doe',
            'woocommerce-process-checkout-nonce' => 'nonce_value',
            'custom_field' => 'Custom Value'
        ];
        $order_data = [];
        $qrCheckoutManager = $this->getMockBuilder(QrCheckoutManager::class)
            ->setMethods(['get_required_fields', 'get_extra_fields', 'is_shipping_to_different_address', 'get_shipping_fields'])
            ->getMock();
        $qrCheckoutManager->posted_data = $posted_data;
        $qrCheckoutManager->order_data = $order_data;

        $qrCheckoutManager->method('get_required_fields')->willReturn(['billing_first_name', 'billing_last_name']);
        $qrCheckoutManager->method('get_extra_fields')->willReturn([]);
        $qrCheckoutManager->method('is_shipping_to_different_address')->willReturn(false);

        $result = $qrCheckoutManager->get_other();

        $this->assertArrayNotHasKey('woocommerce-process-checkout-nonce', $result);
        $this->assertArrayHasKey('custom_field', $result);
        $this->assertEquals('Custom Value', $result['custom_field']);
    }

    public function test_it_should_handle_array_values() {
        $posted_data = [
            'billing_first_name' => 'John',
            'billing_last_name' => 'Doe',
            'custom_field' => ['value1', 'value2']
        ];
        $order_data = [];
        $qrCheckoutManager = $this->getMockBuilder(QrCheckoutManager::class)
            ->setMethods(['get_required_fields', 'get_extra_fields', 'is_shipping_to_different_address', 'get_shipping_fields'])
            ->getMock();
        $qrCheckoutManager->posted_data = $posted_data;
        $qrCheckoutManager->order_data = $order_data;

        $qrCheckoutManager->method('get_required_fields')->willReturn(['billing_first_name', 'billing_last_name']);
        $qrCheckoutManager->method('get_extra_fields')->willReturn([]);
        $qrCheckoutManager->method('is_shipping_to_different_address')->willReturn(false);

        $result = $qrCheckoutManager->get_other();

        $this->assertArrayHasKey('custom_field', $result);
        $this->assertIsArray($result['custom_field']);
        $this->assertEquals(['value1', 'value2'], $result['custom_field']);
    }
}
