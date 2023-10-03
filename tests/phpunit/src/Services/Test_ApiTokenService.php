<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Services\ApiTokenService;

class Test_ApiTokenService extends WP_UnitTestCase {

    /**
     * @var ApiTokenService
     */
    public $api_token_service;


    public function set_up() {
        $this->api_token_service = New ApiTokenService();

        $api_token_manager = $this->getMockBuilder('ApiTokenManager')
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $api_token = $this->getMockBuilder('ApiToken')
            ->disableOriginalConstructor()
            ->setMethods(['getApiToken'])
            ->getMock();

        $api_token->method('getApiToken')->willReturn('fake-api-token-from-api');
        $api_token_manager->method('get')->willReturn($api_token);

        $this->api_token_service->api_token_manager = $api_token_manager;
    }

    public function test_api_token_service_with_api_request() {
        $this->assertEquals( 'fake-api-token-from-api', $this->api_token_service->get_api_token() );
    }

    public function test_api_token_service_with_transient() {
        set_transient( 'multisafepay_api_token', 'transient-api-token', 30 );
        $this->assertEquals( 'transient-api-token', $this->api_token_service->get_api_token() );
    }
}
