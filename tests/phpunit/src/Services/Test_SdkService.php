<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\GatewayManager;


class Test_SdkService extends WP_UnitTestCase {

    /**
     * @var array|false|string
     */
    private $api_key;

    public function set_up() {
        parent::set_up();
        $this->api_key = getenv('MULTISAFEPAY_API_KEY');
        update_option( 'multisafepay_testmode', 1 );
        update_option( 'multisafepay_test_api_key', $this->api_key );
        update_option( 'multisafepay_api_key', $this->api_key );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\SdkService::get_api_key
     */
    public function test_get_api_key() {
        $sdk = new SdkService();
        $output = $sdk->get_api_key();
        $this->assertEquals($this->api_key, $output);
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\SdkService::get_test_mode
     */
    public function test_get_test_mode_as_true() {
        update_option('multisafepay_testmode', true);
        $sdk = new SdkService();
        $output = $sdk->get_test_mode();
        $this->assertTrue($output);
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\SdkService::get_test_mode
     * @depends test_get_test_mode_as_true
     */
    public function test_get_test_mode_as_false() {
        update_option( 'multisafepay_testmode', 0 );
        $sdk = new SdkService();
        $output = $sdk->get_test_mode();
        $this->assertFalse($output);
    }


    /**
     * @covers \MultiSafepay\WooCommerce\Services\SdkService::get_transaction_manager
     */
    public function test_get_transaction_manager() {
        $sdk = new SdkService('string');
        $output = $sdk->get_transaction_manager();
        $this->assertInstanceOf( TransactionManager::class, $output);
    }


    /**
     * @covers \MultiSafepay\WooCommerce\Services\SdkService::get_gateway_manager
     */
    public function test_get_gateway_manager() {
        $sdk = new SdkService('string');
        $output = $sdk->get_gateway_manager();
        $this->assertInstanceOf( GatewayManager::class, $output);
    }

}
