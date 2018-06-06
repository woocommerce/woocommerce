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
	$( '.woocommerce-store-notice__dismiss-link' ).click( function() {
		Cookies.set( 'store_notice', 'hidden', { path: '/' } );
		$( '.woocommerce-store-notice' ).hide();
	});

	// Check the value of that cookie and show/hide the notice accordingly
	if ( 'hidden' === Cookies.get( 'store_notice' ) ) {
		$( '.woocommerce-store-notice' ).hide();
	} else {
		$( '.woocommerce-store-notice' ).show();
	}

	// Make form field descriptions toggle on focus.
	$( document.body ).on( 'click', function() {
		$( '.woocommerce-input-wrapper span.description:visible' ).prop( 'aria-hidden', true ).slideUp( 250 );
	} );

	$( '.woocommerce-input-wrapper' ).on( 'click', function( event ) {
		event.stopPropagation();
	} );

	$( '.woocommerce-input-wrapper :input' )
		.on( 'keydown', function( event ) {
			var input       = $( this ),
				parent      = input.parent(),
				description = parent.find( 'span.description' );

			if ( 27 === event.which && description.length && description.is( ':visible' ) ) {
				description.prop( 'aria-hidden', true ).slideUp( 250 );
				event.preventDefault();
				return false;
			}
		} )
		.on( 'focus', function() {
			var input       = $( this ),
				parent      = input.parent(),
				description = parent.find( 'span.description' );

			parent.addClass( 'currentTarget' );

			$( '.woocommerce-input-wrapper:not(.currentTarget) span.description:visible' ).prop( 'aria-hidden', true ).slideUp( 250 );

			if ( description.length && description.is( ':hidden' ) ) {
				description.prop( 'aria-hidden', false ).slideDown( 250 );
			}

			parent.removeClass( 'currentTarget' );
		} );

	// Common scroll to element code.
	$.scroll_to_notices = function( scrollElement ) {
		var isSmoothScrollSupported = 'scrollBehavior' in document.documentElement.style;

		if ( scrollElement.length ) {
			if ( isSmoothScrollSupported ) {
				scrollElement[0].scrollIntoView({
					behavior: 'smooth',
					block:    'center'
				});
			} else {
				$( 'html, body' ).animate( {
					scrollTop: ( scrollElement.offset().top - 100 )
				}, 1000 );
			}
		}
	};
});
