<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\PaymentMethods\Filters\TransactionOrderId;

class Test_TransactionOrderId extends WP_UnitTestCase {

    /**
     * @return void
     */
    public function test_returns_casted_transaction_id_when_no_sequential_plugin_is_active() {
        if ( function_exists( 'wc_seq_order_number_pro' ) || function_exists( 'wc_sequential_order_numbers' ) ) {
            $this->markTestSkipped( 'Sequential order number plugin is active in this environment.' );
        }

        $filter = new TransactionOrderId();

        $result = $filter->multisafepay_transaction_order_id( '12345' );

        $this->assertSame( 12345, $result );
    }
}
