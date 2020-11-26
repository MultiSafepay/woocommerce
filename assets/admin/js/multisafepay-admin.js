(function( $, multisafepay ) {
	'use strict';
	$(function() {

		if ( 'undefined' === typeof multisafepay ) {
			return;
		}

		$( '.wc_gateways' ).on( 'click', '.wc-payment-gateway-method-toggle-enabled', function() {
			var $cell   = $( this ),
				$row    = $cell.closest( 'tr' ),
				$toggle = $cell.find( '.woocommerce-input-toggle' );
			var data = {
				action: 		'woocommerce_multisafepay_toggle_gateway_enabled',
				security: 		multisafepay.nonces.multisafepay_gateway_toggle,
				gateway_id: 	$row.data( 'gateway_id' ),
				gateway_name: 	$row.find( 'td.name a' ).text()
			};
			$toggle.addClass( 'woocommerce-input-toggle--loading' );
			$.ajax( {
				url:      multisafepay.wp_ajax_url,
				data:     data,
				dataType : 'json',
				type     : 'POST',
				success:  function( response ) {
					if ( 'is_setup' === response.data ) {
						return false;
					} else if ( 'needs_setup' === response.data ) {
						window.location.href = multisafepay.multisafepay_settings_url;
					}
				}
			} );
			return false;
		});
	});
})( jQuery, multisafepay );
