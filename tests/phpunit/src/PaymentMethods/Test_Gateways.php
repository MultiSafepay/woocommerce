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
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

use MultiSafepay\WooCommerce\PaymentMethods\Gateways;
use WC_Payment_Gateway;

class Test_Gateways extends WP_UnitTestCase {

    public function setUp() {
        parent::setUp();
    }

    public function test_get_gateways_ids_returns_an_array() {
        $gateways_ids = Gateways::get_gateways_ids();
        $this->assertIsArray($gateways_ids);
    }

    public function test_get_payment_method_id_is_equal_to_payment_method_key() {
        foreach (Gateways::GATEWAYS as $key => $gateway) {
            $this->assertSame($key, (new $gateway)->get_payment_method_id());
        }
    }

    public function test_get_payment_method_object_by_gateway_code() {
        $gateway = Gateways::get_payment_method_object_by_gateway_code('VISA');
        $this->assertInstanceOf( WC_Payment_Gateway::class, $gateway);
    }

    public function test_get_payment_method_object_by_gateway_code_that_does_not_exist() {
        $gateway = Gateways::get_payment_method_object_by_gateway_code('CODE-NOT-EXIST');
        $this->assertFalse($gateway);
    }

    public function test_get_payment_method_object_by_payment_method_id() {
        $gateways_ids = Gateways::get_gateways_ids();
        $payment_method_index = array_rand($gateways_ids);
        $gateway = Gateways::get_payment_method_object_by_payment_method_id( $gateways_ids[$payment_method_index] );
        $this->assertInstanceOf( WC_Payment_Gateway::class, $gateway);
    }

    public function test_get_payment_method_object_by_payment_method_id_that_does_not_exist() {
        $gateway = Gateways::get_payment_method_object_by_payment_method_id('PAYMENT-METHOD-ID-NOT-EXIST');
        $this->assertFalse($gateway);
    }
}
