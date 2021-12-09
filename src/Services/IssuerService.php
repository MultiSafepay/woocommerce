<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services;

use MultiSafepay\Api\IssuerManager;

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
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function get_issuers( string $gateway_code ): array {
        return $this->issuer_manager->getIssuersByGatewayCode( $gateway_code );
    }
}
