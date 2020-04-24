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

class Klarna extends Core
{

    public static function getCode()
    {
        return 'multisafepay_klarna';
    }

    public static function getName()
    {
        return __('Klarna', 'multisafepay');
    }

    public static function getSettings()
    {
        return get_option('woocommerce_multisafepay_klarna_settings');
    }

    public static function getTitle()
    {
        $settings = self::getSettings();
        if (!isset($settings['title'])) {
            $settings['title'] = '';
        }

        return ($settings['title']);
    }

    public static function getGatewayCode()
    {
        return 'KLARNA';
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'redirect';
    }

    /**
     * @param array $form_fields
     */
    public function init_form_fields($form_fields = array())
    {
        $this->form_fields = array();

        $warning = $this->getWarning();

        if (is_array($warning)) {
            $this->form_fields['warning'] = $warning;
        }

        $this->form_fields['minamount'] = array('title' => __('Minimum order amount', 'multisafepay'),
            'type' => 'text',
            'description' => __('The minimum order amount in euro\'s for an order to use this payment method', 'multisafepay'),
            'css' => 'width: 100px;');

        $this->form_fields['maxamount'] = array('title' => __('Maximum order amount', 'multisafepay'),
            'type' => 'text',
            'description' => __('The maximum order amount in euro\'s for an order to use this payment method', 'multisafepay'),
            'css' => 'width: 100px;');

        parent::init_form_fields($this->form_fields);
    }

    public static function klarna_filter_gateways($gateways)
    {

        global $woocommerce;

        $settings = (array) get_option('woocommerce_multisafepay_klarna_settings');

        if (!empty($settings['minamount']) && $woocommerce->cart->total < $settings['minamount']) {
            unset($gateways['multisafepay_klarna']);
        }

        if (!empty($settings['maxamount']) && $woocommerce->cart->total > $settings['maxamount']) {
            unset($gateways['multisafepay_klarna']);
        }



        return $gateways;
    }
}
