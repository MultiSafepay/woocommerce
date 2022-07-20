(function ($) {

    const PAYMENT_METHOD_SELECTOR = 'ul.wc_payment_methods input[type=\'radio\'][name=\'payment_method\']';

    class MultiSafepayMyBank {

        constructor(config) {
            this.gateway = 'multisafepay_mybank';

            // Triggered when change the payment method selected
            $( document ).on( 'payment_method_selected', ( event ) => { this.on_payment_method_selected( event ); } );

            // Triggered when something changes in the and start the process to refresh everything
            $( document ).on( 'update_checkout', ( event ) => { this.on_update_checkout( event ); } );

            // Triggered when something changed in the checkout and the process to refresh everything is finished
            $( document ).on( 'updated_checkout', ( event ) => { this.on_updated_checkout( event ); } );

            // Trigered when the checkout loads
            $( document ).on( 'init_checkout', ( event ) => { this.on_init_checkout( event ); } );

        }

        on_payment_method_selected( event ) {
            this.maybe_init_select_woo();
        }

        on_update_checkout( event ) {
            this.maybe_init_select_woo();
        }

        on_updated_checkout( event ) {
            this.maybe_init_select_woo();
        }

        on_init_checkout( event ) {
            this.maybe_init_select_woo();
        }

        is_selected() {
            if ( $( PAYMENT_METHOD_SELECTOR + ":checked" ).val() == this.gateway ) {
                return true;
            }
            return false;
        }

        maybe_init_select_woo() {

            if ( false === this.is_selected() ) {
                return;
            }

            $( '#' + this.gateway + '_issuer_id' ).selectWoo()
        }

    }

    if ( $( '#payment ul.wc_payment_methods li.payment_method_multisafepay_mybank' ).length > 0 ) {
        new MultiSafepayMyBank();
    }

})( jQuery );
