/* global jQuery, wcpay_core_checkout_config */
( function( $, window, document ) {
	'use strict';

	var config = window.wcpay_core_checkout_config || {};
	var gatewayId = config.gatewayId || 'woocommerce_payments';
	var stripe = null;
	var elements = null;
	var paymentElement = null;
	var isSubmittingWithPaymentMethod = false;

	function isSelectedGateway() {
		return (
			$( 'input[name="payment_method"]:checked' ).val() === gatewayId ||
			$( 'input[name="payment_method"]' ).filter( '[value="' + gatewayId + '"]' ).length === 1
		);
	}

	function setError( message ) {
		var errorElement = document.getElementById( 'wcpay-core-payment-errors' );
		if ( ! errorElement ) {
			return;
		}

		errorElement.textContent = message || '';
		errorElement.hidden = ! message;
	}

	function ensureHiddenField( form, name, value ) {
		var field = form.find( 'input[name="' + name + '"]' );
		if ( ! field.length ) {
			field = $( '<input />', {
				type: 'hidden',
				name: name,
			} ).appendTo( form );
		}

		field.val( value || '' );
	}

	function appendPaymentFields( form, paymentMethod, error ) {
		var fingerprint = paymentMethod && paymentMethod.card ? paymentMethod.card.fingerprint : '';

		ensureHiddenField( form, 'wcpay-payment-method', paymentMethod && paymentMethod.id ? paymentMethod.id : '' );
		ensureHiddenField( form, 'wcpay-payment-method-error-code', error && error.code ? error.code : '' );
		ensureHiddenField( form, 'wcpay-payment-method-error-message', error && error.message ? error.message : '' );
		ensureHiddenField( form, 'wcpay-fingerprint', fingerprint || '' );
	}

	function initializeStripeElement() {
		var container = document.getElementById( 'wcpay-core-payment-element' );
		if ( ! container || ! config.isCoreNativeCheckoutAvailable || ! config.publishableKey || ! window.Stripe ) {
			return;
		}

		if ( paymentElement ) {
			return;
		}

		stripe = window.Stripe( config.publishableKey, {
			locale: config.locale || 'auto',
			stripeAccount: config.accountId || undefined,
		} );
		elements = stripe.elements( {
			mode: 'payment',
			amount: config.cartTotal || 0,
			currency: ( config.currency || 'usd' ).toLowerCase(),
			paymentMethodTypes: [ 'card' ],
		} );
		paymentElement = elements.create( 'payment' );
		paymentElement.mount( container );
	}

	function createPaymentMethodAndSubmit() {
		var form = $( 'form.checkout' );

		if ( ! stripe || ! elements ) {
			appendPaymentFields( form, null, null );
			return true;
		}

		stripe
			.createPaymentMethod( {
				elements: elements,
			} )
			.then( function( result ) {
				if ( result.error ) {
					appendPaymentFields( form, null, result.error );
					setError( result.error.message );
					$( document.body ).trigger( 'checkout_error', [ result.error.message ] );
					return;
				}

				appendPaymentFields( form, result.paymentMethod, null );
				setError( '' );
				isSubmittingWithPaymentMethod = true;
				form.trigger( 'submit' );
			} );

		return false;
	}

	function updateOrderStatusAfterConfirmation( intentId ) {
		if ( ! config.usesLegacyOrderStatusBridge || ! config.updateOrderStatusNonce || ! config.ajaxUrl || ! intentId ) {
			return $.Deferred().resolve().promise();
		}

		return $.post( config.ajaxUrl, {
			action: 'wcpay_update_order_status',
			_ajax_nonce: config.updateOrderStatusNonce,
			intent_id: intentId,
		} );
	}

	function confirmRedirectIfPresent() {
		var hash = window.location.hash || '';
		var prefix = '#wcpay-confirm-pi:';
		var clientSecret;
		var intentId;

		if ( hash.indexOf( prefix ) !== 0 || ! config.publishableKey || ! window.Stripe ) {
			return;
		}

		clientSecret = decodeURIComponent( hash.substring( prefix.length ) );
		intentId = clientSecret.split( '_secret_' )[ 0 ];
		stripe = stripe || window.Stripe( config.publishableKey, {
			locale: config.locale || 'auto',
			stripeAccount: config.accountId || undefined,
		} );

		stripe
			.confirmPayment( {
				clientSecret: clientSecret,
				confirmParams: {
					return_url: window.location.href.split( '#' )[ 0 ],
				},
				redirect: 'if_required',
			} )
			.then( function( result ) {
				if ( result.error ) {
					setError( result.error.message );
					return;
				}

				updateOrderStatusAfterConfirmation( intentId );
			} );
	}

	$( function() {
		initializeStripeElement();
		confirmRedirectIfPresent();
	} );

	$( document.body ).on( 'updated_checkout', initializeStripeElement );

	$( document.body ).on( 'checkout_place_order_' + gatewayId, function() {
		if ( ! isSelectedGateway() ) {
			return true;
		}

		if ( isSubmittingWithPaymentMethod ) {
			isSubmittingWithPaymentMethod = false;
			return true;
		}

		return createPaymentMethodAndSubmit();
	} );
} )( jQuery, window, document );
