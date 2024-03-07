(function ($) {
    $(
        function (
        ) {
            function togglePaymentSettingsFields( direct, merchantInfo ) {
                const formInput = $( merchantInfo ).closest( 'tr' );

                if ( $( direct ).find( 'option:selected' ).val() === '1' ) {
                    formInput.show();
                } else {
                    formInput.hide();
                }

                $( direct ).on( 'change', function () {
                    if ( $( this ).find( 'option:selected' ).val() === '1' ) {
                        formInput.show();
                    } else {
                        formInput.hide();
                    }
                });
            }

            togglePaymentSettingsFields(
                '#woocommerce_multisafepay_googlepay_use_direct_button',
                '#woocommerce_multisafepay_googlepay_merchant_name, #woocommerce_multisafepay_googlepay_merchant_id'
            );

            togglePaymentSettingsFields(
                '#woocommerce_multisafepay_applepay_use_direct_button',
                '#woocommerce_multisafepay_applepay_merchant_name'
            );
        }
    );
})( jQuery );
