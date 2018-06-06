/*global Simplify_commerce_params, SimplifyCommerce */
(function ( $ ) {

	// Form handler
	function simplifyFormHandler() {
		var $form = $( 'form.checkout, form#order_review, form#add_payment_method' );

		if ( ( $( '#payment_method_simplify_commerce' ).is( ':checked' ) && 'new' === $( 'input[name="wc-simplify_commerce-payment-token"]:checked' ).val() ) || ( '1' === $( '#woocommerce_add_payment_method' ).val() ) ) {

			if ( 0 === $( 'input.simplify-token' ).length ) {

				$form.block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				var card           = $( '#simplify_commerce-card-number' ).val(),
					cvc            = $( '#simplify_commerce-card-cvc' ).val(),
					expiry         = $.payment.cardExpiryVal( $( '#simplify_commerce-card-expiry' ).val() ),
					address1       = $form.find( '#billing_address_1' ).val() || '',
					address2       = $form.find( '#billing_address_2' ).val() || '',
					addressCountry = $form.find( '#billing_country' ).val() || '',
					addressState   = $form.find( '#billing_state' ).val() || '',
					addressCity    = $form.find( '#billing_city' ).val() || '',
					addressZip     = $form.find( '#billing_postcode' ).val() || '';

				addressZip = addressZip.replace( /-/g, '' );
				card = card.replace( /\s/g, '' );

				SimplifyCommerce.generateToken({
					key: Simplify_commerce_params.key,
					card: {
						number: card,
						cvc: cvc,
						expMonth: expiry.month,
						expYear: ( expiry.year - 2000 ),
						addressLine1: address1,
						addressLine2: address2,
						addressCountry: addressCountry,
						addressState: addressState,
						addressZip: addressZip,
						addressCity: addressCity
					}
				}, simplifyResponseHandler );

				// Prevent the form from submitting
				return false;
			}
		}

		return true;
	}

	// Handle Simplify response
	function simplifyResponseHandler( data ) {

		var $form  = $( 'form.checkout, form#order_review, form#add_payment_method' ),
			ccForm = $( '#wc-simplify_commerce-cc-form' );

		if ( data.error ) {

			// Show the errors on the form
			$( '.woocommerce-error, .simplify-token', ccForm ).remove();
			$form.unblock();

			// Show any validation errors
			if ( 'validation' === data.error.code ) {
				var fieldErrors = data.error.fieldErrors,
					fieldErrorsLength = fieldErrors.length,
					errorList = '';

				for ( var i = 0; i < fieldErrorsLength; i++ ) {
					errorList += '<li>' + Simplify_commerce_params[ fieldErrors[i].field ] + ' ' + Simplify_commerce_params.is_invalid  + ' - ' + fieldErrors[i].message + '.</li>';
				}

				ccForm.prepend( '<ul class="woocommerce-error">' + errorList + '</ul>' );
			}

		} else {

			// Insert the token into the form so it gets submitted to the server
			ccForm.append( '<input type="hidden" class="simplify-token" name="simplify_token" value="' + data.id + '"/>' );
			$form.submit();
		}
	}

	$( function () {

		$( document.body ).on( 'checkout_error', function () {
			$( '.simplify-token' ).remove();
		});

		/* Checkout Form */
		$( 'form.checkout' ).on( 'checkout_place_order_simplify_commerce', function () {
			return simplifyFormHandler();
		});

		/* Pay Page Form */
		$( 'form#order_review' ).on( 'submit', function () {
			return simplifyFormHandler();
		});

		/* Pay Page Form */
		$( 'form#add_payment_method' ).on( 'submit', function () {
			return simplifyFormHandler();
		});

		/* Both Forms */
		$( 'form.checkout, form#order_review, form#add_payment_method' ).on( 'change', '#wc-simplify_commerce-cc-form input', function() {
			$( '.simplify-token' ).remove();
		});

	});

}( jQuery ) );
