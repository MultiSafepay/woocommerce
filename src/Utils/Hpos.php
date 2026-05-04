<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

use WC_Order;

/**
 * Class HPOS Checker
 *
 * @package MultiSafepay\WooCommerce\Utils
 */
class Hpos {

    /**
     * WooCommerce version where HPOS was released
     */
    public const HPOS_RELEASE_VERSION = '7.1.0';

    /**
     * Get the WooCommerce version
     *
     * @return string|null
     */
    public static function get_woocommerce_version(): ?string {
        if ( defined( 'WC_VERSION' ) && ! empty( WC_VERSION ) ) {
            return WC_VERSION;
        }
        if ( function_exists( 'WC' ) && ! empty( WC()->version ) ) {
            return WC()->version;
        }
        return null;
    }

    /**
     * Check if the WooCommerce version is compatible with HPOS
     *
     * @return bool
     */
    public static function is_active(): bool {
        return ( 'yes' === get_option( 'woocommerce_custom_orders_table_enabled', 'no' ) ) &&
            version_compare( self::get_woocommerce_version(), self::HPOS_RELEASE_VERSION, '>=' );
    }

    /**
     * @param WC_Order          $order
     * @param string            $key
     * @param string|array|bool $value
     *
     * @return bool|int
     */
    public static function update_meta( WC_Order $order, string $key, $value ) {
        if ( self::is_active() ) {
            $order->update_meta_data( $key, $value );
            return $order->save();
        }

        return update_post_meta( $order->get_id(), $key, $value );
    }

    /**
     * Queue order meta-data deletion.
     *
     * @param WC_Order $order
     * @param string   $key
     *
     * @return int|void
     */
    public static function delete_meta( WC_Order $order, string $key ) {
        if ( self::is_active() ) {
            $order->delete_meta_data( $key );
            return $order->save();
        }

        delete_post_meta( $order->get_id(), $key );
    }

    /**
     * Get the order metadata
     *
     * @param WC_Order $order
     * @param string   $key
     * @param bool     $single
     *
     * @return mixed
     */
    public static function get_meta( WC_Order $order, string $key, bool $single = true ) {
        if ( self::is_active() ) {
            return $order->get_meta( $key );
        }

        return get_post_meta( $order->get_id(), $key, $single );
    }
}
