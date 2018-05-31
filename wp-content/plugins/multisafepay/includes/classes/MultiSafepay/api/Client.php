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
        $this->orders = new ObjectOrders($this);
        $this->issuers = new ObjectIssuers($this);
        $this->gateways = new ObjectGateways($this);
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

    /*
     * Parses and splits up an address in street and housenumber
     */

    private function parseAddress($adress, $seperaatAddition = false)
    {
        $street = '';
        $number = '';
        $numberAddition = '';

        $results = array();
        $pattern_adress = "/^(.*)\s(\d+)(.*)/";

        preg_match($pattern_adress, trim($adress), $results);
        if (count($results) == 0) {
            $street = trim($adress);
        } else {
            $street = trim((isset($results[1])) ? $results[1] : '');
            $number = trim((isset($results[2])) ? $results[2] : '');
            $numberAddition = trim((isset($results[3])) ? $results[3] : '');
        }

        if ($seperaatAddition === true) {
            $pattern_addition = '/^([\s|-]*)(.*)/';
            $replacement_addition = '$2';
            $numberAddition = trim(preg_replace($pattern_addition, $replacement_addition, $numberAddition));
        } else {
            $number .= $numberAddition;
            $numberAddition = '';
        }

        return array($street, $number, $numberAddition);
        //  return array('street' => $street, 'number' => $number, 'numberAddition' => $numberAddition);
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

    public function processAPIRequest($http_method, $api_method, $http_body = null)
    {
        if (empty($this->api_key)) {
            throw new Exception(__('Please configure your MultiSafepay API Key.', 'multisafepay'));
        }

        $url = $this->api_url . $api_method;
        $ch = curl_init($url);

        $request_headers = array(
            "Accept: application/json",
            "api_key:" . $this->api_key,
        );

        if ($http_body !== null) {
            $request_headers[] = "Content-Type: application/json";
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $http_body);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

        $body = curl_exec($ch);

        if ($this->debug) {
            $this->request = $http_body;
            $this->response = $body;
        }

        if (curl_errno($ch)) {
            $str = __('Unable to communicatie with the MultiSafepay payment server', 'multisafepay') . '('
                    . curl_errno($ch) . '): ' . curl_error($ch) . '.';
            throw new Exception($str);
        }
        curl_close($ch);
        return $body;
    }

}
