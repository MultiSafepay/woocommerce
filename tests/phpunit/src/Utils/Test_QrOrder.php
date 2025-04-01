<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Utils\QrOrder;

class Test_QrOrder extends WP_UnitTestCase {

    private $original_server;

    public function setUp(): void {
        $this->original_server = $_SERVER;
    }

    public function tearDown(): void {
        $_SERVER = $this->original_server;
        parent::tearDown();
    }

    public function test_user_agent_is_returned_correctly() {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $service = new QrOrder();
        $this->assertEquals('Mozilla/5.0', $service->get_user_agent());
    }

    public function test_user_agent_is_empty_when_not_set() {
        unset($_SERVER['HTTP_USER_AGENT']);
        $service = new QrOrder();
        $this->assertEquals('', $service->get_user_agent());
    }

    public function test_user_agent_is_sanitized() {
        $_SERVER['HTTP_USER_AGENT'] = '<script>alert("XSS")</script>';
        $service = new QrOrder();
        $this->assertEquals('', $service->get_user_agent());
    }

    public function test_customer_ip_is_returned_from_http_client_ip() {
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.1.1';
        $service = new QrOrder();
        $this->assertEquals('192.168.1.1', $service->get_customer_ip_address());
    }

    public function test_customer_ip_is_returned_from_http_x_forwarded_for() {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.2';
        $service = new QrOrder();
        $this->assertEquals('192.168.1.2', $service->get_customer_ip_address());
    }

    public function test_customer_ip_is_returned_from_remote_addr() {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.3';
        $service = new QrOrder();
        $this->assertEquals('192.168.1.3', $service->get_customer_ip_address());
    }

    public function test_customer_ip_is_empty_when_not_set() {
        unset($_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR']);
        $service = new QrOrder();
        $this->assertEquals('', $service->get_customer_ip_address());
    }

    public function test_customer_ip_is_sanitized() {
        $_SERVER['REMOTE_ADDR'] = '<script>alert("XSS")</script>';
        $service = new QrOrder();
        $this->assertEquals('', $service->get_customer_ip_address());
    }
}
