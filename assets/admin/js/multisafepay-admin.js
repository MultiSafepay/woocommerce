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

            function addMultiSafepayTransactionLink() {
                const orderNumbers = $('.woocommerce-order-data__meta.order_number');
                const theRegex = /\((\d+)\)/;

                orderNumbers.each( function() {
                    const objectThis = $( this );
                    const currentHtml = objectThis.html();
                    if ( ( typeof multisafepayAdminData !== 'undefined' ) && multisafepayAdminData.transactionUrl ) {
                        const newHtml = currentHtml.replace( theRegex, ( match, transactionId ) => {
                            return '(<a href="' + multisafepayAdminData.transactionUrl + '" target="_blank" title="' + multisafepayAdminData.transactionLinkTitle + '">' + transactionId + '</a>)';
                        });
                        objectThis.html( newHtml );
                    }
                });
            }

            addMultiSafepayTransactionLink();
        });
})( jQuery );
