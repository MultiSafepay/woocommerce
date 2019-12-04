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

class Multisafepay_Test_Gateway_Abstract extends WC_Unit_Test_Case
{
    public function setUp()
    {
        if (!get_option('woocommerce_multisafepay_ideal_settings')) {
            add_option('woocommerce_multisafepay_ideal_settings');
        }
    }

    public function updateIdealDirectSettings($type)
    {
        update_option('woocommerce_multisafepay_ideal_settings', ['direct' => $type]);

        return get_option('woocommerce_multisafepay_ideal_settings');
    }

    public function testIsDirectYes()
    {
        $settings = $this->updateIdealDirectSettings('yes');
        $this->assertTrue(MultiSafepay_Gateway_Abstract::isDirect($settings));
    }

    public function testIsDirectNo()
    {
        $settings = $this->updateIdealDirectSettings('no');
        $this->assertFalse(MultiSafepay_Gateway_Abstract::isDirect($settings));
    }
}
