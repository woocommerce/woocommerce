jQuery( function( $ ) {
	// woocommerce_params is required to continue, ensure the object exists
	if ( 'undefined' === typeof woocommerce_params ) {
		return false;
	}

	$( '#add_payment_method' )

	/* Payment option selection */

		.on( 'click init_add_payment_method', '.payment_methods input.input-radio', function() {
			if ( $( 1 < '.payment_methods input.input-radio' ).length ) {
				var targetPaymentBox = $( 'div.payment_box.' + $( this ).attr( 'ID' ) );
				if ( $( this ).is( ':checked' ) && ! targetPaymentBox.is( ':visible' ) ) {
					$( 'div.payment_box' ).filter( ':visible' ).slideUp( 250 );
					if ( $( this ).is( ':checked' ) ) {
						$( 'div.payment_box.' + $( this ).attr( 'ID' ) ).slideDown( 250 );
					}
				}
			} else {
				$( 'div.payment_box' ).show();
			}
		} )

		// Trigger initial click
		.find( 'input[name=payment_method]:checked' ).click();

	$( '#add_payment_method' ).submit( function() {
		$( '#add_payment_method' ).block(
			{ message: null, overlayCSS: { background: '#fff', opacity: 0.6 } }
		);
	} );

	$( document.body ).trigger( 'init_add_payment_method' );
} );
