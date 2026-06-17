/* global jQuery, wcpayExpressCheckoutParams */
( function ( $, window, document ) {
	'use strict';

	var config = window.wcpayExpressCheckoutParams || {};
	var cachedCartData = null;
	var elements = null;
	var expressElement = null;
	var tokenizedCartSession = null;
	var productAddToCartPromise = Promise.resolve();
	var productAddToCartErrorMessage = '';

	function getApiFetch() {
		return window.wp && window.wp.apiFetch;
	}

	function getButtonContext() {
		return config.button_context || ( config.button && config.button.context ) || 'checkout';
	}

	function isProduct() {
		return getButtonContext() === 'product';
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

		if ( isProduct() && tokenizedCartSession !== null ) {
			headers[ 'X-WooPayments-Tokenized-Cart-Session' ] =
				tokenizedCartSession;
		}

		return headers;
	}

	function normalizeStoreApiResponse( response ) {
		var nextNonce;
		var nextSession;

		if (
			! isProduct() ||
			! response ||
			! response.headers ||
			typeof response.headers.get !== 'function'
		) {
			return response;
		}

		nextNonce = response.headers.get( 'Nonce' );
		if ( nextNonce ) {
			config.nonce = config.nonce || {};
			config.nonce.store_api_nonce = nextNonce;
		}

		nextSession = response.headers.get(
			'X-WooPayments-Tokenized-Cart-Session'
		);
		if ( nextSession !== null && nextSession !== undefined ) {
			tokenizedCartSession = nextSession;
		}

		if ( typeof response.json === 'function' ) {
			return response.json();
		}

		return response;
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
		var includeSessionNonce = isProduct();
		var requestOptions = Object.assign( {}, options, {
			headers: Object.assign(
				{},
				getStoreApiHeaders( includeSessionNonce, true ),
				options.headers || {}
			),
		} );

		if ( isProduct() ) {
			requestOptions.parse = false;
		}

		return apiFetch( requestOptions ).then( normalizeStoreApiResponse );
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
		if (
			cartData &&
			cartData.total &&
			cartData.total.amount !== undefined
		) {
			return Math.max( parseInt( cartData.total.amount || 0, 10 ), 0 );
		}

		var totals = cartData && cartData.totals ? cartData.totals : {};
		var total = parseInt( totals.total_price || 0, 10 );
		var refund = parseInt( totals.total_refund || 0, 10 );

		return Math.max( total - refund, 0 );
	}

	function getCurrency( cartData ) {
		return (
			( cartData && cartData.currency ) ||
			( cartData && cartData.total && cartData.total.currency ) ||
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

	function getFieldValue( form, selector ) {
		var value = '';

		form.querySelectorAll( selector ).forEach( function ( field ) {
			if ( ! value && field.value ) {
				value = field.value;
			}
		} );

		return value;
	}

	function getSelectedProduct() {
		var form = document.querySelector( 'form.cart' );
		var quantityField;
		var variationId;
		var variation = [];
		var id;
		var quantity;

		if ( ! form ) {
			return null;
		}

		quantityField = form.querySelector( 'input[name="quantity"]' );
		variationId = parseInt(
			getFieldValue( form, 'input[name="variation_id"]' ) || 0,
			10
		);
		id = parseInt(
			getFieldValue(
				form,
				'button[name="add-to-cart"], input[name="add-to-cart"]'
			) || 0,
			10
		);
		quantity = parseFloat( ( quantityField && quantityField.value ) || 1 );

		if ( ! id ) {
			return null;
		}

		form.querySelectorAll(
			'select[name^="attribute_"], input[name^="attribute_"]'
		).forEach( function ( field ) {
			if ( field.name && field.value ) {
				variation.push( {
					attribute: field.name,
					value: field.value,
				} );
			}
		} );

		return {
			id: variationId || id,
			quantity: quantity > 0 ? quantity : 1,
			variation: variation,
		};
	}

	function getAddressLine( address, index ) {
		if ( address && Array.isArray( address.addressLine ) ) {
			return address.addressLine[ index ] || '';
		}

		if ( index === 0 ) {
			return address.line1 || address.line_1 || address.address_1 || '';
		}

		return address.line2 || address.line_2 || address.address_2 || '';
	}

	function transformShippingAddress( name, address ) {
		var split;
		address = address || {};
		split = splitName( name || address.recipient || address.name || '' );

		return normalizeAddress(
			{
				first_name: split.first_name,
				last_name: split.last_name,
				company: '',
				address_1: getAddressLine( address, 0 ),
				address_2: getAddressLine( address, 1 ),
				city: address.city || address.locality || '',
				state: address.state || address.region || '',
				postcode: address.postal_code || address.postcode || '',
				country: address.country || '',
			},
			cachedCartData && cachedCartData.shipping_address
		);
	}

	function getShippingRates( cartData ) {
		var rates =
			cartData &&
			cartData.shipping_rates &&
			cartData.shipping_rates[ 0 ] &&
			Array.isArray( cartData.shipping_rates[ 0 ].shipping_rates )
				? cartData.shipping_rates[ 0 ].shipping_rates
				: [];

		return rates.map( function ( rate ) {
			return {
				id: rate.rate_id,
				displayName: rate.name,
				amount:
					parseInt( rate.price || 0, 10 ) +
					parseInt( rate.taxes || 0, 10 ),
			};
		} );
	}

	function getLineItems( cartData ) {
		var items =
			cartData && Array.isArray( cartData.items ) ? cartData.items : [];

		return items.map( function ( item ) {
			var totals = item.totals || {};

			return {
				name: item.name,
				amount:
					parseInt( totals.line_subtotal || 0, 10 ) +
					parseInt( totals.line_subtotal_tax || 0, 10 ),
			};
		} );
	}

	function updateElementsForCart( cartData ) {
		var amount = getTotalAmount( cartData );

		if ( elements && typeof elements.update === 'function' && amount > 0 ) {
			return elements.update( { amount: amount } );
		}

		return Promise.resolve();
	}

	function filterSelectedProduct( product ) {
		if (
			window.wp &&
			window.wp.hooks &&
			typeof window.wp.hooks.applyFilters === 'function'
		) {
			return window.wp.hooks.applyFilters(
				'wcpay.express-checkout.cart-add-item',
				product
			);
		}

		return product;
	}

	function filterShippingPackageId( packageId, cartData, rateId ) {
		if (
			window.wp &&
			window.wp.hooks &&
			typeof window.wp.hooks.applyFilters === 'function'
		) {
			return window.wp.hooks.applyFilters(
				'wcpay.express-checkout.shipping-package-id',
				packageId,
				cartData,
				rateId
			);
		}

		return packageId;
	}

	function addSelectedProductToCart( product ) {
		tokenizedCartSession = '';

		return requestCart( {
			method: 'POST',
			path: '/wc/store/v1/cart/add-item',
			data: product,
		} )
			.then( function ( cartData ) {
				cachedCartData = cartData;

				return updateElementsForCart( cartData ).then( function () {
					return cartData;
				} );
			} )
			.catch( function ( error ) {
				return emptyProductCart().then( function () {
					throw error;
				} );
			} );
	}

	function startProductCartRequest() {
		var product = filterSelectedProduct( getSelectedProduct() );

		productAddToCartErrorMessage = '';

		if ( ! product ) {
			productAddToCartErrorMessage = 'Unable to add this product to the cart.';
			return false;
		}

		productAddToCartPromise = addSelectedProductToCart( product ).catch(
			function ( error ) {
				productAddToCartErrorMessage =
					( error && error.message ) ||
					'Unable to add this product to the cart.';
				setError( productAddToCartErrorMessage );
				throw error;
			}
		);
		productAddToCartPromise.catch( function () {} );

		return true;
	}

	function getPendingShippingRate() {
		return {
			id: 'pending',
			displayName: 'Pending',
			amount: 0,
		};
	}

	function updateProductShippingAddress( event ) {
		return productAddToCartPromise
			.then( function () {
				return requestCart( {
					method: 'POST',
					path: '/wc/store/v1/cart/update-customer',
					headers: {
						'X-WooPayments-Tokenized-Cart': true,
					},
					data: {
						shipping_address: transformShippingAddress(
							event.name,
							event.address
						),
					},
				} );
			} )
			.then( function ( cartData ) {
				var shippingRates = getShippingRates( cartData );

				if ( ! shippingRates.length ) {
					event.reject();
					return;
				}

				cachedCartData = cartData;

				return updateElementsForCart( cartData )
					.then( function () {
						event.resolve( {
							shippingRates: shippingRates,
							lineItems: getLineItems( cartData ),
						} );
					} )
					.catch( function () {
						event.reject();
						return emptyProductCart();
					} );
			} )
			.catch( function () {
				event.resolve();
			} );
	}

	function selectProductShippingRate( event ) {
		var rateId = event && event.shippingRate ? event.shippingRate.id : '';

		return productAddToCartPromise
			.then( function () {
				return requestCart( {
					method: 'POST',
					path: '/wc/store/v1/cart/select-shipping-rate',
					data: {
						package_id: filterShippingPackageId(
							0,
							cachedCartData,
							rateId
						),
						rate_id: rateId,
					},
				} );
			} )
			.then( function ( cartData ) {
				cachedCartData = cartData;

				return updateElementsForCart( cartData )
					.then( function () {
						event.resolve( {
							lineItems: getLineItems( cartData ),
						} );
					} )
					.catch( function () {
						event.reject();
						return emptyProductCart();
					} );
			} )
			.catch( function () {
				event.reject();
			} );
	}

	function emptyProductCart() {
		if ( ! isProduct() || tokenizedCartSession === null ) {
			return Promise.resolve();
		}

		return requestCart( {
			method: 'GET',
			path: '/wc/store/v1/cart',
			headers: {
				'X-WooPayments-Tokenized-Cart-Is-Ephemeral-Cart': '1',
			},
		} )
			.then( function () {
				tokenizedCartSession = null;
				cachedCartData = null;
			} )
			.catch( function () {
				tokenizedCartSession = null;
				cachedCartData = null;
			} );
	}

	function getClickOptions() {
		var shippingAddressRequired = isPayForOrder()
			? false
			: Boolean(
					cachedCartData
						? cachedCartData.needs_shipping
						: config.checkout && config.checkout.needs_shipping
			  );
		var shippingRates =
			cachedCartData && cachedCartData.needs_shipping
				? getShippingRates( cachedCartData )
				: undefined;

		if (
			isProduct() &&
			shippingAddressRequired &&
			( ! shippingRates || ! shippingRates.length )
		) {
			shippingRates = [ getPendingShippingRate() ];
		}

		return {
			business: {
				name: config.store_name || '',
			},
			emailRequired: true,
			phoneNumberRequired: Boolean(
				config.checkout && config.checkout.needs_payer_phone
			),
			shippingAddressRequired: shippingAddressRequired,
			allowedShippingCountries:
				( config.checkout &&
					config.checkout.allowed_shipping_countries ) ||
				[],
			shippingRates: shippingRates,
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
			cachedCartData = isProduct() ? config.product : await getCart();
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

		expressElement.on( 'click', async function ( event ) {
			recordUserEvent( 'applepay_button_click', {
				source: getButtonContext(),
			} );
			recordUserEvent( 'gpay_button_click', {
				source: getButtonContext(),
			} );

			try {
				if ( isProduct() ) {
					if ( ! startProductCartRequest() ) {
						throw new Error( productAddToCartErrorMessage );
					}
				}

				event.resolve( getClickOptions() );
			} catch ( error ) {
				setError(
					( error && error.message ) ||
						'Unable to process this payment, please try again.'
				);
				await emptyProductCart();
			}
		} );

		expressElement.on( 'shippingaddresschange', async function ( event ) {
			if ( ! isProduct() ) {
				return;
			}

			await updateProductShippingAddress( event );
		} );

		expressElement.on( 'shippingratechange', async function ( event ) {
			if ( ! isProduct() ) {
				return;
			}

			await selectProductShippingRate( event );
		} );

		expressElement.on( 'confirm', async function ( event ) {
			var submitResult;
			var confirmationResult;
			var response;

			try {
				if ( isProduct() ) {
					await productAddToCartPromise;
					if ( productAddToCartErrorMessage ) {
						throw new Error( productAddToCartErrorMessage );
					}
				}

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
				await emptyProductCart();
			}
		} );

		expressElement.on( 'cancel', function () {
			emptyProductCart();
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
