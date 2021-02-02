(function ($, multisafepay) {
    'use strict';

    $(
        function () {

			if ('undefined' === typeof multisafepay) {
				return;
			}

			initiate_tokenization();
			change_event();

			$( document ).ajaxComplete(
                function (e, xhr, settings) {
                    if (settings.url == '/?wc-ajax=update_order_review') {
                        if ($( "ul.wc_payment_methods li.payment_method_" + multisafepay.id + " div.payment_method_" + multisafepay.id + " .woocommerce-SavedPaymentMethods" ).length > 0 ) {
                            initiate_tokenization();
                            change_event();
                        }
                    }
                }
			);

			function initiate_tokenization() {
				if ($( "ul.wc_payment_methods li.payment_method_" + multisafepay.id + " div.payment_method_" + multisafepay.id + " .woocommerce-SavedPaymentMethods input:checked" ).val() != 'new' ) {
					$( "ul.wc_payment_methods li.payment_method_" + multisafepay.id + " div.payment_method_" + multisafepay.id + " p.woocommerce-SavedPaymentMethods-saveNew" ).hide();
				}
			}

			function change_event() {
				$( document ).on(
                    'change',
                    "ul.wc_payment_methods li.payment_method_" + multisafepay.id + " div.payment_method_" + multisafepay.id + " .woocommerce-SavedPaymentMethods input",
                    function () {
                        if ($( this ).val() === 'new') {
                            $( "ul.wc_payment_methods li.payment_method_" + multisafepay.id + " div.payment_method_" + multisafepay.id + " p.woocommerce-SavedPaymentMethods-saveNew" ).show();
                        } else {
                            $( "ul.wc_payment_methods li.payment_method_" + multisafepay.id + " div.payment_method_" + multisafepay.id + " p.woocommerce-SavedPaymentMethods-saveNew" ).hide();
                        }
                    }
                )
			}

		}
    )

})( jQuery, multisafepay );
