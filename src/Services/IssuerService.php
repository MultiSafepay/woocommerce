<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services;

use Exception;
use MultiSafepay\Api\IssuerManager;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\WooCommerce\Utils\Logger;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @since      4.0.0
 */
class IssuerService {

    /**
     * @var IssuerManager
     */
    private $issuer_manager;

    /**
     * IssuerService constructor.
     */
    public function __construct() {
        $this->issuer_manager = ( new SdkService() )->get_issuer_manager();
    }

    /**
     * @param string $gateway_code
     * @return array
     */
    public function get_issuers( string $gateway_code ): array {
        try {
            return $this->issuer_manager->getIssuersByGatewayCode( $gateway_code );
        } catch ( ClientExceptionInterface $client_exception ) {
            Logger::log_error( $client_exception->getMessage() );
            return array();
        } catch ( ApiException $api_exception ) {
            Logger::log_error( $api_exception->getMessage() );
            return array();
        } catch ( Exception $exception ) {
            Logger::log_error( $exception->getMessage() );
            return array();
        }
    }
}
