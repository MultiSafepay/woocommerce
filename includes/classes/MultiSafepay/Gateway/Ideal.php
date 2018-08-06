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
class MultiSafepay_Gateway_Ideal extends MultiSafepay_Gateway_Abstract
{

    public static function getCode()
    {
        return "multisafepay_ideal";
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
        if (!isset ($settings['title']))
            $settings['title'] = '';

        return ($settings['title']);
    }

    public static function getGatewayCode()
    {
        return "IDEAL";
    }

    public function getType()
    {
        $settings = get_option('woocommerce_multisafepay_ideal_settings');

        if ($settings['direct'] == 'yes' && isset($_POST['ideal_issuer']))
            return "direct";
        else
            return "redirect";
    }

    public function getGatewayInfo($order_id)
    {
        if (isset($_POST['ideal_issuer']))
            return (array("issuer_id" => $_POST['ideal_issuer']));
        else
            return ('');
    }

    public function init_settings($form_fields = array())
    {
        $this->form_fields = array();

        $warning = $this->getWarning();

        if (is_array($warning))
            $this->form_fields['warning'] = $warning;

        $this->form_fields['direct'] = array('title' => __('Enable', 'multisafepay'),
            'type'          => 'checkbox',
            'label'         => sprintf(__('Direct %s', 'multisafepay'), $this->getName()),
            'description'   => __('Enable of disable the selection of the preferred bank within the website.', 'multisafepay'),
            'default'       => 'yes');
        parent::init_settings($this->form_fields);
    }

    public function payment_fields()
    {
        $description = '';
        $settings = (array) get_option('woocommerce_multisafepay_ideal_settings');
        if ($settings['direct'] == 'yes') {

            $description = '';

            $msp = new MultiSafepay_Client();

            $msp->setApiKey($this->getApiKey());
            $msp->setApiUrl($this->getTestMode());

            try {
                $msg = null;
                $issuers = $msp->issuers->get();
            } catch (Exception $e) {

                $msg = htmlspecialchars($e->getMessage());
                $this->write_log($msg);
                wc_add_notice($msg, 'error');
            }

            $description .= __('Choose your bank', 'multisafepay') . '<br/>';
            $description .= '<select id="ideal_issuer" name="ideal_issuer" class="required-entry">';
            $description .= '<option value="">' . __('Please choose...', 'multisafepay') . '</option>';
            foreach ($issuers as $issuer) {
                $description .= '<option value="' . $issuer->code . '">' . $issuer->description . '</option>';
            }
            $description .= '</select>';
            $description .= '</p>';
        }

        $description_text = $this->get_option('description');
        if (!empty($description_text))
            $description .= '<p>' . $description_text . '</p>';

        echo $description;
    }

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
