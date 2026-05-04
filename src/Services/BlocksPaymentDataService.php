<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Services;

use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Exception;
use MultiSafepay\WooCommerce\Utils\Hpos;
use MultiSafepay\WooCommerce\Utils\Logger;
use WC_Order;

/**
 * Maps Store API payment data to order meta for compatibility with legacy handlers.
 */
class BlocksPaymentDataService {

    public const META_KEY = '_multisafepay_blocks_payment_data';

    /**
     * Request-lifecycle Blocks payloads are cleared after checkout, but they are still
     * written to order meta first. Keep a defensive cap so oversized client-controlled
     * values do not hit the database and fail explicitly instead of truncating opaque payloads.
     */
    private const MAX_STORED_PAYMENT_DATA_VALUE_LENGTH = 20000;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Keys ending with these suffixes are only needed during the request lifecycle.
     */
    public const REQUEST_LIFECYCLE_KEY_PATTERN = '/(?:^|_)(payment_token|browser|payment_component_payload|payment_component_tokenize)$/';

    /**
     * @param Logger|null $logger
     */
    public function __construct( ?Logger $logger = null ) {
        $this->logger = $logger ?? new Logger();
    }

    /**
     * Get stored Blocks payment data from order meta.
     *
     * @param WC_Order $order
     * @return array
     */
    public function get_blocks_payment_data( WC_Order $order ): array {
        $data = $order->get_meta( self::META_KEY );
        return is_array( $data ) ? $data : array();
    }

    /**
     * Get a scalar value from Blocks payment meta.
     *
     * @param WC_Order $order
     * @param string   $key
     * @return string
     */
    public function get_blocks_payment_data_value( WC_Order $order, string $key ): string {
        if ( empty( $key ) ) {
            return '';
        }
        $data = $this->get_blocks_payment_data( $order );
        if ( ! isset( $data[ $key ] ) ) {
            return '';
        }

        $raw_value = $data[ $key ];
        if ( ! is_scalar( $raw_value ) ) {
            return '';
        }

        $value = (string) $raw_value;

        // Wallet payloads are JSON strings (Google Pay token and browser info).
        // sanitize_text_field() can alter JSON (e.g. stripping characters), which may break
        // server-side processing. These values are never rendered back to the customer,
        // so we preserve them as-is.
        if ( preg_match( '/(?:^|_)payment_token$/', $key ) || 'payment_token' === $key ) {
            return $value;
        }
        if ( preg_match( '/(?:^|_)browser$/', $key ) || 'browser' === $key ) {
            return $value;
        }

        return sanitize_text_field( $value );
    }

    /**
     * Save MultiSafepay Blocks payment component data to order meta.
     *
     * @param PaymentContext $context Holds context for the payment.
     * @param PaymentResult  $result  Result of the payment.
     * @return void
     * @throws Exception When submitted payment data exceeds the defensive storage limit.
     */
    public function save_blocks_payment_data_to_order_meta( PaymentContext $context, PaymentResult $result ): void {
        unset( $result );

        $payment_method = (string) ( $context->__get( 'payment_method' ) ?? '' );
        if ( empty( $payment_method ) ) {
            return;
        }

        if ( strpos( $payment_method, 'multisafepay_' ) !== 0 ) {
            return;
        }

        $payment_data = $context->__get( 'payment_data' );
        $payment_data = is_array( $payment_data ) ? $payment_data : array();

        $order = $context->__get( 'order' );
        if ( ! $order instanceof WC_Order ) {
            return;
        }

        // Limit stored keys to the expected MultiSafepay Blocks payload.
        // This avoids unintentionally persisting unrelated request data.
        //
        // We also allow the legacy keys (payment_token, browser) so the backend can
        // behave exactly like classic checkout when Blocks submits wallet-direct data.
        $allowed_key_pattern = '/^(?:multisafepay_[a-z0-9_]+_(payment_component_payload|payment_component_tokenize|payment_token|browser)|payment_token|browser)$/';

        $filtered_payment_data = array();

        foreach ( $payment_data as $key => $value ) {
            if ( ! is_string( $key ) || ! preg_match( $allowed_key_pattern, $key ) ) {
                continue;
            }

            if ( ! is_scalar( $value ) ) {
                continue;
            }

            $value = (string) $value;

            if ( strlen( $value ) > self::MAX_STORED_PAYMENT_DATA_VALUE_LENGTH ) {
                $this->logger->log_warning(
                    'Rejected oversized Blocks payment data for order ID ' . $order->get_id() .
                    ', key ' . $key .
                    ', received length ' . strlen( $value ) .
                    ', max allowed length ' . self::MAX_STORED_PAYMENT_DATA_VALUE_LENGTH
                );

                throw new Exception( __( 'We could not process your payment details. Please try again.', 'multisafepay' ) );
            }

            $filtered_payment_data[ $key ] = $value;
        }

        if ( empty( $filtered_payment_data ) ) {
            return;
        }

        Hpos::update_meta( $order, self::META_KEY, $filtered_payment_data );
    }

    /**
     * Remove keys matching a given pattern from stored Blocks payment data.
     *
     * @param WC_Order $order
     * @param string   $key_pattern
     * @return void
     */
    public function clear_blocks_payment_data_from_order_meta_by_pattern( WC_Order $order, string $key_pattern ): void {
        $blocks_payment_data = $this->get_blocks_payment_data( $order );

        if ( empty( $blocks_payment_data ) ) {
            return;
        }

        $did_remove_matching_data = false;

        foreach ( array_keys( $blocks_payment_data ) as $key ) {
            if ( ! is_string( $key ) || ! preg_match( $key_pattern, $key ) ) {
                continue;
            }

            unset( $blocks_payment_data[ $key ] );
            $did_remove_matching_data = true;
        }

        if ( ! $did_remove_matching_data ) {
            return;
        }

        if ( empty( $blocks_payment_data ) ) {
            Hpos::delete_meta( $order, self::META_KEY );
        }

        if ( ! empty( $blocks_payment_data ) ) {
            Hpos::update_meta( $order, self::META_KEY, $blocks_payment_data );
        }
    }
}
