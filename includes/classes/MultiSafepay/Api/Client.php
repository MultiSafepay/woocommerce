<?php

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
 * @author      TechSupport <techsupport@multisafepay.com>
 * @copyright   Copyright (c) 2017 MultiSafepay, Inc. (http://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MultiSafepay\WooCommerce\Api;

use MultiSafepay\WooCommerce\Api\Client\Gateways;
use MultiSafepay\WooCommerce\Api\Client\Issuers;
use MultiSafepay\WooCommerce\Api\Client\Orders;

class Client
{

    public $orders;
    public $issuers;
    public $transactions;
    public $gateways;
    protected $api_key;
    public $api_url;
    public $api_endpoint;
    public $request;
    public $response;
    public $debug;

    public function __construct()
    {
        $this->orders = new Orders($this);
        $this->issuers = new Issuers($this);
        $this->gateways = new Gateways($this);
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setApiUrl($test)
    {
        if ($test) {
            $url = 'https://testapi.multisafepay.com/v1/json/';
        } else {
            $url = 'https://api.multisafepay.com/v1/json/';
        }
        $this->api_url = trim($url);
    }

    public function setDebug($debug)
    {
        $this->debug = trim($debug);
    }

    public function setApiKey($api_key)
    {
        $this->api_key = trim($api_key);
    }

    /*
     * Parses and sets customer address
     */

    public function parseCustomerAddress($street_address)
    {
        list($address, $apartment) = $this->parseAddress($street_address);
        return array($address, $apartment);
    }

    /**
     * Parses and sets delivery address
     */
    public function parseDeliveryAddress($street_address)
    {
        list($address, $apartment) = $this->parseAddress($street_address);
        $this->delivery['address1'] = $address;
        $this->delivery['housenumber'] = $apartment;
    }

    /**
     * Parses and splits up an address in street and housenumber
     */
    public function parseAddress($address)
    {
        // Trim the addres
        $address = trim($address);
        $address = preg_replace('/[[:blank:]]+/', ' ', $address);

        // Make array of all regex matches
        $matches = array();

        /**
         * Regex part one: Add all before number.
         * If number contains whitespace, Add it also to street.
         * All after that will be added to apartment
         */
        $pattern = '/(.+?)\s?([\d]+[\S]*)(\s?[A-z]*?)$/';
        preg_match($pattern, $address, $matches);

        // Save the street and apartment and trim the result
        $street = isset($matches[1]) ? $matches[1] : '';
        $apartment = isset($matches[2]) ? $matches[2] : '';
        $extension = isset($matches[3]) ? $matches[3] : '';
        $street = trim($street);
        $apartment = trim($apartment . $extension);

        return array($street, $apartment);
    }

    private function rstrpos($haystack, $needle, $offset = null)
    {
        $size = strlen($haystack);

        if (is_null($offset)) {
            $offset = $size;
        }

        $pos = strpos(strrev($haystack), strrev($needle), $size - $offset);

        if ($pos === false) {
            return false;
        }

        return $size - $pos - strlen($needle);
    }


    /**
     * @param $http_method
     * @param $endpoint
     * @param $http_body
     *
     * @return mixed
     * @throws \Exception Error on request.
     */
    public function processAPIRequest($http_method, $endpoint, $http_body = null)
    {
        if (empty($this->api_key)) {
            throw new \Exception(__('Please configure your MultiSafepay API key.', 'multisafepay'));
        }

        $args = [
            'headers' => [
                'Accept'   => 'application/json',
                'api_key'  => $this->api_key
            ],
            'method'  => $http_method,
            'body'    => $http_body,
            'sslverify' => true,
            'timeout'   => 120,
        ];

        if ($http_body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
        }

        $url = $this->api_url . $endpoint;
        $response = wp_remote_request($url, $args);

        if ($this->debug) {
            $this->request = $http_body;
            $this->response = $response;
        }

        if (is_wp_error($response)) {
            $str = __('Unable to communicate with the MultiSafepay payment server', 'multisafepay') .
                   $response->get_error_message();
            throw new \Exception($str);
        }
        $result = wp_remote_retrieve_body($response);

        return $result;
    }
}
