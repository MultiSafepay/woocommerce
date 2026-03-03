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

            function getDirectPaymentConfirmationText() {
                const defaultTitle = 'Direct payment activation confirmation';
                const defaultMessage = 'Before enabling %payment_method% Direct, confirm all prerequisites are fulfilled.';

                if (
                    typeof multisafepayAdminData !== 'undefined' &&
                    multisafepayAdminData.directPaymentConfirmation
                ) {
                    return {
                        title: multisafepayAdminData.directPaymentConfirmation.title || defaultTitle,
                        messageTemplate: multisafepayAdminData.directPaymentConfirmation.messageTemplate || defaultMessage,
                    };
                }

                return {
                    title: defaultTitle,
                    messageTemplate: defaultMessage,
                };
            }

            function getDirectPaymentMethodName(fieldId) {
                if (fieldId.indexOf('_googlepay_') !== -1) {
                    return 'Google Pay';
                }

                if (fieldId.indexOf('_applepay_') !== -1) {
                    return 'Apple Pay';
                }

                return 'this payment method';
            }

            function addDirectPaymentActivationSafeguard() {
                const directToggleSelector = 'select[id^="woocommerce_multisafepay_"][id$="_use_direct_button"]';
                const previousValueKey = 'multisafepayPreviousValue';
                const skipConfirmationKey = 'multisafepaySkipConfirmation';
                const directToggles = $(directToggleSelector);
                const confirmationText = getDirectPaymentConfirmationText();

                if (!directToggles.length) {
                    return;
                }

                directToggles.each(function() {
                    $(this).data(previousValueKey, $(this).val());
                });

                directToggles.on('change', function() {
                    const field = $(this);

                    if (field.data(skipConfirmationKey)) {
                        field.data(skipConfirmationKey, false);
                        field.data(previousValueKey, field.val());
                        return;
                    }

                    const previousValue = field.data(previousValueKey);
                    const currentValue = field.val();

                    if (previousValue !== '1' && currentValue === '1') {
                        const paymentMethodName = getDirectPaymentMethodName(field.attr('id') || '');
                        const confirmationMessage = confirmationText.messageTemplate.replace('%payment_method%', paymentMethodName);
                        const confirmation = window.confirm(confirmationText.title + '\n\n' + confirmationMessage);

                        if (!confirmation) {
                            field.val(previousValue || '0');
                            field.data(skipConfirmationKey, true);
                            field.trigger('change');
                            return;
                        }
                    }

                    field.data(previousValueKey, field.val());
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

            addDirectPaymentActivationSafeguard();

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
