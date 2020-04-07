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

namespace MultiSafepay\WooCommerce\Gateway;

use MultiSafepay\WooCommerce\Api\Client;
use MultiSafepay\WooCommerce\Helper\Helper;

class Creditcard extends Core
{
    public function __construct()
    {
        parent::__construct();
        $this->has_fields = true;
        $this->max_amount = $this->getMaxAmount();
    }

    public static function getCode()
    {
        return 'multisafepay_creditcard';
    }

    public static function getName()
    {
        return __('Credit card', 'multisafepay');
    }

    public static function getSettings()
    {
        return get_option('woocommerce_multisafepay_creditcard_settings');
    }

    public static function getTitle()
    {
        $settings = self::getSettings();
        if (!isset($settings['title'])) {
            $settings['title'] = '';
        }

        return ($settings['title']);
    }

    /**
     * @return string
     */
    public static function getGatewayCode()
    {
        return ( empty($_POST['cc_issuer']) ? 'CREDITCARDS' : sanitize_text_field($_POST['cc_issuer']));
    }

    public function getType()
    {
        return 'redirect';
    }

    public function payment_fields()
    {
        $description = '';

        if (!empty($this->description)) {
            $description .= '<p>' . $this->description . '</p>';
        }

        $msp = new Client();
        $helper = new Helper();

        $msp->setApiKey($helper->getApiKey());
        $msp->setApiUrl($helper->getTestMode());

        try {
            $msg = null;
            $gateways = $msp->gateways->get();
        } catch (\Exception $e) {
            $msg = htmlspecialchars($e->getMessage());
            $helper->write_log($msg);
            wc_add_notice($msg, 'error');
        }

        $description .= __('Select a credit card', 'multisafepay') . '<br/>';
        $description .= '<select id="cc_issuer" name="cc_issuer" class="required-entry">';

        foreach ($gateways as $gateway) {
            switch ($gateway->id) {
                case 'VISA':
                case 'AMEX':
                case 'MAESTRO':
                case 'MASTERCARD':
                    $description .= '<option value="' . $gateway->id . '">' . $gateway->description . '</option>';
            }
        }

        $description .= '</select>';
        $description .= '</p>';

        echo $description;
    }

    /**
     * @return bool
     */
    public function validate_fields()
    {
        if (empty($_POST['cc_issuer'])) {
            wc_add_notice(__('Error: ', 'multisafepay') . ' ' . __('Please select a credit card', 'multisafepay'));
            return false;
        }
        return true;
    }

    /**
     * Setup the settings field for the payment methods.
     *
     * @param array $form_fields
     */
    public function init_form_fields($form_fields = [])
    {
        $this->form_fields = [
            'max_amount' => [
                'title' => __('Maximum order amount', 'multisafepay'),
                'type' => 'price',
                'description'=> __('The maximum order amount in euro\'s for an order to use this payment method', 'multisafepay'),
                'css' => 'width: 100px;',
                'desc_tip' => true,
                'placeholder' => wc_format_localized_price('0.00'),
            ],
        ];
        parent::init_form_fields($this->form_fields);
    }
}
