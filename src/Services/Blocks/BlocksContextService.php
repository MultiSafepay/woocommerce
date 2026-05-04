<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services\Blocks;

use Throwable;
use WC_Blocks_Utils;

/**
 * Helpers for detecting WooCommerce Blocks / Store API request context.
 */
class BlocksContextService {

    /**
     * Returns true when the checkout page contains the WooCommerce Checkout block.
     *
     * This is used to decide whether the site is running block-based checkout.
     *
     * @return bool
     */
    public function is_checkout_blocks_active(): bool {
        if ( ! function_exists( 'wc_get_page_id' ) ) {
            return false;
        }

        if ( ! class_exists( '\\WC_Blocks_Utils' ) ) {
            return false;
        }

        $checkout_id = (int) wc_get_page_id( 'checkout' );
        if ( $checkout_id <= 0 ) {
            return false;
        }

        try {
            return (bool) WC_Blocks_Utils::has_block_in_page( $checkout_id, 'woocommerce/checkout' );
        } catch ( Throwable $exception ) {
            return false;
        }
    }

    /**
     * Returns true when the current request is a WooCommerce Store API request.
     *
     * @return bool
     */
    public function is_store_api_request(): bool {
        // Prefer WooCommerce's native detector when available (introduced in WC 9.x; not available in WC 6.x-8.x).
        // We keep the fallback below because this plugin supports older WooCommerce versions
        // and some Store API requests are routed via rest_route query vars, which the native
        // detector does not recognize consistently.
        if ( function_exists( 'WC' ) ) {
            $woocommerce = WC();
            if ( is_object( $woocommerce ) && method_exists( $woocommerce, 'is_store_api_request' ) ) {
                if ( $woocommerce->is_store_api_request() ) {
                    return true;
                }
            }
        }

        // Fallback for older WooCommerce versions and edge execution paths:
        // Store API requests may be routed either via pretty permalinks (/wp-json/wc/store/...)
        // or via the rest_route query param (?rest_route=/wc/store/...).
        // In some internal execution paths, REST_REQUEST may not be set when this code runs,
        // so we also check the explicit route.

        $rest_route = '';
        if ( isset( $GLOBALS['wp'] ) && is_object( $GLOBALS['wp'] ) && isset( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
            $rest_route = (string) $GLOBALS['wp']->query_vars['rest_route'];
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if ( '' === $rest_route && isset( $_GET['rest_route'] ) ) {
            $rest_route = sanitize_text_field( wp_unslash( $_GET['rest_route'] ) );
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ( '' !== $rest_route && false !== strpos( $rest_route, '/wc/store/' ) ) {
            return true;
        }

        if ( ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return false;
        }

        if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
            return false;
        }

        $request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
        // Store API routes are under /wc/store/...
        return false !== strpos( $request_uri, '/wc/store/' );
    }
}
