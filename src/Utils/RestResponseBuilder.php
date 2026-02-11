<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

use WP_REST_Response;

/**
 * Class RestResponseBuilder
 *
 * Shared helper for building REST responses with explicit no-store cache headers.
 *
 * @package MultiSafepay\WooCommerce\Utils
 */
class RestResponseBuilder {

    /**
     * Build a REST response with explicit anti-cache headers.
     *
     * @param int   $status  HTTP status code
     * @param mixed $body    Response body
     * @param array $headers Optional associative array of custom headers (e.g., ['Location' => '...', 'Content-Type' => '...'])
     * @return WP_REST_Response
     */
    public static function build_response( int $status = 200, $body = 'OK', array $headers = array() ): WP_REST_Response {
        $response = new WP_REST_Response( $body, $status );

        // Set default Content-Type as required by MultiSafepay webhook acknowledgment
        if ( $status < 300 || $status >= 400 ) {
            $response->header( 'Content-Type', 'text/plain; charset=UTF-8' );
        }

        $nocache_headers = function_exists( 'wp_get_nocache_headers' )
            ? wp_get_nocache_headers()
            : array(
                'Expires'       => 'Wed, 11 Jan 1984 05:00:00 GMT',
                'Cache-Control' => 'no-cache, must-revalidate, max-age=0, no-store, private',
            );

        foreach ( $nocache_headers as $header => $header_value ) {
            if ( 'Last-Modified' === $header ) {
                continue;
            }
            if ( empty( $header_value ) ) {
                continue;
            }
            $response->header( $header, (string) $header_value );
        }

        $response->header( 'Pragma', 'no-cache' );

        // Apply custom headers (can override defaults like Content-Type)
        foreach ( $headers as $header => $header_value ) {
            $response->header( $header, (string) $header_value );
        }

        return $response;
    }
}
