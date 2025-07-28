(function ($) {
    $(
        function (
        ) {
            function togglePaymentSettingsFields(conditional, fieldToToggle) {
                const formInput = $(fieldToToggle).closest('tr');
                const validValues = ['1', 'qr', 'qr_only'];
                const selectedValue = $(conditional).find('option:selected').val();
                const isValidSelection = validValues.indexOf(selectedValue) !== -1;

                // Initial state
                formInput[isValidSelection ? 'show' : 'hide']();

                // Handle select changes
                $(conditional).on('change', function() {
                    const currentValue = $(this).find('option:selected').val();
                    formInput[validValues.indexOf(currentValue) !== -1 ? 'show' : 'hide']();
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

            togglePaymentSettingsFields(
                '#woocommerce_multisafepay_bancontact_payment_component',
                '#woocommerce_multisafepay_bancontact_qr_width'
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
