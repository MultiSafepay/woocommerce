<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Services\CustomerService;

/**
 * Covers customer and delivery details creation, including Blocks/Store API meta-fallback for Payment Components.
 *
 * @covers \MultiSafepay\WooCommerce\Services\CustomerService
 */
class Test_CustomerService extends WP_UnitTestCase {

    /**
     * @var WC_Order
     */
    private $mock;

    public function set_up() {
        parent::set_up();
        $this->mock = $this->getMockBuilder(WC_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'get_id',
                    'get_billing_address_1',
                    'get_billing_address_2',
                    'get_billing_country',
                    'get_billing_state',
                    'get_billing_city',
                    'get_billing_postcode',
                    'get_billing_email',
                    'get_billing_phone',
                    'get_billing_first_name',
                    'get_billing_last_name',
                    'get_billing_company',
                    'get_shipping_address_1',
                    'get_shipping_address_2',
                    'get_shipping_country',
                    'get_shipping_state',
                    'get_shipping_city',
                    'get_shipping_first_name',
                    'get_shipping_last_name',
                    'get_shipping_company',
                    'get_shipping_postcode',
                    'get_customer_ip_address',
                    'get_customer_user_agent'
                )
            )->getMock();
        $this->mock->method('get_id')->will($this->returnValue(5));
        $this->mock->method('get_billing_address_1')->will($this->returnValue('Kraanspoor'));
        $this->mock->method('get_billing_address_2')->will($this->returnValue('39C'));
        $this->mock->method('get_billing_country')->will($this->returnValue('NL'));
        $this->mock->method('get_billing_state')->will($this->returnValue(''));
        $this->mock->method('get_billing_city')->will($this->returnValue('Amsterdam'));
        $this->mock->method('get_billing_postcode')->will($this->returnValue('1033 SC'));
        $this->mock->method('get_billing_email')->will($this->returnValue('john.doe@multisafepay.com'));
        $this->mock->method('get_billing_phone')->will($this->returnValue('123456789'));
        $this->mock->method('get_billing_first_name')->will($this->returnValue('John'));
        $this->mock->method('get_billing_last_name')->will($this->returnValue('Doe'));
        $this->mock->method('get_billing_company')->will($this->returnValue('MultiSafepay'));
        $this->mock->method('get_customer_ip_address')->will($this->returnValue('127.0.0.1'));
        $this->mock->method('get_customer_user_agent')->will($this->returnValue('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.121 Safari/537.36'));
        $this->mock->method('get_shipping_address_1')->will($this->returnValue('Kraanspoor'));
        $this->mock->method('get_shipping_address_2')->will($this->returnValue('39C'));
        $this->mock->method('get_shipping_country')->will($this->returnValue('NL'));
        $this->mock->method('get_shipping_state')->will($this->returnValue(''));
        $this->mock->method('get_shipping_city')->will($this->returnValue('Amsterdam'));
        $this->mock->method('get_shipping_postcode')->will($this->returnValue('1033 SC'));
        $this->mock->method('get_shipping_first_name')->will($this->returnValue('John'));
        $this->mock->method('get_shipping_last_name')->will($this->returnValue('Doe'));
        $this->mock->method('get_shipping_company')->will($this->returnValue('MultiSafepay'));
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\CustomerService::create_customer_details
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_customer_details_has_keys() {
        $customer_service = new CustomerService();
        $customer_details = $customer_service->create_customer_details($this->mock);
        $output = $customer_details->getData();
        $this->assertArrayHasKey( 'firstname', $output );
        $this->assertArrayHasKey( 'lastname', $output );
        $this->assertArrayHasKey( 'company_name', $output );
        $this->assertArrayHasKey( 'address1', $output );
        $this->assertArrayHasKey( 'address2', $output );
        $this->assertArrayHasKey( 'house_number', $output );
        $this->assertArrayHasKey( 'zip_code', $output );
        $this->assertArrayHasKey( 'city', $output );
        $this->assertArrayHasKey( 'state', $output );
        $this->assertArrayHasKey( 'country', $output );
        $this->assertArrayHasKey( 'phone', $output );
        $this->assertArrayHasKey( 'email', $output );
        $this->assertArrayHasKey( 'ip_address', $output );
        $this->assertArrayHasKey( 'locale', $output );
        $this->assertArrayHasKey( 'referrer', $output );
        $this->assertArrayHasKey( 'forwarded_ip', $output );
        $this->assertArrayHasKey( 'user_agent', $output );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\CustomerService::create_delivery_details
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_delivery_details_has_keys() {
        $customer_service = new CustomerService();
        $customer_details = $customer_service->create_delivery_details($this->mock);
        $output = $customer_details->getData();
        $this->assertArrayHasKey( 'firstname', $output );
        $this->assertArrayHasKey( 'lastname', $output );
        $this->assertArrayHasKey( 'company_name', $output );
        $this->assertArrayHasKey( 'address1', $output );
        $this->assertArrayHasKey( 'address2', $output );
        $this->assertArrayHasKey( 'house_number', $output );
        $this->assertArrayHasKey( 'zip_code', $output );
        $this->assertArrayHasKey( 'city', $output );
        $this->assertArrayHasKey( 'state', $output );
        $this->assertArrayHasKey( 'country', $output );
        $this->assertArrayHasKey( 'phone', $output );
        $this->assertArrayHasKey( 'email', $output );
        $this->assertArrayHasKey( 'ip_address', $output );
        $this->assertArrayHasKey( 'locale', $output );
        $this->assertArrayHasKey( 'referrer', $output );
        $this->assertArrayHasKey( 'forwarded_ip', $output );
        $this->assertArrayHasKey( 'user_agent', $output );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\CustomerService::create_customer_details
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_customer_details_has_values() {
        $customer_service = new CustomerService();
        $customer_details = $customer_service->create_customer_details($this->mock);
        $output = $customer_details->getData();
        $this->assertEquals( 'John', $output['firstname'] );
        $this->assertEquals( 'Doe', $output['lastname'] );
        $this->assertEquals( 'MultiSafepay', $output['company_name'] );
        $this->assertEquals( 'Kraanspoor', $output['address1'] );
        $this->assertEquals( '', $output['address2'] );
        $this->assertEquals( '39C', $output['house_number'] );
        $this->assertEquals( '1033 SC', $output['zip_code'] );
        $this->assertEquals( 'Amsterdam', $output['city'] );
        $this->assertEquals( '', $output['state'] );
        $this->assertEquals( 'NL', $output['country'] );
        $this->assertEquals( '123456789', $output['phone'] );
        $this->assertEquals( 'john.doe@multisafepay.com', $output['email'] );
        $this->assertEquals( '127.0.0.1', $output['ip_address'] );
        $this->assertEquals( 'en_US', $output['locale'] );
        $this->assertEquals( '', $output['referrer'] );
        $this->assertEquals( '', $output['forwarded_ip'] );
        $this->assertEquals( 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.121 Safari/537.36', $output['user_agent'] );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\CustomerService::create_delivery_details
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_delivery_details_has_values() {
        $customer_service = new CustomerService();
        $customer_details = $customer_service->create_delivery_details($this->mock);
        $output = $customer_details->getData();
        $this->assertEquals( 'John', $output['firstname'] );
        $this->assertEquals( 'Doe', $output['lastname'] );
        $this->assertEquals( 'MultiSafepay', $output['company_name'] );
        $this->assertEquals( 'Kraanspoor', $output['address1'] );
        $this->assertEquals( '', $output['address2'] );
        $this->assertEquals( '39C', $output['house_number'] );
        $this->assertEquals( '1033 SC', $output['zip_code'] );
        $this->assertEquals( 'Amsterdam', $output['city'] );
        $this->assertEquals( '', $output['state'] );
        $this->assertEquals( 'NL', $output['country'] );
        $this->assertEquals( '123456789', $output['phone'] );
        $this->assertEquals( 'john.doe@multisafepay.com', $output['email'] );
        $this->assertEquals( '', $output['ip_address'] );
        $this->assertEquals( 'en_US', $output['locale'] );
        $this->assertEquals( '', $output['referrer'] );
        $this->assertEquals( '', $output['forwarded_ip'] );
    }

    /**
     * Sets customer reference when a Blocks meta tokenize flag is truthy.
     *
     * @covers \MultiSafepay\WooCommerce\Services\CustomerService::create_customer_details
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_customer_details_sets_reference_when_blocks_tokenize_is_true() {
        $_POST = array();

        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'get_id',
                    'get_payment_method',
                    'get_meta',
                    'get_customer_id',
                    'get_billing_address_1',
                    'get_billing_address_2',
                    'get_billing_country',
                    'get_billing_state',
                    'get_billing_city',
                    'get_billing_postcode',
                    'get_billing_email',
                    'get_billing_phone',
                    'get_billing_first_name',
                    'get_billing_last_name',
                    'get_billing_company',
                    'get_customer_ip_address',
                    'get_customer_user_agent',
                )
            )
            ->getMock();

        $order->method( 'get_id' )->willReturn( 5 );
        $order->method( 'get_payment_method' )->willReturn( 'multisafepay_amex' );
        $order->method( 'get_customer_id' )->willReturn( 42 );

        $order->method( 'get_meta' )->willReturn(
            array(
                'multisafepay_amex_payment_component_tokenize' => '1',
            )
        );

        $order->method('get_billing_address_1')->willReturn('Kraanspoor');
        $order->method('get_billing_address_2')->willReturn('39C');
        $order->method('get_billing_country')->willReturn('NL');
        $order->method('get_billing_state')->willReturn('');
        $order->method('get_billing_city')->willReturn('Amsterdam');
        $order->method('get_billing_postcode')->willReturn('1033 SC');
        $order->method('get_billing_email')->willReturn('john.doe@multisafepay.com');
        $order->method('get_billing_phone')->willReturn('123456789');
        $order->method('get_billing_first_name')->willReturn('John');
        $order->method('get_billing_last_name')->willReturn('Doe');
        $order->method('get_billing_company')->willReturn('MultiSafepay');
        $order->method('get_customer_ip_address')->willReturn('127.0.0.1');
        $order->method('get_customer_user_agent')->willReturn('Mozilla/5.0');

        $customer_service = new CustomerService();
        $customer_details = $customer_service->create_customer_details( $order );
        $output           = $customer_details->getData();

        $this->assertEquals( '42', $output['reference'] );
    }

    /**
     * Does not set customer reference when a Blocks meta tokenize flag is falsy.
     *
     * @covers \MultiSafepay\WooCommerce\Services\CustomerService::create_customer_details
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_customer_details_does_not_set_reference_when_blocks_tokenize_is_false() {
        $_POST = array();

        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'get_id',
                    'get_payment_method',
                    'get_meta',
                    'get_customer_id',
                    'get_billing_address_1',
                    'get_billing_address_2',
                    'get_billing_country',
                    'get_billing_state',
                    'get_billing_city',
                    'get_billing_postcode',
                    'get_billing_email',
                    'get_billing_phone',
                    'get_billing_first_name',
                    'get_billing_last_name',
                    'get_billing_company',
                    'get_customer_ip_address',
                    'get_customer_user_agent',
                )
            )
            ->getMock();

        $order->method( 'get_id' )->willReturn( 5 );
        $order->method( 'get_payment_method' )->willReturn( 'multisafepay_amex' );
        $order->method( 'get_customer_id' )->willReturn( 42 );

        $order->method( 'get_meta' )->willReturn(
            array(
                'multisafepay_amex_payment_component_tokenize' => '',
            )
        );

        $order->method('get_billing_address_1')->willReturn('Kraanspoor');
        $order->method('get_billing_address_2')->willReturn('39C');
        $order->method('get_billing_country')->willReturn('NL');
        $order->method('get_billing_state')->willReturn('');
        $order->method('get_billing_city')->willReturn('Amsterdam');
        $order->method('get_billing_postcode')->willReturn('1033 SC');
        $order->method('get_billing_email')->willReturn('john.doe@multisafepay.com');
        $order->method('get_billing_phone')->willReturn('123456789');
        $order->method('get_billing_first_name')->willReturn('John');
        $order->method('get_billing_last_name')->willReturn('Doe');
        $order->method('get_billing_company')->willReturn('MultiSafepay');
        $order->method('get_customer_ip_address')->willReturn('127.0.0.1');
        $order->method('get_customer_user_agent')->willReturn('Mozilla/5.0');

        $customer_service = new CustomerService();
        $customer_details = $customer_service->create_customer_details( $order );
        $output           = $customer_details->getData();

        $this->assertNull( $output['reference'] );
    }

    /**
     * Does not set customer reference when Blocks tokenize is explicitly zero.
     *
     * @covers \MultiSafepay\WooCommerce\Services\CustomerService::create_customer_details
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_customer_details_does_not_set_reference_when_blocks_tokenize_is_zero() {
        $_POST = array(
            'multisafepay_amex_payment_component_tokenize' => '1',
        );

        $order = $this->create_order_for_customer_reference_test( '0' );

        $customer_service = new CustomerService();
        $customer_details = $customer_service->create_customer_details( $order );
        $output           = $customer_details->getData();

        $this->assertNull( $output['reference'] );
    }

    /**
     * Does not set customer reference when Blocks tokenize is the string false.
     *
     * @covers \MultiSafepay\WooCommerce\Services\CustomerService::create_customer_details
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_customer_details_does_not_set_reference_when_blocks_tokenize_is_false_string() {
        $_POST = array(
            'multisafepay_amex_payment_component_tokenize' => '1',
        );

        $order = $this->create_order_for_customer_reference_test( 'false' );

        $customer_service = new CustomerService();
        $customer_details = $customer_service->create_customer_details( $order );
        $output           = $customer_details->getData();

        $this->assertNull( $output['reference'] );
    }

    /**
     * Does not set customer reference when Blocks tokenize is unrecognized text.
     *
     * @covers \MultiSafepay\WooCommerce\Services\CustomerService::create_customer_details
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_customer_details_does_not_set_reference_when_blocks_tokenize_is_unrecognized_text() {
        $_POST = array(
            'multisafepay_amex_payment_component_tokenize' => '1',
        );

        $order = $this->create_order_for_customer_reference_test( 'foo' );

        $customer_service = new CustomerService();
        $customer_details = $customer_service->create_customer_details( $order );
        $output           = $customer_details->getData();

        $this->assertNull( $output['reference'] );
    }

    /**
     * Uses POST tokenize fallback when Blocks tokenize is empty.
     *
     * @covers \MultiSafepay\WooCommerce\Services\CustomerService::create_customer_details
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_customer_details_sets_reference_from_post_when_blocks_tokenize_is_empty() {
        $_POST = array(
            'multisafepay_amex_payment_component_tokenize' => '1',
        );

        $order = $this->create_order_for_customer_reference_test( '' );

        $customer_service = new CustomerService();
        $customer_details = $customer_service->create_customer_details( $order );
        $output           = $customer_details->getData();

        $this->assertEquals( '42', $output['reference'] );
    }

    /**
     * Does not set customer reference when POST tokenize fallback is malformed.
     *
     * @covers \MultiSafepay\WooCommerce\Services\CustomerService::create_customer_details
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_customer_details_does_not_set_reference_when_post_tokenize_is_array() {
        $_POST = array(
            'multisafepay_amex_payment_component_tokenize' => array( '1' ),
        );

        $order = $this->create_order_for_customer_reference_test( '' );

        $customer_service = new CustomerService();
        $customer_details = $customer_service->create_customer_details( $order );
        $output           = $customer_details->getData();

        $this->assertNull( $output['reference'] );
    }

    /**
     * Includes browser data from classic checkout POST payloads.
     *
     * @covers \MultiSafepay\WooCommerce\Services\CustomerService::create_customer_details
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_customer_details_includes_browser_data_from_classic_post() {
        $original_post = $_POST;
        $original_get  = $_GET;

        try {
            $_POST = array(
                'browser' => wp_json_encode(
                    array(
                        'browser' => array(
                            'javascript_enabled' => true,
                            'java_enabled'       => false,
                            'cookies_enabled'    => true,
                            'language'           => 'zh-Hant-HK',
                            'screen_color_depth' => '48',
                            'screen_height'      => '1234567',
                            'screen_width'       => '999999',
                            'time_zone'          => '-123456',
                            'user_agent'         => str_repeat( 'u', 600 ),
                            'platform'           => str_repeat( 'p', 140 ),
                            'ignored'            => 'value',
                        ),
                    )
                ),
            );
            unset( $_GET['rest_route'] );

            $customer_service = new CustomerService();
            $customer_details = $customer_service->create_customer_details( $this->mock );
            $output           = $customer_details->getData();

            $this->assertSame( 'zh-Hant-HK', $output['browser']['language'] ?? null );
            $this->assertTrue( $output['browser']['javascript_enabled'] ?? false );
            $this->assertFalse( $output['browser']['java_enabled'] ?? true );
            $this->assertTrue( $output['browser']['cookies_enabled'] ?? false );
            $this->assertSame( 48, $output['browser']['screen_color_depth'] ?? null );
            $this->assertSame( 1234567, $output['browser']['screen_height'] ?? null );
            $this->assertSame( 999999, $output['browser']['screen_width'] ?? null );
            $this->assertSame( -1440, $output['browser']['time_zone'] ?? null );
            $this->assertSame( 512, strlen( $output['browser']['user_agent'] ?? '' ) );
            $this->assertSame( 128, strlen( $output['browser']['platform'] ?? '' ) );
            $this->assertArrayNotHasKey( 'ignored', $output['browser'] ?? array() );
        } finally {
            $_POST = $original_post;
            $_GET  = $original_get;
        }
    }

    /**
     * Omits browser colour depth values outside the documented 3DS set.
     *
     * @covers \MultiSafepay\WooCommerce\Services\CustomerService::create_customer_details
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_customer_details_omits_invalid_browser_color_depth() {
        $original_post = $_POST;
        $original_get  = $_GET;

        try {
            $_POST = array(
                'browser' => wp_json_encode(
                    array(
                        'browser' => array(
                            'javascript_enabled' => true,
                            'screen_color_depth' => '12345',
                        ),
                    )
                ),
            );
            unset( $_GET['rest_route'] );

            $customer_service = new CustomerService();
            $customer_details = $customer_service->create_customer_details( $this->mock );
            $output           = $customer_details->getData();

            $this->assertTrue( $output['browser']['javascript_enabled'] ?? false );
            $this->assertArrayNotHasKey( 'screen_color_depth', $output['browser'] ?? array() );
        } finally {
            $_POST = $original_post;
            $_GET  = $original_get;
        }
    }

    /**
     * Includes browser data from Blocks order meta when the request payload does not include it.
     *
     * @covers \MultiSafepay\WooCommerce\Services\CustomerService::create_customer_details
     * @throws \MultiSafepay\Exception\InvalidArgumentException
     */
    public function test_create_customer_details_includes_browser_data_from_blocks_order_meta() {
        $original_post = $_POST;
        $original_get  = $_GET;

        try {
            $_POST               = array();
            $_GET['rest_route']  = '/wc/store/v1/checkout';
            $browser_from_blocks = '{"browser":{"platform":"MacIntel","javascript_enabled":true}}';
            $order               = $this->create_order_for_customer_reference_test(
                '',
                array(
                    'multisafepay_amex_browser' => $browser_from_blocks,
                )
            );

            $customer_service = new CustomerService();
            $customer_details = $customer_service->create_customer_details( $order );
            $output           = $customer_details->getData();

            $this->assertSame( 'MacIntel', $output['browser']['platform'] ?? null );
            $this->assertTrue( $output['browser']['javascript_enabled'] ?? false );
        } finally {
            $_POST = $original_post;
            $_GET  = $original_get;
        }
    }

    /**
     * Create an order mock for customer reference to tokenize checks.
     *
     * @param string $blocks_tokenize_value
     * @param array  $extra_meta
     * @return WC_Order
     */
    private function create_order_for_customer_reference_test( string $blocks_tokenize_value, array $extra_meta = array() ): WC_Order {
        $order = $this->getMockBuilder( WC_Order::class )
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'get_id',
                    'get_payment_method',
                    'get_meta',
                    'get_customer_id',
                    'get_billing_address_1',
                    'get_billing_address_2',
                    'get_billing_country',
                    'get_billing_state',
                    'get_billing_city',
                    'get_billing_postcode',
                    'get_billing_email',
                    'get_billing_phone',
                    'get_billing_first_name',
                    'get_billing_last_name',
                    'get_billing_company',
                    'get_customer_ip_address',
                    'get_customer_user_agent',
                )
            )
            ->getMock();

        $order->method( 'get_id' )->willReturn( 5 );
        $order->method( 'get_payment_method' )->willReturn( 'multisafepay_amex' );
        $order->method( 'get_customer_id' )->willReturn( 42 );

        $order->method( 'get_meta' )->willReturn(
            array_merge(
                array(
                    'multisafepay_amex_payment_component_tokenize' => $blocks_tokenize_value,
                ),
                $extra_meta
            )
        );

        $order->method( 'get_billing_address_1' )->willReturn( 'Kraanspoor' );
        $order->method( 'get_billing_address_2' )->willReturn( '39C' );
        $order->method( 'get_billing_country' )->willReturn( 'NL' );
        $order->method( 'get_billing_state' )->willReturn( '' );
        $order->method( 'get_billing_city' )->willReturn( 'Amsterdam' );
        $order->method( 'get_billing_postcode' )->willReturn( '1033 SC' );
        $order->method( 'get_billing_email' )->willReturn( 'john.doe@multisafepay.com' );
        $order->method( 'get_billing_phone' )->willReturn( '123456789' );
        $order->method( 'get_billing_first_name' )->willReturn( 'John' );
        $order->method( 'get_billing_last_name' )->willReturn( 'Doe' );
        $order->method( 'get_billing_company' )->willReturn( 'MultiSafepay' );
        $order->method( 'get_customer_ip_address' )->willReturn( '127.0.0.1' );
        $order->method( 'get_customer_user_agent' )->willReturn( 'Mozilla/5.0' );

        return $order;
    }

}
