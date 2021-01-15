<?php declare(strict_types=1);

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

use MultiSafepay\WooCommerce\Services\CustomerService;

class Test_CustomerService extends WP_UnitTestCase {

    public function setUp() {
        parent::setUp();
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
        $this->assertEquals( '127.0.0.1', $output['ip_address'] );
        $this->assertEquals( 'en_US', $output['locale'] );
        $this->assertEquals( '', $output['referrer'] );
        $this->assertEquals( '', $output['forwarded_ip'] );
        $this->assertEquals( 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.121 Safari/537.36', $output['user_agent'] );
    }

}
