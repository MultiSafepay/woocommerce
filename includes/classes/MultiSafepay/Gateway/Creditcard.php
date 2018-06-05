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
class MultiSafepay_Gateway_Creditcard extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_creditcard";
    }

    public static function getName()
    {
        return __('Creditcard', 'multisafepay');
    }

    public static function getSettings()
    {
        return get_option('woocommerce_multisafepay_creditcard_settings');
    }

    public static function getTitle()
    {
        $settings = self::getSettings();
        if (!isset ($settings['title']))
            $settings['title'] = '';

        return ($settings['title']);
    }

    public static function getGatewayCode()
    {
        return ( empty($_POST['cc_issuer']) ? "CREDITCARDS" : $_POST['cc_issuer']);
    }

    public function getType()
    {
        return "redirect";
    }

    public function payment_fields()
    {
        $description = '';

        $description_text = $this->get_option('description');
        if (!empty($description_text))
            $description .= '<p>' . $description_text . '</p>';

        $msp = new Client();

        $msp->setApiKey($this->getApiKey());
        $msp->setApiUrl($this->getTestMode());

        try {
            $msg = null;
            $gateways = $msp->gateways->get();
        } catch (Exception $e) {
            $msg = htmlspecialchars($e->getMessage());
            $this->write_log($msg);
            wc_add_notice($msg, 'error');
        }

        $description .= __('Select CreditCard', 'multisafepay') . '<br/>';
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

    public function validate_fields()
    {
        if (empty($_POST['cc_issuer'])) {
            wc_add_notice(__('Error: ', 'multisafepay') . ' ' . __('Please select a CreditCard.', 'multisafepay'));
            return false;
        }
        return true;
    }

}
