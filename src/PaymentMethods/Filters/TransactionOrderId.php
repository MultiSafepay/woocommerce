<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\PaymentMethods\Filters;

/**
 * Resolve transaction order id from the callback order number.
 */
class TransactionOrderId {

    /**
     * @param string $transactionid
     * @return int
     */
    public function multisafepay_transaction_order_id( string $transactionid ): int {
        if ( function_exists( 'wc_seq_order_number_pro' ) ) {
            return (int) wc_seq_order_number_pro()->find_order_by_order_number( $transactionid );
        }

        if ( function_exists( 'wc_sequential_order_numbers' ) ) {
            return (int) wc_sequential_order_numbers()->find_order_by_order_number( $transactionid );
        }

        return (int) $transactionid;
    }
}
