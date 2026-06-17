/* global jQuery, wcpayExpressCheckoutParams */
( function ( $, window, document ) {
	'use strict';

	var config = window.wcpayExpressCheckoutParams || {};
	var cachedCartData = null;
	var elements = null;
	var expressElement = null;

	function getApiFetch() {
		return window.wp && window.wp.apiFetch;
	}

	function getButtonContext() {
		return config.button_context || ( config.button && config.button.context ) || 'checkout';
	}

	function isPayForOrder() {
		return getButtonContext() === 'pay_for_order' && config.order_id;
	}

	function isBlockSurface() {
		return config.has_block && getButtonContext() !== 'pay_for_order';
	}

	function isPaymentRequestEnabled() {
		return (
			Array.isArray( config.enabled_methods ) &&
			config.enabled_methods.indexOf( 'payment_request' ) !== -1
		);
	}

	function isAmazonPayEnabled() {
		return (
			Array.isArray( config.enabled_methods ) &&
			config.enabled_methods.indexOf( 'amazon_pay' ) !== -1 &&
			getPaymentMethodTypes().indexOf( 'amazon_pay' ) !== -1
		);
	}

	function getPaymentMethodTypes() {
		var paymentMethodTypes = Array.isArray( config.payment_method_types )
			? config.payment_method_types
			: [ 'card' ];

		paymentMethodTypes = paymentMethodTypes.filter( function ( type ) {
			return [ 'card', 'amazon_pay' ].indexOf( type ) !== -1;
		} );

		return paymentMethodTypes.length ? paymentMethodTypes : [ 'card' ];
	}

	function getStoreApiHeaders( includeSessionNonce, includeTokenizedCartNonce ) {
		var nonce = config.nonce || {};
		var headers = {};

		if ( nonce.store_api_nonce ) {
			headers.Nonce = nonce.store_api_nonce;
		}

		if ( includeTokenizedCartNonce !== false && nonce.tokenized_cart_nonce ) {
			headers[ 'X-WooPayments-Tokenized-Cart-Nonce' ] =
				nonce.tokenized_cart_nonce;
		}

		if ( includeSessionNonce && nonce.tokenized_cart_session_nonce ) {
			headers[ 'X-WooPayments-Tokenized-Cart-Session-Nonce' ] =
				nonce.tokenized_cart_session_nonce;
		}

		return headers;
	}

	function addQueryArgs( path, args ) {
		var query = Object.keys( args )
			.filter( function ( key ) {
				return args[ key ] !== undefined && args[ key ] !== null && args[ key ] !== '';
			} )
			.map( function ( key ) {
				return (
					encodeURIComponent( key ) +
					'=' +
					encodeURIComponent( args[ key ] )
				);
			} )
			.join( '&' );

		return query ? path + '?' + query : path;
	}

	function requestCart( options ) {
		var apiFetch = getApiFetch();
		var includeSessionNonce = getButtonContext() === 'product';

		return apiFetch(
			Object.assign( {}, options, {
				headers: Object.assign(
					{},
					getStoreApiHeaders( includeSessionNonce, true ),
					options.headers || {}
				),
			} )
		);
	}

	function requestOrder( options ) {
		var apiFetch = getApiFetch();

		return apiFetch(
			Object.assign( {}, options, {
				headers: Object.assign(
					{},
					getStoreApiHeaders( false, false ),
					options.headers || {}
				),
			} )
		);
	}

	function getCart() {
		if ( isPayForOrder() ) {
			return requestOrder( {
				method: 'GET',
				path: addQueryArgs( '/wc/store/v1/order/' + config.order_id, {
					key: config.key,
					billing_email: config.billing_email,
				} ),
			} );
		}

		return requestCart( {
			method: 'GET',
			path: '/wc/store/v1/cart',
		} );
	}

	function getTotalAmount( cartData ) {
		var totals = cartData && cartData.totals ? cartData.totals : {};
		var total = parseInt( totals.total_price || 0, 10 );
		var refund = parseInt( totals.total_refund || 0, 10 );

		return Math.max( total - refund, 0 );
	}

	function getCurrency( cartData ) {
		return (
			( cartData &&
				cartData.totals &&
				cartData.totals.currency_code ) ||
			( config.checkout && config.checkout.currency_code ) ||
			'usd'
		).toLowerCase();
	}

	function clampButtonHeight( height ) {
		var parsedHeight = parseInt( height, 10 );

		if ( ! Number.isFinite( parsedHeight ) ) {
			return 48;
		}

		return Math.min( Math.max( parsedHeight, 40 ), 55 );
	}

	function getButtonTheme( method ) {
		var theme = ( config.button && config.button.theme ) || 'dark';

		if ( theme === 'light-outline' ) {
			return method === 'applePay' ? 'white-outline' : 'white';
		}

		if ( theme === 'light' ) {
			return 'white';
		}

		return 'black';
	}

	function getButtonType( method ) {
		var type = ( config.button && config.button.type ) || 'default';

		if ( type === 'default' ) {
			return 'plain';
		}

		if ( method === 'applePay' ) {
			return [ 'buy', 'donate', 'book', 'check-out' ].indexOf( type ) !==
				-1
				? type
				: 'plain';
		}

		return [ 'buy', 'donate', 'book', 'checkout' ].indexOf( type ) !== -1
			? type
			: 'plain';
	}

	function getButtonOptions() {
		return {
			buttonHeight: clampButtonHeight( config.button && config.button.height ),
			buttonTheme: {
				applePay: getButtonTheme( 'applePay' ),
				googlePay: getButtonTheme( 'googlePay' ),
			},
			buttonType: {
				applePay: getButtonType( 'applePay' ),
				googlePay: getButtonType( 'googlePay' ),
			},
			layout: {
				overflow: 'never',
			},
			paymentMethods: {
				applePay: isPaymentRequestEnabled() ? 'always' : 'never',
				googlePay: isPaymentRequestEnabled() ? 'always' : 'never',
				link: 'never',
				paypal: 'never',
				amazonPay: isAmazonPayEnabled() ? 'auto' : 'never',
				klarna: 'never',
			},
		};
	}

	function getStripeElementsOptions( cartData ) {
		var amount = getTotalAmount( cartData );
		var options = {
			mode: 'payment',
			amount: amount,
			currency: getCurrency( cartData ),
			loader: 'never',
			paymentMethodTypes: getPaymentMethodTypes(),
		};

		if ( config.is_manual_capture ) {
			options.captureMethod = 'manual';
		}

		return options;
	}

	function getStripe() {
		if (
			! window.Stripe ||
			! config.stripe ||
			! config.stripe.publishableKey
		) {
			return null;
		}

		return window.Stripe( config.stripe.publishableKey, {
			locale: config.stripe.locale || 'auto',
			stripeAccount: config.stripe.accountId,
		} );
	}

	function getTrackingNonce() {
		return (
			( config.nonce && config.nonce.platform_tracker ) ||
			config.platformTrackerNonce ||
			config.platform_tracker_nonce
		);
	}

	function recordUserEvent( eventName, eventProperties ) {
		var ajaxUrl = config.ajax_url || config.ajaxUrl;
		var nonce = getTrackingNonce();
		var body;

		if (
			! eventName ||
			config.isShopperTrackingEnabled === false ||
			config.is_shopper_tracking_enabled === false ||
			! ajaxUrl ||
			! nonce ||
			! window.fetch ||
			! window.FormData
		) {
			return;
		}

		body = new window.FormData();
		body.append( 'tracksNonce', nonce );
		body.append( 'action', 'platform_tracks' );
		body.append( 'tracksEventName', eventName );
		body.append( 'tracksEventProp', JSON.stringify( eventProperties || {} ) );

		window.fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		} ).catch( function () {} );
	}

	function showExpressButton() {
		var container = document.getElementById(
			'wcpay-express-checkout-element'
		);
		var separator = document.getElementById(
			'wcpay-express-checkout-button-separator'
		);

		if ( container ) {
			container.classList.add( 'is-ready' );
		}

		if ( separator ) {
			separator.hidden = false;
		}
	}

	function hideExpressButton() {
		var container = document.getElementById(
			'wcpay-express-checkout-element'
		);
		var separator = document.getElementById(
			'wcpay-express-checkout-button-separator'
		);

		if ( container ) {
			container.classList.remove( 'is-ready' );
		}

		if ( separator ) {
			separator.hidden = true;
		}
	}

	function setError( message ) {
		var notices = document.querySelector( '.woocommerce-notices-wrapper' );
		var error;

		if ( ! notices || ! message ) {
			return;
		}

		error = document.createElement( 'div' );
		error.className = 'woocommerce-error';
		error.textContent = message;
		notices.appendChild( error );
	}

	function splitName( name ) {
		var parts = ( name || '' ).trim().split( /\s+/ ).filter( Boolean );

		return {
			first_name: parts.shift() || '',
			last_name: parts.join( ' ' ),
		};
	}

	function normalizeAddress( address, fallback ) {
		address = address || {};
		fallback = fallback || {};

		return {
			first_name: address.first_name || fallback.first_name || '',
			last_name: address.last_name || fallback.last_name || '',
			company: address.company || fallback.company || '',
			address_1: address.address_1 || address.line1 || fallback.address_1 || '',
			address_2: address.address_2 || address.line2 || fallback.address_2 || '',
			city: address.city || fallback.city || '',
			state: address.state || fallback.state || '',
			postcode: address.postcode || address.postal_code || fallback.postcode || '',
			country: address.country || fallback.country || '',
			email: address.email || fallback.email || '',
			phone: address.phone || fallback.phone || '',
		};
	}

	function getBillingAddress( billingDetails ) {
		var nameParts = splitName( billingDetails && billingDetails.name );

		return normalizeAddress(
			Object.assign(
				{},
				nameParts,
				billingDetails && billingDetails.address,
				{
					email: billingDetails && billingDetails.email,
					phone: billingDetails && billingDetails.phone,
				}
			),
			cachedCartData && cachedCartData.billing_address
		);
	}

	function getPaymentData( confirmationTokenId ) {
		return [
			{
				key: 'wcpay-confirmation-token',
				value: confirmationTokenId,
			},
			{
				key: 'wcpay-is-platform-payment-method',
				value: 'true',
			},
			{
				key: 'wcpay-express-payment-method-types',
				value: JSON.stringify( getPaymentMethodTypes() ),
			},
			{
				key: 'wcpay-express-checkout-context',
				value: getButtonContext(),
			},
		];
	}

	function placeOrder( confirmationTokenId, event ) {
		if ( isPayForOrder() ) {
			return requestOrder( {
				method: 'POST',
				path: '/wc/store/v1/checkout/' + config.order_id,
				data: {
					key: config.key,
					billing_email: config.billing_email,
					payment_method: 'woocommerce_payments',
					billing_address:
						cachedCartData && cachedCartData.billing_address,
					shipping_address:
						cachedCartData && cachedCartData.shipping_address,
					payment_data: getPaymentData( confirmationTokenId ),
				},
			} );
		}

		return requestCart( {
			method: 'POST',
			path: '/wc/store/v1/checkout',
			headers: {
				'X-WooPayments-Tokenized-Cart': true,
			},
			data: {
				payment_method: 'woocommerce_payments',
				billing_address: getBillingAddress(
					event && event.billingDetails
				),
				shipping_address:
					cachedCartData && cachedCartData.shipping_address,
				payment_data: getPaymentData( confirmationTokenId ),
			},
		} );
	}

	function redirectToOrder( response ) {
		var redirectUrl =
			response &&
			response.payment_result &&
			response.payment_result.redirect_url;

		if ( redirectUrl ) {
			window.location.href = redirectUrl;
		}
	}

	function getClickOptions() {
		return {
			business: {
				name: config.store_name || '',
			},
			emailRequired: true,
			phoneNumberRequired: Boolean(
				config.checkout && config.checkout.needs_payer_phone
			),
			shippingAddressRequired: isPayForOrder()
				? false
				: Boolean(
						cachedCartData
							? cachedCartData.needs_shipping
							: config.checkout && config.checkout.needs_shipping
				  ),
			allowedShippingCountries:
				( config.checkout &&
					config.checkout.allowed_shipping_countries ) ||
				[],
		};
	}

	async function initExpressCheckout() {
		var stripe;
		var total;

		if (
			isBlockSurface() ||
			! ( isPaymentRequestEnabled() || isAmazonPayEnabled() ) ||
			! getApiFetch() ||
			! document.getElementById( 'wcpay-express-checkout-element' )
		) {
			return;
		}

		try {
			cachedCartData = await getCart();
		} catch ( error ) {
			hideExpressButton();
			return;
		}

		total = getTotalAmount( cachedCartData );
		if ( total <= 0 ) {
			hideExpressButton();
			return;
		}

		stripe = getStripe();
		if ( ! stripe ) {
			return;
		}

		if ( expressElement && expressElement.unmount ) {
			expressElement.unmount();
		}

		elements = stripe.elements( getStripeElementsOptions( cachedCartData ) );
		expressElement = elements.create( 'expressCheckout', getButtonOptions() );

		expressElement.on( 'ready', function ( event ) {
			if ( event && event.availablePaymentMethods ) {
				showExpressButton();
				recordUserEvent( 'applepay_button_load', {
					source: getButtonContext(),
				} );
				recordUserEvent( 'gpay_button_load', {
					source: getButtonContext(),
				} );
			}
		} );

		expressElement.on( 'click', function ( event ) {
			recordUserEvent( 'applepay_button_click', {
				source: getButtonContext(),
			} );
			recordUserEvent( 'gpay_button_click', {
				source: getButtonContext(),
			} );

			event.resolve( getClickOptions() );
		} );

		expressElement.on( 'confirm', async function ( event ) {
			var submitResult;
			var confirmationResult;
			var response;

			try {
				submitResult = await elements.submit();
				if ( submitResult && submitResult.error ) {
					throw new Error( submitResult.error.message );
				}

				confirmationResult = await stripe.createConfirmationToken( {
					elements: elements,
				} );

				if ( confirmationResult && confirmationResult.error ) {
					throw new Error( confirmationResult.error.message );
				}

				response = await placeOrder(
					confirmationResult.confirmationToken.id,
					event
				);
				redirectToOrder( response );
			} catch ( error ) {
				setError(
					( error && error.message ) ||
						'Unable to process this payment, please try again.'
				);
			}
		} );

		expressElement.on( 'cancel', function () {
			hideExpressButton();
		} );

		expressElement.mount( '#wcpay-express-checkout-element' );
	}

	$( function () {
		if ( isBlockSurface() ) {
			return;
		}

		hideExpressButton();

		if (
			getButtonContext() !== 'checkout' ||
			getButtonContext() === 'pay_for_order'
		) {
			initExpressCheckout();
		}

		$( document.body ).on( 'updated_checkout', function () {
			cachedCartData = null;
			return initExpressCheckout();
		} );

		$( document.body ).on( 'updated_cart_totals', function () {
			cachedCartData = null;
			return initExpressCheckout();
		} );
	} );
} )( jQuery, window, document );
