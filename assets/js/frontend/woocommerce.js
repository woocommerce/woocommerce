/* global Cookies */
jQuery( function( $ ) {
	// Orderby
	$( '.woocommerce-ordering' ).on( 'change', 'select.orderby', function() {
		$( this ).closest( 'form' ).submit();
	});

	// Target quantity inputs on product pages
	$( 'input.qty:not(.product-quantity input.qty)' ).each( function() {
		var min = parseFloat( $( this ).attr( 'min' ) );

		if ( min >= 0 && parseFloat( $( this ).val() ) < min ) {
			$( this ).val( min );
		}
	});

	// Set a cookie and hide the store notice when the dismiss button is clicked
	jQuery( '.woocommerce-store-notice__dismiss-link' ).click( function() {
		Cookies.set( 'store_notice', 'hidden', { path: '/' } );
		jQuery( '.woocommerce-store-notice' ).hide();
	});

	// Check the value of that cookie and show/hide the notice accordingly
	if ( 'hidden' === Cookies.get( 'store_notice' ) ) {
		jQuery( '.woocommerce-store-notice' ).hide();
	} else {
		jQuery( '.woocommerce-store-notice' ).show();
	}
});
