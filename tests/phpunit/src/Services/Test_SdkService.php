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

use MultiSafepay\WooCommerce\Services\SdkService;
use MultiSafepay\Api\Gateways\Gateway;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\GatewayManager;


class Test_SdkService extends WP_UnitTestCase {

    public function setUp() {
        parent::setUp();
        $this->api_key = getenv('MULTISAFEPAY_API_KEY');
        update_option( 'multisafepay_testmode', 1 );
        update_option( 'multisafepay_test_api_key', $this->api_key );
        update_option( 'multisafepay_api_key', $this->api_key );
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\SdkService::get_api_key
     */
    public function test_get_api_key() {
        $sdk = new SdkService();
        $output = $sdk->get_api_key();
        $this->assertEquals($this->api_key, $output);
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\SdkService::get_test_mode
     */
    public function test_get_test_mode_as_true() {
        update_option('multisafepay_testmode', true);
        $sdk = new SdkService();
        $output = $sdk->get_test_mode();
        $this->assertTrue($output);
    }

    /**
     * @covers \MultiSafepay\WooCommerce\Services\SdkService::get_test_mode
     * @depends test_get_test_mode_as_true
     */
    public function test_get_test_mode_as_false() {
        update_option( 'multisafepay_testmode', 0 );
        $sdk = new SdkService();
        $output = $sdk->get_test_mode();
        $this->assertFalse($output);
    }


    /**
     * @covers \MultiSafepay\WooCommerce\Services\SdkService::get_transaction_manager
     */
    public function test_get_transaction_manager() {
        $sdk = new SdkService('string');
        $output = $sdk->get_transaction_manager();
        $this->assertInstanceOf( TransactionManager::class, $output);
    }


    /**
     * @covers \MultiSafepay\WooCommerce\Services\SdkService::get_gateway_manager
     */
    public function test_get_gateway_manager() {
        $sdk = new SdkService('string');
        $output = $sdk->get_gateway_manager();
        $this->assertInstanceOf( GatewayManager::class, $output);
    }

}
