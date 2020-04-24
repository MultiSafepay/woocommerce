<?php declare(strict_types=1);
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

class Applepay extends Core
{
    public function __construct()
    {
        parent::__construct();
        if ($this->enabled === 'yes') {
            add_action('woocommerce_before_checkout_form', array($this, 'checkApplePay'));
        }
    }


    /**
     * @return string|void
     */
    public static function getCode()
    {
        return 'multisafepay_applepay';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return __('Apple Pay', 'multisafepay');
    }

    /**
     * @return mixed
     */
    public static function getSettings()
    {
        return get_option('woocommerce_multisafepay_applepay_settings');
    }

    /**
     * @return string
     */
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
        return 'APPLEPAY';
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'redirect';
    }

    /**
     * Show Apple Pay if we are on an Apple device
     */
    public function checkApplePay()
    {
        wp_enqueue_script('msp_applepay', MULTISAFEPAY_PLUGIN_FILE . '/js/applepay.js', [], $this->getVersion(), false);
    }
}
