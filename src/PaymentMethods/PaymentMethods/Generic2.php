<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods;

class Generic2 extends Generic {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_generic_2';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return __( 'Generic Gateway 2', 'multisafepay' );
    }

}
