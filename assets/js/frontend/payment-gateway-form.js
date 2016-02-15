/*global jQuery, woocommercePaymentGatewayParams */
jQuery( function( $ ) {

	var wcPaymentGatewayForm = {
		gatewayID: woocommercePaymentGatewayParams.gatewayID,
		userLoggedIn: woocommercePaymentGatewayParams.userLoggedIn,

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
		if ( ! $( 'input[name="wc-' + woocommercePaymentGatewayParams.gatewayID + '-payment-token"]' ).is( ':checked' ) ) {
			$( 'input:radio[name="wc-' + woocommercePaymentGatewayParams.gatewayID + '-payment-token"]:first' ).attr( 'checked', true );
			if ( 'new' === $( 'input:radio[name="wc-' + woocommercePaymentGatewayParams.gatewayID + '-payment-token"]:first' ).val() ) {
				wcPaymentGatewayForm.showForm();
				wcPaymentGatewayForm.showSaveNewCheckboxForLoggedInOnly();
			} else {
				wcPaymentGatewayForm.hideForm();
				wcPaymentGatewayForm.hideSaveNewCheckbox();
			}
		} else {
			wcPaymentGatewayForm.hideForm();
			wcPaymentGatewayForm.hideSaveNewCheckbox();
		}

		// When a radio button is changed, make sure to show/hide our new CC info area
		$( 'input[name="wc-' + woocommercePaymentGatewayParams.gatewayID + '-payment-token"]' ).change( function () {
			if ( 'new' === $( 'input[name="wc-' + woocommercePaymentGatewayParams.gatewayID + '-payment-token"]:checked' ).val() ) {
				wcPaymentGatewayForm.showForm();
				wcPaymentGatewayForm.showSaveNewCheckboxForLoggedInOnly();
			} else {
				wcPaymentGatewayForm.hideForm();
				wcPaymentGatewayForm.hideSaveNewCheckbox();
			}
		} );

		// OR if create account is checked
		$ ( 'input#createaccount' ).change( function() {
			if ( $( this ).is( ':checked' ) ) {
				wcPaymentGatewayForm.showSaveNewCheckbox();
			} else {
				wcPaymentGatewayForm.hideSaveNewCheckbox();
			}
		} );

		// Don't show the "use new" radio button if we are a guest or only have one method..
		if ( 0 === $( '#wc-' + woocommercePaymentGatewayParams.gatewayID + '-method-count' ).data( 'count' ) || ! woocommercePaymentGatewayParams.userLoggedIn ) {
			$( '.wc-' + woocommercePaymentGatewayParams.gatewayID + '-payment-form-new-checkbox-wrap' ).hide();
		}

	} );

} );
