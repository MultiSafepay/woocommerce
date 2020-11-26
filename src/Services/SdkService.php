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

namespace MultiSafepay\WooCommerce\Services;


use MultiSafepay\Api\GatewayManager;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidApiKeyException;
use MultiSafepay\Sdk;
use WP_Error;

/**
 * This class returns the SDK object.
 *
 * @since      4.0.0
 */
class SdkService {

    /**
     * @var     string      The API Key
     */
    private $api_key;

    /**
     * @var   boolean       The environment.
     */
    private $test_mode;

    /**
     * @var   Sdk       Sdk.
     */
    private $sdk = null;


    /**
     * SdkService constructor.
     *
     * @param  string   $api_key
     * @param  boolean  $test_mode
     */
    public function __construct( string $api_key, bool $test_mode ) {
        $this->api_key   = $api_key;
        $this->test_mode = ($test_mode) ? false : true;
        try {
            $this->sdk = new Sdk( $this->api_key, $this->test_mode );
        }
        catch ( InvalidApiKeyException $invalidApiKeyException ) {
            if( get_option( 'multisafepay_debugmode', false ) ) {
                $logger = wc_get_logger();
                $logger->log( 'warning', $invalidApiKeyException->getMessage() );
            }
        }
    }


    /**
     * Returns gateway manager
     *
     * @return  GatewayManager
     */
    public function get_gateway_manager(): GatewayManager {
        try {
            $gateway_manager = $this->sdk->getGatewayManager();
            return $gateway_manager;
        }
        catch ( ApiException $apiException ) {
            if( get_option( 'multisafepay_debugmode', false ) ) {
                $logger = wc_get_logger();
                $logger->log( 'warning', $apiException->getMessage() );
            }
            return new WP_Error( 'multisafepay-warning', $apiException->getMessage() );
        }
    }


    /**
     * Returns an array of the gateways available on the merchant account
     *
     * @return array
     */
    public function get_gateways() {
        try {
            $gateways = $this->get_gateway_manager()->getGateways( true );
            return $gateways;
        }
        catch ( ApiException $apiException ) {
            if( get_option( 'multisafepay_debugmode', false ) ) {
                $logger = wc_get_logger();
                $logger->log( 'warning', $apiException->getMessage() );
            }
            return new WP_Error( 'multisafepay-warning', $apiException->getMessage() );
        }
    }
}