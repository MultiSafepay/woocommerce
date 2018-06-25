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
class MultiSafepay_Gateway_Payafter extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_payafter";
    }

    public static function getName()
    {
        return __('Pay After Delivery', 'multisafepay');
    }

    public static function getSettings()
    {
        return get_option('woocommerce_multisafepay_payafter_settings');
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
        return "PAYAFTER";
    }

    public function getType()
    {
        $settings = get_option('woocommerce_multisafepay_payafter_settings');

        if ($settings['direct'] == 'yes')
            return "direct";
        else
            return "redirect";
    }

    public function init_settings($form_fields = array())
    {
        $this->form_fields = array();

        $warning = $this->getWarning();

        if (is_array($warning))
            $this->form_fields['warning'] = $warning;

        $this->form_fields['direct'] = array('title' => __('Enable', 'multisafepay'),
            'type' => 'checkbox',
            'label' => sprintf(__('Direct %s', 'multisafepay'), $this->getName()),
            'description' => __('If enable extra credentials can be filled in checkout form, otherwise an extra form will be used.', 'multisafepay'),
            'default' => 'yes');

        $this->form_fields['minamount'] = array('title' => __('Minimal order amount', 'multisafepay'),
            'type' => 'text',
            'description' => __('The minimal order amount in euro\'s  for an order to use this payment method', 'multisafepay'),
            'css' => 'width: 100px;');

        $this->form_fields['maxamount'] = array('title' => __('Maximal order amount', 'multisafepay'),
            'type' => 'text',
            'description' => __('The maximal order amount in euro\'s  for an order to use this payment method', 'multisafepay'),
            'css' => 'width: 100px;');

        parent::init_settings($this->form_fields);
    }

    public function payment_fields()
    {
        $description = '';
        $settings = (array) get_option('woocommerce_multisafepay_payafter_settings');

        if ($settings['direct'] == 'yes') {

            $description = '<p class="form-row form-row-wide  validate-required">
                                <label for="msp_birthday" class="">' . __('Birthday', 'multisafepay') .
                    '<abbr class="required" title="required">*</abbr>
                                </label>
                                <input type="text" class="input-text" name="pad_birthday" id="pad_birthday" placeholder="dd-mm-yyyy"/>
                            </p>

                            <p class="form-row form-row-wide  validate-required">
                                <label for="msp_account" class="">' . __('Bankaccount', 'multisafepay') .
                    '<abbr class="required" title="required">*</abbr>
                                </label>
                                <input type="text" class="input-text" name="pad_account" id="pad_account" placeholder=""/>
                            </p>';

            $description .= '<p class="form-row form-row-wide">' . __('By confirming this order you agree with the ', 'multisafepay') . '<br><a href="https://www.multifactor.nl/voorwaarden/betalingsvoorwaarden-consument/" target="_blank">' . __('Terms and conditions of MultiFactor', 'multisafepay') . '</a>';
        }

        $description_text = $this->get_option('description');
        if (!empty($description_text))
            $description .= '<p>' . $description_text . '</p>';

        echo $description;
    }

    public function validate_fields()
    {
        return true;
    }

    public static function payafter_filter_gateways($gateways)
    {

        global $woocommerce;

        $settings = (array) get_option("woocommerce_multisafepay_payafter_settings");

        if (!empty($settings['minamount']) && $woocommerce->cart->total < $settings['minamount'])
            unset($gateways['multisafepay_payafter']);

        if (!empty($settings['maxamount']) && $woocommerce->cart->total > $settings['maxamount'])
            unset($gateways['multisafepay_payafter']);

        // Compatiblity Woocommerce 2.x and 3.x
        if (method_exists($woocommerce->customer,'get_billing_country')){
            $billingCountry = $woocommerce->customer->get_billing_country();
        }elseif (method_exists($woocommerce->customer,'get_country')){
            $billingCountry = $woocommerce->customer->get_country();
        }

        if (isset ($woocommerce->customer) && $billingCountry != 'NL')
            unset($gateways['multisafepay_payafter']);

        return $gateways;
    }

    public function process_payment($order_id)
    {
        $this->type = $this->getType();
        $this->GatewayInfo = $this->getGatewayInfo($order_id);

        return parent::process_payment($order_id);
    }

}
