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
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) 2019 MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

class MultiSafepay_Test_Gateway_ApplePay extends WC_Unit_Test_Case
{
    public function testGetGatewayCode()
    {
        $this->assertEquals('APPLEPAY', MultiSafepay_Gateway_Applepay::getGatewayCode());
    }

    public function testGetCode()
    {
        $this->assertEquals('multisafepay_applepay', MultiSafepay_Gateway_Applepay::getCode());
    }

    public function testGetSettings()
    {
        if (!get_option('woocommerce_multisafepay_applepay_settings')) {
            add_option('woocommerce_multisafepay_applepay_settings');
        }

        $settingsData = [
            'enabled' => 'yes',
            'title' => 'ApplePay',
            'description' => 'Pay by ApplePay',
            'gateway' => 'APPLEPAY',
            'instructions' => 'This is how to pay'
        ];

        update_option('woocommerce_multisafepay_applepay_settings', $settingsData);

        $settings = MultiSafepay_Gateway_Applepay::getSettings();

        $this->assertEquals($settingsData['enabled'], $settings['enabled']);
        $this->assertEquals($settingsData['title'], $settings['title']);
        $this->assertEquals($settingsData['description'], $settings['description']);
        $this->assertEquals($settingsData['gateway'], $settings['gateway']);
        $this->assertEquals($settingsData['instructions'], $settings['instructions']);
    }
}
