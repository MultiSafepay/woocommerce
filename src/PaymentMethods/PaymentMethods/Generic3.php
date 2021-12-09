<?php declare( strict_types=1 );

namespace MultiSafepay\WooCommerce\PaymentMethods\PaymentMethods;

class Generic3 extends Generic {

    /**
     * @return string
     */
    public function get_payment_method_id(): string {
        return 'multisafepay_generic_3';
    }

    /**
     * @return string
     */
    public function get_payment_method_title(): string {
        return __( 'Generic Gateway 3', 'multisafepay' );
    }

}

