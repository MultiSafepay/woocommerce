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

class Multisafepay_Test_Gateway_Vvvcadeaukaart extends WC_Unit_Test_Case
{
    public function testGetGatewayCode()
    {
        $this->assertEquals('VVVGIFTCRD', MultiSafepay_Gateway_Vvvcadeaukaart::getGatewayCode());
    }

    public function testGetName()
    {
        $this->assertEquals('VVV Cadeaukaart', MultiSafepay_Gateway_Vvvcadeaukaart::getName());
    }

    public function testGetCode()
    {
        $this->assertEquals('multisafepay_vvvcadeaukaart', MultiSafepay_Gateway_Vvvcadeaukaart::getCode());
    }

    public function testGetSettings()
    {
        if (!get_option('woocommerce_multisafepay_vvvcadeaukaart_settings')) {
            add_option('woocommerce_multisafepay_vvvcadeaukaart_settings');
        }

        $settingsData = [
            'enabled' => 'yes',
            'title' => 'VVV Cadeaukaart',
            'description' => 'Betaal met VVV Cadeaukaart',
            'gateway' => 'VVVGIFTCRD',
            'instructions' => ''
        ];

        update_option('woocommerce_multisafepay_vvvcadeaukaart_settings', $settingsData);

        $this->assertArrayHasKey('enabled', Multisafepay_Gateway_Vvvcadeaukaart::getSettings());
        $this->assertArrayHasKey('title', Multisafepay_Gateway_Vvvcadeaukaart::getSettings());
        $this->assertArrayHasKey('description', Multisafepay_Gateway_Vvvcadeaukaart::getSettings());
        $this->assertArrayHasKey('gateway', Multisafepay_Gateway_Vvvcadeaukaart::getSettings());
        $this->assertArrayHasKey('instructions', Multisafepay_Gateway_Vvvcadeaukaart::getSettings());
    }
}
