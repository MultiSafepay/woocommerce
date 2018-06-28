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
class MultiSafepay_Gateway_Santander extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_santander";
    }

    public static function getName()
    {
        return __('Santander Betaalplan', 'multisafepay');
    }

    public static function getSettings()
    {
        return get_option('woocommerce_multisafepay_santander_settings');
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
        return "SANTANDER";
    }

    public function getType()
    {
        return "redirect";
    }

    public function init_settings($form_fields = array())
    {
        $this->form_fields = array();

        $warning = $this->getWarning();

        if (is_array($warning))
            $this->form_fields['warning'] = $warning;

        $this->form_fields['minamount'] = array('title' => __('Minimal order amount', 'multisafepay'),
            'type' => 'text',
            'description' => __('The minimal order amount in euro\'s  for an order to use this payment method', 'multisafepay'),
            'default' => '250',
            'css' => 'width: 100px;');

        $this->form_fields['maxamount'] = array('title' => __('Maximal order amount', 'multisafepay'),
            'type' => 'text',
            'description' => __('The maximal order amount in euro\'s  for an order to use this payment method', 'multisafepay'),
            'default' => '1000',
            'css' => 'width: 100px;');

        parent::init_settings($this->form_fields);
    }


    public static function santander_filter_gateways($gateways)
    {

        global $woocommerce;

        $settings = (array) get_option("woocommerce_multisafepay_santander_settings");

        if (!empty($settings['minamount']) && $woocommerce->cart->total < $settings['minamount'])
            unset($gateways['multisafepay_santander']);

        if (!empty($settings['maxamount']) && $woocommerce->cart->total > $settings['maxamount'])
            unset($gateways['multisafepay_santander']);



        return $gateways;
    }
}

?>
