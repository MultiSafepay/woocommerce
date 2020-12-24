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
 */

namespace MultiSafepay\WooCommerce\Services;

use MultiSafepay\Api\GatewayManager;
use MultiSafepay\Api\Gateways\Gateway;
use MultiSafepay\Api\IssuerManager;
use MultiSafepay\Api\TransactionManager;
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
     * @param  string  $api_key
     * @param  boolean $test_mode
     */
    public function __construct( string $api_key = null, bool $test_mode = null ) {
        $this->api_key   = $api_key ?? $this->get_api_key();
        $this->test_mode = $test_mode ?? $this->get_test_mode();

        try {
            $this->sdk = new Sdk( $this->api_key, ( $this->test_mode ) ? false : true );
        } catch ( InvalidApiKeyException $invalid_api_key_exception ) {
            if ( get_option( 'multisafepay_debugmode', false ) ) {
                $logger = wc_get_logger();
                $logger->log( 'warning', $invalid_api_key_exception->getMessage() );
            }
        }
    }

    /**
     * Returns if test mode is enable
     *
     * @return  boolean
     */
    public function get_test_mode(): bool {
        return (bool) get_option( 'multisafepay_testmode', false );
    }

    /**
     * Returns api key set in settings page according with
     * the environment selected
     *
     * @return  string
     */
    public function get_api_key(): string {
        if ( $this->get_test_mode() ) {
            return get_option( 'multisafepay_test_api_key', false );
        }
        return get_option( 'multisafepay_api_key', false );
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
        } catch ( ApiException $api_exception ) {
            if ( get_option( 'multisafepay_debugmode', false ) ) {
                $logger = wc_get_logger();
                $logger->log( 'warning', $api_exception->getMessage() );
            }
            return new WP_Error( 'multisafepay-warning', $api_exception->getMessage() );
        }
    }


    /**
     * Returns an array of the gateways available on the merchant account
     *
     * @return Gateway[]
     */
    public function get_gateways() {
        try {
            $gateways = $this->get_gateway_manager()->getGateways( true );
            return $gateways;
        } catch ( ApiException $api_exception ) {
            if ( get_option( 'multisafepay_debugmode', false ) ) {
                $logger = wc_get_logger();
                $logger->log( 'warning', $api_exception->getMessage() );
            }
            return new WP_Error( 'multisafepay-warning', $api_exception->getMessage() );
        }
    }

    /**
     * Returns transaction manager
     *
     * @return  TransactionManager
     */
    public function get_transaction_manager(): TransactionManager {
        return $this->sdk->getTransactionManager();
    }

    /**
     * Returns issuer manager
     *
     * @return  IssuerManager
     */
    public function get_issuer_manager(): IssuerManager {
        return $this->sdk->getIssuerManager();
    }
}
