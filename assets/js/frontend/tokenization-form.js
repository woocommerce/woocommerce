( function( $ ) {
	$( function() {
		var wcTokenizationForm = (function() {
			function wcTokenizationForm( target ) {
				var $target             = $( target ),
					$formWrap           = $target.closest( '.payment_box' ),
					$wcTokenizationForm = this;

				this.onTokenChange = function() {
					if ( 'new' === $( this ).val() ) {
						 $wcTokenizationForm.showForm();
						 $wcTokenizationForm.showSaveNewCheckbox();
					} else {
						 $wcTokenizationForm.hideForm();
						 $wcTokenizationForm.hideSaveNewCheckbox();
					}
				};

				this.onCreateAccountChange = function() {
					if ( $( this ).is( ':checked' ) ) {
						 $wcTokenizationForm.showSaveNewCheckbox();
					} else {
						 $wcTokenizationForm.hideSaveNewCheckbox();
					}
				};

				this.onDisplay = function() {
					// Make sure a radio button is selected if there is no is_default for this payment method..
					if ( 0 === $( ':input.woocommerce-SavedPaymentMethods-tokenInput:checked', $target ).length ) {
						$( ':input.woocommerce-SavedPaymentMethods-tokenInput:last', $target ).prop( 'checked', true );
					}

					// Don't show the "use new" radio button if we only have one method..
					if ( 0 === $target.data( 'count' ) ) {
						$( '.woocommerce-SavedPaymentMethods-new', $target ).hide();
					}

					// Trigger change event
					$( ':input.woocommerce-SavedPaymentMethods-tokenInput:checked', $target ).trigger( 'change' );

					// Hide "save card" if "Create Account" is not checked.
					// Check that the field is shown in the form - some plugins and force create account remove it
					if ( $( 'input#createaccount' ).length && ! $('input#createaccount').is( ':checked' ) ) {
						$wcTokenizationForm.hideSaveNewCheckbox();
      				}

				};

				this.hideForm = function() {
					$( '.wc-payment-form', $formWrap ).hide();
				};

				this.showForm = function() {
					$( '.wc-payment-form', $formWrap ).show();
				};

				this.showSaveNewCheckbox = function() {
					$( '.woocommerce-SavedPaymentMethods-saveNew', $formWrap ).show();
				};

				this.hideSaveNewCheckbox = function() {
					$( '.woocommerce-SavedPaymentMethods-saveNew', $formWrap ).hide();
				};

				// When a radio button is changed, make sure to show/hide our new CC info area
				$( ':input.woocommerce-SavedPaymentMethods-tokenInput', $target ).change( this.onTokenChange );

				// OR if create account is checked
				$ ( 'input#createaccount' ).change( this.onCreateAccountChange );

				this.onDisplay();
			}

			return wcTokenizationForm;
		})();

		$( document.body ).on( 'updated_checkout', function() {
			// Loop over gateways with saved payment methods
			var $saved_payment_methods = $( 'ul.woocommerce-SavedPaymentMethods' );

			$saved_payment_methods.each( function() {
				new wcTokenizationForm( this );
			} );
		} );
	});
})( jQuery );
