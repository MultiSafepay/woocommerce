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

namespace MultiSafepay\WooCommerce\Services;


use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\AddressParser;
use MultiSafepay\ValueObject\Customer\Country;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use MultiSafepay\ValueObject\IpAddress;

/**
 * Class CustomerService
 * @package MultiSafepay\WooCommerce\Services
 */
class CustomerService
{

    /**
     * @param int $customer_id
     * @return CustomerDetails
     */
    public function create_customer_details(int $customer_id): CustomerDetails
    {
        $customer = get_user_by('id', $customer_id);

        $customer_first_name = get_user_meta($customer->get('id'), 'billing_first_name', true);
        $customer_last_name = get_user_meta($customer->get('id'), 'billing_last_name', true);
        $customer_email = get_user_meta($customer->get('id'), 'billing_email', true);
        $customer_country = get_user_meta($customer->get('id'), 'billing_country', true);
        $customer_city = get_user_meta($customer->get('id'), 'billing_city', true);
        $customer_state = get_user_meta($customer->get('id'), 'billing_state', true);
        $customer_zip_code = get_user_meta($customer->get('id'), 'billing_postcode', true);
        $customer_address_line_1 = get_user_meta($customer->get('id'), 'billing_address_1', true);
        $customer_address_line_2 = get_user_meta($customer->get('id'), 'billing_address_2', true);
        $customer_phone_number = get_user_meta($customer->get('id'), 'billing_phone', true);

        $customer_address = $this->create_address(
            $customer_address_line_1,
            $customer_address_line_2,
            $customer_country,
            $customer_state,
            $customer_city,
            $customer_zip_code);

        return $this->create_customer(
            $customer_address,
            $customer_email,
            $customer_phone_number,
            $customer_first_name,
            $customer_last_name,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
    }

    /**
     * @param Address $address
     * @param string $email_address
     * @param string $phone_number
     * @param string $first_name
     * @param string $last_name
     * @param string $ip_address
     * @param string $user_agent
     * @return CustomerDetails
     */
    protected function create_customer(
        Address $address,
        string $email_address,
        string $phone_number,
        string $first_name,
        string $last_name,
        string $ip_address,
        string $user_agent
    ): CustomerDetails
    {
        $customer_details = new CustomerDetails();
        return $customer_details
            ->addAddress($address)
            ->addEmailAddress(new EmailAddress($email_address))
            ->addFirstName($first_name)
            ->addLastName($last_name)
            ->addIpAddress(new IpAddress($ip_address))
            ->addUserAgent($user_agent)
            ->addPhoneNumber(new PhoneNumber($phone_number));
    }

    /**
     * @param string $address_line_1
     * @param string $address_line_2
     * @param string $country
     * @param string $state
     * @param string $city
     * @param string $zip_code
     * @return Address
     */
    protected function create_address(
        string $address_line_1,
        string $address_line_2,
        string $country,
        string $state,
        string $city,
        string $zip_code
    ): Address
    {
        $address_parser = new AddressParser();
        $address = $address_parser->parse($address_line_1, $address_line_2);

        $street = $address[0];
        $house_number = $address[1];

        $customer_address = new Address();
        return $customer_address
            ->addStreetName($street)
            ->addHouseNumber($house_number)
            ->addState($state)
            ->addCity($city)
            ->addCountry(new Country($country))
            ->addZipCode($zip_code);
    }
}