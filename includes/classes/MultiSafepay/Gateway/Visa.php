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

class Visa extends Core
{
    public function __construct()
    {
        parent::__construct();
        $this->max_amount = $this->getMaxAmount();
    }

    public static function getCode()
    {
        return 'multisafepay_visa';
    }

    public static function getName()
    {
        return __('Visa', 'multisafepay');
    }

    public static function getSettings()
    {
        return get_option('woocommerce_multisafepay_visa_settings');
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
        return 'VISA';
    }

    public function getType()
    {
        return 'redirect';
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
