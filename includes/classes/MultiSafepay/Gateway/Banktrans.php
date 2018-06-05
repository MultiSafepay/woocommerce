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
class MultiSafepay_Gateway_Banktrans extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_banktrans";
    }

    public static function getName()
    {
        return __('Banktransfer', 'multisafepay');
    }

    public static function getSettings()
    {
        return get_option('woocommerce_multisafepay_banktrans_settings');
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
        return "BANKTRANS";
    }

    public function getType()
    {
        $settings = get_option('woocommerce_multisafepay_banktrans_settings');

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

        $this->form_fields['direct'] = array(
            'title' => __('Direct', 'woocommerce'),
            'type' => 'checkbox',
            'label' => sprintf(__('Direct %s', 'multisafepay'), $this->getName()),
            'default' => 'no');

        $this->form_fields['direct'] = array('title' => __('Enable', 'multisafepay'),
            'type' => 'checkbox',
            'label' => sprintf(__('Direct %s', 'multisafepay'), $this->getName()),
            'description' => __('If enabled, the consumer receives an e-mail with payment details, no extra credentals are needed during checkout.', 'multisafepay'),
            'default' => 'no');
        parent::init_settings($this->form_fields);
    }

}
