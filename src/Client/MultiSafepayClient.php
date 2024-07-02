<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Client;

use Exception;
use MultiSafepay\WooCommerce\Utils\Logger;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class MultiSafepayClient
 */
class MultiSafepayClient implements ClientInterface {

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger|null $logger
     */
    public function __construct( ?Logger $logger = null ) {
        $this->logger = $logger ?? new Logger();
    }

    /**
     * Sends a request using wp_remote_request for the given PSR-7 request (RequestInterface)
     * and returns a PSR-7 response (ResponseInterface).
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function sendRequest( RequestInterface $request ): ResponseInterface {
        $request->getBody()->rewind();
        $args = $this->get_headers_from_request_interface( $request );

        try {
            $response_data = wp_remote_request( $request->getUri()->__toString(), $args );
            if ( is_wp_error( $response_data ) ) {
                throw new Exception( $response_data->get_error_message() );
            }
        } catch ( Exception $exception ) {
            $this->logger->log_error( 'Error when process request via MultiSafepayClient: ' . $exception->getMessage() );
            throw new Exception( $exception->getMessage() );
        }

        $body     = wp_remote_retrieve_body( $response_data );
        $response = new Response( $response_data['response']['code'], $response_data['headers']->getAll(), $body, '1.1', null );
        $response->getBody()->rewind();
        return $response;
    }

    /**
     * Return an array of headers to be used in wp_remote_request
     *
     * @param RequestInterface $request
     * @return array
     */
    private function get_headers_from_request_interface( RequestInterface $request ): array {
        $args = array(
            'method'      => $request->getMethod(),
            'body'        => $request->getBody()->getContents(),
            'httpversion' => $request->getProtocolVersion(),
            'timeout'     => 30,
        );
        foreach ( $request->getHeaders() as $name => $value ) {
            $args['headers'][ $name ] = $value[0];
        }
        return $args;
    }
}
