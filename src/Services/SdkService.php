<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services;

use Exception;
use MultiSafepay\Api\ApiTokenManager;
use MultiSafepay\Api\GatewayManager;
use MultiSafepay\Api\Gateways\Gateway;
use MultiSafepay\Api\IssuerManager;
use MultiSafepay\Api\PaymentMethodManager;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidApiKeyException;
use MultiSafepay\Sdk;
use MultiSafepay\WooCommerce\Client\MultiSafepayClient;
use MultiSafepay\WooCommerce\Utils\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientExceptionInterface;
use WP_Error;

/**
 * This class returns the SDK object.
 *
 * Class SdkService
 *
 * @package MultiSafepay\WooCommerce\Services
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
     * @var Logger
     */
    private $logger;

    /**
     * SdkService constructor.
     *
     * @param  string      $api_key
     * @param  boolean     $test_mode
     * @param Logger|null $logger
     */
    public function __construct( string $api_key = null, bool $test_mode = null, ?Logger $logger = null ) {
        $this->api_key   = $api_key ?? $this->get_api_key();
        $this->test_mode = $test_mode ?? $this->get_test_mode();
        $this->logger    = $logger ?? new Logger();
        $psr_factory     = new Psr17Factory();
        $client          = new MultiSafepayClient();
        try {
            $this->sdk = new Sdk( $this->api_key, ( $this->test_mode ) ? false : true, $client, $psr_factory, $psr_factory );
        } catch ( InvalidApiKeyException $invalid_api_key_exception ) {
            set_transient( 'multisafepay_payment_methods', array() );
            $this->logger->log_error( $invalid_api_key_exception->getMessage() );
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
            return get_option( 'multisafepay_test_api_key', '' );
        }
        return get_option( 'multisafepay_api_key', '' );
    }


    /**
     * Returns gateway manager
     *
     * @return GatewayManager|WP_Error
     */
    public function get_gateway_manager() {
        try {
            return $this->sdk->getGatewayManager();
        } catch ( ApiException $api_exception ) {
            $this->logger->log_error( $api_exception->getMessage() );
            return new WP_Error( 'multisafepay-warning', $api_exception->getMessage() );
        }
    }


    /**
     * Returns an array of the gateways available on the merchant account
     *
     * @return Gateway[]|WP_Error
     */
    public function get_gateways() {
        try {
            return $this->get_gateway_manager()->getGateways( true );
        } catch ( ApiException $api_exception ) {
            $this->logger->log_error( $api_exception->getMessage() );
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


    /**
     * @return Sdk
     */
    public function get_sdk(): Sdk {
        return $this->sdk;
    }

    /**
     * Returns api token manager
     *
     * @return ApiTokenManager
     */
    public function get_api_token_manager(): ?ApiTokenManager {
        if ( null === $this->sdk ) {
            $this->logger->log_error( 'SDK is not initialized' );
            return null;
        }
        return $this->sdk->getApiTokenManager();
    }

    /**
     * Returns a PaymentMethodManager instance
     *
     * @return PaymentMethodManager|null
     */
    public function get_payment_method_manager(): ?PaymentMethodManager {
        if ( null === $this->sdk ) {
            $this->logger->log_error( 'SDK is not initialized' );
            return null;
        }
        try {
            return $this->sdk->getPaymentMethodManager();
        } catch ( ApiException $api_exception ) {
            $this->logger->log_error( $api_exception->getMessage() );
            return null;
        }
    }

    /**
     * Returns an array of tokens for the given customer reference and gateway code
     *
     * @param string $customer_reference
     * @param string $gateway_code
     * @return array
     */
    public function get_payment_tokens( string $customer_reference, string $gateway_code ): array {
        try {
            $tokens = $this->sdk->getTokenManager()->getListByGatewayCodeAsArray( $customer_reference, $gateway_code );
        } catch ( ApiException $api_exception ) {
            $this->logger->log_error( $api_exception->getMessage() );
            return array();
        } catch ( ClientExceptionInterface $client_exception ) {
            $this->logger->log_error( $client_exception->getMessage() );
            return array();
        } catch ( Exception $exception ) {
            $this->logger->log_error( $exception->getMessage() );
            return array();
        }

        return $tokens;
    }

    /**
     * Return the MultiSafepay Merchant Account ID
     *
     * @return int
     */
    public function get_multisafepay_account_id(): int {
        try {
            $account_manager     = $this->sdk->getAccountManager();
            $gateway_merchant_id = $account_manager->get()->getAccountId();
        } catch ( ApiException | ClientExceptionInterface | Exception $exception ) {
            $this->logger->log_error( 'Error when try to set the merchant credentials: ' . $exception->getMessage() );
        }

        return $gateway_merchant_id ?? 0;
    }
}
