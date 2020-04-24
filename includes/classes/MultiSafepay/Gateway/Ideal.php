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

class Ideal extends Core
{
    public function __construct()
    {
        parent::__construct();
        $this->has_fields = self::isDirect(self::getSettings());
    }

    public static function getCode()
    {
        return 'multisafepay_ideal';
    }

    public static function getName()
    {
        return __('iDEAL', 'multisafepay');
    }

    public static function getSettings()
    {
        return get_option('woocommerce_multisafepay_ideal_settings');
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
        return 'IDEAL';
    }

    /**
     * @return string
     */
    public function getType()
    {
        $settings = get_option('woocommerce_multisafepay_ideal_settings');

        if ($settings['direct'] == 'yes' && isset($_POST['ideal_issuer'])) {
            return 'direct';
        } else {
            return 'redirect';
        }
    }

    /**
     * @param $order_id
     * @return array|string
     */
    public function getGatewayInfo($order_id)
    {
        if (isset($_POST['ideal_issuer'])) {
            return (array('issuer_id' => sanitize_text_field($_POST['ideal_issuer'])));
        } else {
            return ('');
        }
    }

    public function init_form_fields($form_fields = array())
    {
        $this->form_fields = array();

        $warning = $this->getWarning();

        if (is_array($warning)) {
            $this->form_fields['warning'] = $warning;
        }

        $this->form_fields['direct'] = array(
            'title' => __('Enable', 'multisafepay'),
            'type' => 'checkbox',
            'label' => sprintf(__('Direct %s', 'multisafepay'), $this->getName()),
            'description' => __('Enable or disable the selection of the preferred bank within the website.', 'multisafepay'),
            'default' => 'yes'
        );
        parent::init_form_fields($this->form_fields);
    }

    public function payment_fields()
    {
        $description = '';
        $settings = (array) get_option('woocommerce_multisafepay_ideal_settings');
        if ($settings['direct'] == 'yes') {
            $description = '';

            $msp = new Client();
            $helper = new Helper();

            $msp->setApiKey($helper->getApiKey());
            $msp->setApiUrl($helper->getTestMode());

            try {
                $msg = null;
                $issuers = $msp->issuers->get();
            } catch (\Exception $e) {
                $msg = htmlspecialchars($e->getMessage());
                $helper->write_log($msg);
                wc_add_notice($msg, 'error');
            }

            $description .= __('Please select an issuer', 'multisafepay') . '<br/>';
            $description .= '<select id="ideal_issuer" name="ideal_issuer" class="required-entry">';
            $description .= '<option value="">' . __('Please choose...', 'multisafepay') . '</option>';
            foreach ($issuers as $issuer) {
                $description .= '<option value="' . $issuer->code . '">' . $issuer->description . '</option>';
            }
            $description .= '</select>';
            $description .= '</p>';
        }

        if (!empty($this->description)) {
            $description .= '<p>' . $this->description . '</p>';
        }

        echo $description;
    }

    /**
     * @return bool
     */
    public function validate_fields()
    {
        $settings = get_option('woocommerce_multisafepay_ideal_settings');

        if ($settings['direct'] == 'yes' && empty($_POST['ideal_issuer'])) {
            wc_add_notice(__('Error: ', 'multisafepay') . ' ' . __('Please select an issuer.', 'multisafepay'), 'error');
            return false;
        }
        return true;
    }

    public function process_payment($order_id)
    {
        $this->type = $this->getType();
        $this->GatewayInfo = $this->getGatewayInfo($order_id);

        return parent::process_payment($order_id);
    }
}
