/*global jQuery,  woocommerceTokenizationParams */
jQuery( function( $ ) {

	var  wcTokenizationForm = {
		gatewayID: woocommerceTokenizationParams.gatewayID,
		userLoggedIn:  woocommerceTokenizationParams.userLoggedIn,

		hideForm: function() {
			$( '#wc-' + this.gatewayID + '-cc-form, #wc-' + this.gatewayID + '-echeck-form' ).hide();
		},

		showForm: function() {
			$( '#wc-' + this.gatewayID + '-cc-form, #wc-' + this.gatewayID + '-echeck-form' ).show();
		},

		showSaveNewCheckbox: function() {
			$( '#wc-' + this.gatewayID + '-new-payment-method-wrap' ).show();
		},

		hideSaveNewCheckbox: function() {
			$( '#wc-' + this.gatewayID + '-new-payment-method-wrap' ).hide();
		},

		showSaveNewCheckboxForLoggedInOnly: function() {
			if ( this.userLoggedIn ) {
				$( '#wc-' + this.gatewayID + '-new-payment-method-wrap' ).show();
			} else {
				$( '#wc-' + this.gatewayID + '-new-payment-method-wrap' ).hide();
			}
		}
	};

	$(  document.body ).on( 'updated_checkout', function() {

		// Make sure a radio button (1st) is selected if there is no is_default for this payment method..
		if ( ! $( 'input[name="wc-' +  woocommerceTokenizationParams.gatewayID + '-payment-token"]' ).is( ':checked' ) ) {
			$( 'input:radio[name="wc-' +  woocommerceTokenizationParams.gatewayID + '-payment-token"]:first' ).attr( 'checked', true );
			if ( 'new' === $( 'input:radio[name="wc-' +  woocommerceTokenizationParams.gatewayID + '-payment-token"]:first' ).val() ) {
				 wcTokenizationForm.showForm();
				 wcTokenizationForm.showSaveNewCheckboxForLoggedInOnly();
			} else {
				 wcTokenizationForm.hideForm();
				 wcTokenizationForm.hideSaveNewCheckbox();
			}
		} else {
			 wcTokenizationForm.hideForm();
			 wcTokenizationForm.hideSaveNewCheckbox();
		}

		// When a radio button is changed, make sure to show/hide our new CC info area
		$( 'input[name="wc-' +  woocommerceTokenizationParams.gatewayID + '-payment-token"]' ).change( function () {
			if ( 'new' === $( 'input[name="wc-' +  woocommerceTokenizationParams.gatewayID + '-payment-token"]:checked' ).val() ) {
				 wcTokenizationForm.showForm();
				 wcTokenizationForm.showSaveNewCheckboxForLoggedInOnly();
			} else {
				 wcTokenizationForm.hideForm();
				 wcTokenizationForm.hideSaveNewCheckbox();
			}
		} );

		// OR if create account is checked
		$ ( 'input#createaccount' ).change( function() {
			if ( $( this ).is( ':checked' ) ) {
				 wcTokenizationForm.showSaveNewCheckbox();
			} else {
				 wcTokenizationForm.hideSaveNewCheckbox();
			}
		} );

		// Don't show the "use new" radio button if we are a guest or only have one method..
		if ( 0 === $( '#wc-' +  woocommerceTokenizationParams.gatewayID + '-method-count' ).data( 'count' ) || !  woocommerceTokenizationParams.userLoggedIn ) {
			$( '.wc-' +  woocommerceTokenizationParams.gatewayID + '-payment-form-new-checkbox-wrap' ).hide();
		}

	} );

} );
