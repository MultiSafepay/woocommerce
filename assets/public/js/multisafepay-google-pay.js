(function( $ ) {
	'use strict';

	$(
        function() {
			check_if_google_pay_is_available();
			$( document ).ajaxComplete(
                function(e, xhr, settings) {
                    if ( settings.url.indexOf( '?wc-ajax=update_order_review' ) != -1 ) {
                        check_if_google_pay_is_available();
                    }
                }
			);
		}
    );

	function check_if_google_pay_is_available() {

        let googlePayClient = new google.payments.api.PaymentsClient(
            {
                environment: 'PRODUCTION'
            }
        );

        const baseCardPaymentMethod = {
            type: 'CARD',
            parameters: {
                allowedCardNetworks: ['VISA','MASTERCARD'],
                allowedAuthMethods: ['PAN_ONLY','CRYPTOGRAM_3DS']
            }
        };

        const googlePayBaseConfiguration = {
            apiVersion: 2,
            apiVersionMinor: 0,
            allowedPaymentMethods: [baseCardPaymentMethod],
            existingPaymentMethodRequired: true
        }
        googlePayClient.isReadyToPay( googlePayBaseConfiguration ).then(
            ( response ) => {
                if ( ! response.result ) {
                    $( '.payment_method_multisafepay_googlepay' ).remove();
                }
            }
        );
	}

})( jQuery );
