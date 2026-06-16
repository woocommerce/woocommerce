/* global jQuery, wcpay_core_checkout_config */
( function ( $, window, document ) {
	'use strict';

	var config = window.wcpay_core_checkout_config || {};
	var gatewayId = config.gatewayId || 'woocommerce_payments';
	var stripe = null;
	var elements = null;
	var paymentElement = null;
	var paymentElementContainer = null;
	var isSubmittingWithPaymentMethod = false;

	function isSelectedGateway() {
		return (
			$( 'input[name="payment_method"]:checked' ).val() === gatewayId ||
			$( 'input[name="payment_method"]' ).filter(
				'[value="' + gatewayId + '"]'
			).length === 1
		);
	}

	function setError( message ) {
		var errorElement = document.getElementById(
			'wcpay-core-payment-errors'
		);
		if ( ! errorElement ) {
			return;
		}

		errorElement.textContent = message || '';
		errorElement.hidden = ! message;
	}

	function copyTestNumber( event ) {
		var button;
		var testNumber;

		if ( ! ( event.target instanceof window.Element ) ) {
			return;
		}

		button = event.target.closest( '.js-woopayments-copy-test-number' );
		testNumber = button && button.textContent ? button.textContent.trim() : '';

		if ( ! button || ! testNumber || ! window.navigator.clipboard ) {
			return;
		}

		window.navigator.clipboard.writeText( testNumber );
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
		var fingerprint =
			paymentMethod && paymentMethod.card
				? paymentMethod.card.fingerprint
				: '';

		ensureHiddenField(
			form,
			'wcpay-payment-method',
			paymentMethod && paymentMethod.id ? paymentMethod.id : ''
		);
		ensureHiddenField(
			form,
			'wcpay-payment-method-error-code',
			error && error.code ? error.code : ''
		);
		ensureHiddenField(
			form,
			'wcpay-payment-method-error-message',
			error && error.message ? error.message : ''
		);
		ensureHiddenField( form, 'wcpay-fingerprint', fingerprint || '' );
	}

	function getStripeElementsOptions() {
		var amount = Number( config.cartTotal || 0 );
		var options = {
			mode: amount > 0 && isFinite( amount ) ? 'payment' : 'setup',
			currency: ( config.currency || 'usd' ).toLowerCase(),
			paymentMethodCreation: 'manual',
			paymentMethodTypes: [ 'card' ],
		};

		if ( 'payment' === options.mode ) {
			options.amount = amount;
		}

		return options;
	}

	function initializeStripeElement() {
		var container = document.getElementById( 'wcpay-core-payment-element' );
		if (
			! container ||
			! config.isCoreNativeCheckoutAvailable ||
			! config.publishableKey ||
			! window.Stripe
		) {
			return;
		}

		if ( paymentElement ) {
			if ( paymentElementContainer !== container ) {
				if ( paymentElement.unmount ) {
					paymentElement.unmount();
				}
				paymentElement.mount( container );
				paymentElementContainer = container;
			}
			return;
		}

		stripe = window.Stripe( config.publishableKey, {
			locale: config.locale || 'auto',
			stripeAccount: config.accountId || undefined,
		} );
		elements = stripe.elements( getStripeElementsOptions() );
		paymentElement = elements.create( 'payment' );
		paymentElement.mount( container );
		paymentElementContainer = container;
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
			.then( function ( result ) {
				if ( result.error ) {
					appendPaymentFields( form, null, result.error );
					setError( result.error.message );
					$( document.body ).trigger( 'checkout_error', [
						result.error.message,
					] );
					return;
				}

				appendPaymentFields( form, result.paymentMethod, null );
				setError( '' );
				isSubmittingWithPaymentMethod = true;
				form.trigger( 'submit' );
			} );

		return false;
	}

	function parseConfirmationHash( hash ) {
		var match = ( hash || '' ).match(
			/^#wcpay-confirm-(pi|si):([^:]+):([^:]+):([^:]+)(?::(.+))?$/
		);
		var clientSecret;

		if ( ! match ) {
			return null;
		}

		clientSecret = decodeURIComponent( match[ 3 ] );

		return {
			type: match[ 1 ],
			orderId: decodeURIComponent( match[ 2 ] ),
			clientSecret: clientSecret,
			nonce: decodeURIComponent( match[ 4 ] ),
			confirmationToken: match[ 5 ]
				? decodeURIComponent( match[ 5 ] )
				: '',
			intentId: clientSecret.split( '_secret_' )[ 0 ],
		};
	}

	function updateOrderStatusAfterConfirmation( confirmation, intentId ) {
		if (
			! config.ajaxUrl ||
			! confirmation ||
			! confirmation.orderId ||
			! confirmation.nonce ||
			! intentId
		) {
			return $.Deferred().resolve().promise();
		}

		return $.post( config.ajaxUrl, {
			action: 'update_order_status',
			order_id: confirmation.orderId,
			_ajax_nonce: confirmation.nonce,
			intent_id: intentId,
			should_save_payment_method: 'false',
			is_changing_payment: 'false',
		} );
	}

	function confirmRedirectIfPresent() {
		var confirmation = parseConfirmationHash( window.location.hash || '' );
		var intentId;
		var confirmationPromise;

		if ( ! confirmation || ! config.publishableKey || ! window.Stripe ) {
			return;
		}

		stripe =
			stripe ||
			window.Stripe( config.publishableKey, {
				locale: config.locale || 'auto',
				stripeAccount: config.accountId || undefined,
			} );

		if ( confirmation.type === 'si' ) {
			if ( confirmation.confirmationToken && stripe.confirmSetup ) {
				confirmationPromise = stripe.confirmSetup( {
					clientSecret: confirmation.clientSecret,
					confirmParams: {
						confirmation_token: confirmation.confirmationToken,
					},
					redirect: 'if_required',
				} );
			} else if ( stripe.handleNextAction ) {
				confirmationPromise = stripe.handleNextAction( {
					clientSecret: confirmation.clientSecret,
				} );
			}
		} else if ( stripe.handleNextAction ) {
			confirmationPromise = stripe.handleNextAction( {
				clientSecret: confirmation.clientSecret,
			} );
		}

		if ( ! confirmationPromise ) {
			return;
		}

		confirmationPromise.then( function ( result ) {
			if ( result.error ) {
				setError( result.error.message );
				return;
			}

			intentId =
				( result.paymentIntent && result.paymentIntent.id ) ||
				( result.setupIntent && result.setupIntent.id ) ||
				confirmation.intentId;

			updateOrderStatusAfterConfirmation( confirmation, intentId )
				.done( function ( response ) {
					var resultResponse =
						typeof response === 'string'
							? JSON.parse( response )
							: response;

					if ( resultResponse.error && resultResponse.error.message ) {
						setError( resultResponse.error.message );
						return;
					}

					if ( resultResponse.return_url ) {
						window.location.href = resultResponse.return_url;
					}
				} )
				.fail( function () {
					setError( config.confirmationErrorMessage || '' );
				} );
		} );
	}

	$( function () {
		initializeStripeElement();
		confirmRedirectIfPresent();
		document.addEventListener( 'click', copyTestNumber );
	} );

	$( window ).on( 'hashchange', function () {
		if ( ( window.location.hash || '' ).indexOf( '#wcpay-confirm-' ) === 0 ) {
			confirmRedirectIfPresent();
		}
	} );

	$( document.body ).on( 'updated_checkout', initializeStripeElement );

	$( document.body ).on( 'checkout_place_order_' + gatewayId, function () {
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
