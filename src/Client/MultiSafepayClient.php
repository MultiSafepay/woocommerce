<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Client;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


/**
 * Class MultiSafepayClient
 */
class MultiSafepayClient implements ClientInterface {

    /**
     * Sends a request using wp_remote_request for the given PSR-7 request (RequestInterface)
     * and returns a PSR-7 response (ResponseInterface).
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function sendRequest( RequestInterface $request ): ResponseInterface {
        $request->getBody()->rewind();
        $args          = $this->get_headers_from_request_interface( $request );
        $response_data = wp_remote_request( $request->getUri(), $args );
        $body          = wp_remote_retrieve_body( $response_data );
        $response      = new Response( $response_data['response']['code'], $response_data['headers']->getAll(), $body, '1.1', null );
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
