<?php declare(strict_types=1);

use MultiSafepay\ValueObject\Customer\Country;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use MultiSafepay\WooCommerce\Services\Qr\QrCustomerService;

class Test_QrCustomerService extends WP_UnitTestCase {

    public function test_customer_details_are_created_correctly() {
        $customer = [
            'billing' => [
                'address_1' => 'Kranspoor, 39',
                'address_2' => '',
                'country' => 'NL',
                'state' => 'Noord Holland',
                'city' => 'Amsterdam',
                'postcode' => '1033 SC',
                'email' => 'example@multisafepay.com',
                'phone' => '1234567890',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company' => 'Test Company'
            ]
        ];
        $service = new QrCustomerService();
        $customer_details = $service->create_customer_details_from_cart($customer);

        $this->assertEquals('Kranspoor,', $customer_details->getAddress()->getStreetName());
        $this->assertEquals('39', $customer_details->getAddress()->getHouseNumber());
        $this->assertEquals('', $customer_details->getAddress()->getStreetNameAdditional());
        $this->assertEquals( new Country('NL'), $customer_details->getAddress()->getCountry());
        $this->assertEquals('Noord Holland', $customer_details->getAddress()->getState());
        $this->assertEquals('Amsterdam', $customer_details->getAddress()->getCity());
        $this->assertEquals('1033 SC', $customer_details->getAddress()->getZipCode());
        $this->assertEquals( new EmailAddress('example@multisafepay.com'), $customer_details->getEmailAddress());
        $this->assertEquals( new PhoneNumber('1234567890'), $customer_details->getPhoneNumber());
        $this->assertEquals('John', $customer_details->getFirstName());
        $this->assertEquals('Doe', $customer_details->getLastName());
        $this->assertEquals('Test Company', $customer_details->getCompanyName());
    }

    public function test_customer_details_are_created_with_shipping_type() {
        $customer = [
            'billing' => [
                'address_1' => 'Kranspoor, 39',
                'address_2' => '',
                'country' => 'NL',
                'state' => 'Noord Holland',
                'city' => 'Amsterdam',
                'postcode' => '1033 SC',
                'email' => 'example@multisafepay.com',
                'phone' => '1234567890',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company' => 'Test Company'
            ],
            'shipping' => [
                'address_1' => 'Kranspoor, 39',
                'address_2' => 'Suite 5',
                'country' => 'NL',
                'state' => 'Noord Holland',
                'city' => 'Amsterdam',
                'postcode' => '1033 SC',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'company' => 'Shipping Company'
            ],
        ];
        $service = new QrCustomerService();
        $customer_details = $service->create_customer_details_from_cart($customer, 'shipping');

        $this->assertEquals('Kranspoor, 39 Suite', $customer_details->getAddress()->getStreetName());
        $this->assertEquals('5', $customer_details->getAddress()->getHouseNumber());
        $this->assertEquals( new Country('NL'), $customer_details->getAddress()->getCountry());
        $this->assertEquals('Noord Holland', $customer_details->getAddress()->getState());
        $this->assertEquals('Amsterdam', $customer_details->getAddress()->getCity());
        $this->assertEquals('1033 SC', $customer_details->getAddress()->getZipCode());
        $this->assertEquals( new EmailAddress('example@multisafepay.com'), $customer_details->getEmailAddress());
        $this->assertEquals( new PhoneNumber('1234567890'), $customer_details->getPhoneNumber());
        $this->assertEquals('Jane', $customer_details->getFirstName());
        $this->assertEquals('Smith', $customer_details->getLastName());
        $this->assertEquals('Shipping Company', $customer_details->getCompanyName());
    }
}
