jQuery( function( $ ) {

	// woocommerce_params is required to continue, ensure the object exists
	if ( typeof woocommerce_params === 'undefined' ) {
		return false;
	}

	var $form = $( '#add_payment_method' );

	/**
	 * Create the API object passed to custom place order button render callbacks.
	 * This is specific to the add-payment-method page.
	 *
	 * @return {Object} API object with validate and submit methods
	 */
	function createAddPaymentMethodApi() {

		return {
			/**
			 * Validate the form.
			 * For add payment method, there's minimal validation - the payment gateway handles most of it.
			 *
			 * @return {Promise<{hasError: boolean}>} Promise resolving to a validation result
			 */
			validate: function() {
				return new Promise( function( resolve ) {
					// The "add payment method" page has no form validation needs.
					resolve( { hasError: false } );
				} );
			},

			/**
			 * Submit the "add payment method" form.
			 */
			submit: function() {
				$form.trigger( 'submit' );
			}
		};
	}

	// When a gateway registers after a page load, render its button if it's selected.
	$( document.body ).on( 'wc_custom_place_order_button_registered', function( e, gatewayId ) {
		wc.customPlaceOrderButton.__maybeShow( gatewayId, createAddPaymentMethodApi() );
	} );

	/* Payment option selection */
	$form.on( 'click init_add_payment_method', '.payment_methods input.input-radio', function() {
		if ( $( '.payment_methods input.input-radio' ).length > 1 ) {
			var target_payment_box = $( 'div.payment_box.' + $( this ).attr( 'ID' ) );
			if ( $( this ).is( ':checked' ) && ! target_payment_box.is( ':visible' ) ) {
				$( 'div.payment_box' ).filter( ':visible' ).slideUp( 250 );
				if ( $( this ).is( ':checked' ) ) {
					$( 'div.payment_box.' + $( this ).attr( 'ID' ) ).slideDown( 250 );
				}
			}
		} else {
			$( 'div.payment_box' ).show();
		}

		// Handle custom place order button for selected gateway
		wc.customPlaceOrderButton.__maybeShow( $( this ).val(), createAddPaymentMethodApi() );
	});

	// Hide default button immediately if initially selected gateway has custom button.
	// This must happen BEFORE triggering click to prevent flash of the default button.
	var $initialPaymentMethod = $form.find( 'input[name="payment_method"]:checked' );
	if ( $initialPaymentMethod.length ) {
		wc.customPlaceOrderButton.__maybeHideDefaultButtonOnInit( $initialPaymentMethod.val() );
	}

	// Trigger initial click
	$form.find( 'input[name=payment_method]:checked' ).trigger( 'click' );

	$form.on( 'submit', function() {
		$form.block({ message: null, overlayCSS: { background: '#fff', opacity: 0.6 } });
	});

	$( document.body ).trigger( 'init_add_payment_method' );

	// Prevent firing multiple requests upon double clicking the buttons in payment methods table
	$(' .woocommerce .payment-method-actions .button.delete' ).on( 'click' , function( event ) {
		if ( $( this ).hasClass( 'disabled' ) ) {
			event.preventDefault();
		}

		$( this ).addClass( 'disabled' );
	});

});
