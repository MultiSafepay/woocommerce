<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services;

use MultiSafepay\Api\ApiTokenManager;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\WooCommerce\Utils\Logger;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Class ApiTokenService
 *
 * @package MultiSafepay\WooCommerce\Services
 */
class ApiTokenService {

    /**
     * Time in which the stored value in cache with an ApiToken will expired.
     */
    public const EXPIRATION_TIME_FOR_API_TOKEN_REQUEST = 240;

    /**
     * @var ApiTokenManager
     */
    public $api_token_manager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ApiTokenService constructor.
     *
     * @param Logger|null $logger
     */
    public function __construct( ?Logger $logger = null ) {
        $this->logger            = $logger ?? new Logger();
        $this->api_token_manager = ( new SdkService() )->get_api_token_manager();
    }

    /**
     * Returns a MultiSafepay ApiToken
     *
     * @return string
     */
    public function get_api_token(): string {
        if ( null === $this->api_token_manager ) {
            return '';
        }

        $cached_api_token = get_transient( 'multisafepay_api_token' );
        if ( false !== $cached_api_token ) {
            return $cached_api_token;
        }

        try {
            $api_token = $this->api_token_manager->get()->getApiToken();
        } catch ( ApiException | ClientExceptionInterface $exception ) {
            $this->logger->log_error( $exception->getMessage() );
            return '';
        }

        set_transient( 'multisafepay_api_token', $api_token, self::EXPIRATION_TIME_FOR_API_TOKEN_REQUEST );

        return $api_token;
    }
}
