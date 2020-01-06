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

class MultiSafepay_Test_Gateway_Ohmygood extends WC_Unit_Test_Case
{
    public function testGetGatewayCode()
    {
        $this->assertEquals('OHMYGOOD', MultiSafepay_Gateway_Ohmygood::getGatewayCode());
    }

    public function testGetName()
    {
        $this->assertEquals('Ohmygood', MultiSafepay_Gateway_Ohmygood::getName());
    }

    public function testGetCode()
    {
        $this->assertEquals('multisafepay_ohmygood', MultiSafepay_Gateway_Ohmygood::getCode());
    }

    public function testGetSettings()
    {
        if (!get_option('woocommerce_multisafepay_ohmygood_settings')) {
            add_option('woocommerce_multisafepay_ohmygood_settings');
        }

        $settingsData = [
            'enabled' => 'yes',
            'title' => 'Ohmygood',
            'description' => 'Betaal met Ohmygood',
            'gateway' => 'OHMYGOOD',
            'instructions' => ''
        ];

        update_option('woocommerce_multisafepay_ohmygood_settings', $settingsData);

        $this->assertArrayHasKey('enabled', MultiSafepay_Gateway_Ohmygood::getSettings());
        $this->assertArrayHasKey('title', MultiSafepay_Gateway_Ohmygood::getSettings());
        $this->assertArrayHasKey('description', MultiSafepay_Gateway_Ohmygood::getSettings());
        $this->assertArrayHasKey('gateway', MultiSafepay_Gateway_Ohmygood::getSettings());
        $this->assertArrayHasKey('instructions', MultiSafepay_Gateway_Ohmygood::getSettings());
    }
}
