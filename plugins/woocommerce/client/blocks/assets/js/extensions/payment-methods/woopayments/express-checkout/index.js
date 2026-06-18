/**
 * External dependencies
 */
import { registerExpressPaymentMethod } from '@woocommerce/blocks-registry';
import { getPaymentMethodData } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { recordWooPaymentsUserEvent } from '../tracks';

const PAYMENT_METHOD_NAME = 'woocommerce_payments';
const EXPRESS_CHECKOUT_PAYMENT_METHOD_NAME =
	'woocommerce_payments_express_checkout';
const settings = getPaymentMethodData( PAYMENT_METHOD_NAME, {} );
const params = settings?.expressCheckoutParams || {};

const METHOD_CONFIG = {
	applePay: {
		name: `${ EXPRESS_CHECKOUT_PAYMENT_METHOD_NAME }_applePay`,
		title: __( 'WooPayments - Apple Pay', 'woocommerce' ),
		enabledMethod: 'payment_request',
		loadEvent: 'applepay_button_load',
		clickEvent: 'applepay_button_click',
	},
	googlePay: {
		name: `${ EXPRESS_CHECKOUT_PAYMENT_METHOD_NAME }_googlePay`,
		title: __( 'WooPayments - Google Pay', 'woocommerce' ),
		enabledMethod: 'payment_request',
		loadEvent: 'gpay_button_load',
		clickEvent: 'gpay_button_click',
	},
	amazonPay: {
		name: `${ EXPRESS_CHECKOUT_PAYMENT_METHOD_NAME }_amazonPay`,
		title: __( 'WooPayments - Amazon Pay', 'woocommerce' ),
		enabledMethod: 'amazon_pay',
	},
};

const availabilityCache = new Map();
let availabilityStripe = null;

const getTrackingSettings = () => ( {
	...settings,
	ajaxUrl: params.ajax_url,
	isShopperTrackingEnabled: params.isShopperTrackingEnabled,
	is_shopper_tracking_enabled: params.is_shopper_tracking_enabled,
	platformTrackerNonce: params?.nonce?.platform_tracker,
} );

const clampButtonHeight = ( height ) => {
	const parsedHeight = Number.parseInt( height, 10 );

	if ( ! Number.isFinite( parsedHeight ) ) {
		return 48;
	}

	return Math.min( Math.max( parsedHeight, 40 ), 55 );
};

const getExpressButtonType = ( method ) => {
	const type = params?.button?.type || 'default';

	if ( type === 'default' ) {
		return 'plain';
	}

	if ( method === 'applePay' ) {
		return [ 'buy', 'donate', 'book', 'check-out' ].includes( type )
			? type
			: 'plain';
	}

	return [ 'buy', 'donate', 'book', 'checkout' ].includes( type )
		? type
		: 'plain';
};

const getExpressButtonTheme = ( method ) => {
	const theme = params?.button?.theme || 'dark';

	if ( theme === 'light-outline' ) {
		return method === 'applePay' ? 'white-outline' : 'white';
	}

	return theme === 'light' ? 'white' : 'black';
};

const getExpressButtonOptions = ( method ) => ( {
	buttonHeight: clampButtonHeight( params?.button?.height ),
	buttonTheme: {
		applePay: getExpressButtonTheme( 'applePay' ),
		googlePay: getExpressButtonTheme( 'googlePay' ),
	},
	buttonType: {
		applePay: getExpressButtonType( 'applePay' ),
		googlePay: getExpressButtonType( 'googlePay' ),
	},
	layout: {
		overflow: 'never',
	},
	paymentMethods: {
		applePay: method === 'applePay' ? 'always' : 'never',
		googlePay: method === 'googlePay' ? 'always' : 'never',
		amazonPay: method === 'amazonPay' ? 'auto' : 'never',
		link: 'never',
		paypal: 'never',
		klarna: 'never',
	},
} );

const getPaymentMethodTypes = () => {
	const paymentMethodTypes = Array.isArray( params?.payment_method_types )
		? params.payment_method_types
		: [ 'card' ];
	const filteredTypes = paymentMethodTypes.filter( ( type ) =>
		[ 'card', 'amazon_pay' ].includes( type )
	);

	return filteredTypes.length ? filteredTypes : [ 'card' ];
};

const getStoreApiHeaders = ( includeSessionNonce = false ) => {
	const nonce = params?.nonce || {};
	const headers = {};

	if ( nonce.store_api_nonce ) {
		headers.Nonce = nonce.store_api_nonce;
	}

	if ( nonce.tokenized_cart_nonce ) {
		headers[ 'X-WooPayments-Tokenized-Cart-Nonce' ] =
			nonce.tokenized_cart_nonce;
	}

	if ( includeSessionNonce && nonce.tokenized_cart_session_nonce ) {
		headers[ 'X-WooPayments-Tokenized-Cart-Session-Nonce' ] =
			nonce.tokenized_cart_session_nonce;
	}

	return headers;
};

const getCartTotal = ( billing ) => {
	const cartTotal = Number( billing?.cartTotal?.value );

	if ( Number.isFinite( cartTotal ) && cartTotal > 0 ) {
		return cartTotal;
	}

	return Number( params?.checkout?.cart_total || 0 );
};

const getCartCurrency = ( billing ) =>
	(
		billing?.currency?.code ||
		params?.checkout?.currency_code ||
		'usd'
	).toLowerCase();

const getCartTotalsCurrency = ( cart ) =>
	(
		cart?.cartTotals?.currency_code ||
		params?.checkout?.currency_code ||
		''
	).toLowerCase();

const getCartTotalsAmount = ( cart ) => {
	const totalPrice = Number.parseInt( cart?.cartTotals?.total_price, 10 );

	if ( Number.isFinite( totalPrice ) && totalPrice > 0 ) {
		return totalPrice;
	}

	return Number( params?.checkout?.cart_total || 0 );
};

const getStripeElementsOptions = ( billing ) => {
	const amount = getCartTotal( billing );
	const options = {
		mode: amount > 0 ? 'payment' : 'setup',
		loader: 'never',
		currency: getCartCurrency( billing ),
		paymentMethodTypes: getPaymentMethodTypes(),
	};

	if ( options.mode === 'payment' ) {
		options.amount = amount;
	}

	return options;
};

const getAvailabilityElementsOptions = ( cart ) => {
	const amount = getCartTotalsAmount( cart );
	const options = {
		mode: amount > 0 ? 'payment' : 'setup',
		currency: getCartTotalsCurrency( cart ),
		paymentMethodTypes: getPaymentMethodTypes(),
	};

	if ( options.mode === 'payment' ) {
		options.amount = Math.max( amount, 1 );
	}

	if ( params?.is_manual_capture ) {
		options.captureMethod = 'manual';
	}

	if ( params?.has_subscription ) {
		options.setupFutureUsage = 'off_session';
	}

	return options;
};

const getStripe = () => {
	if ( availabilityStripe ) {
		return availabilityStripe;
	}

	if ( ! window.Stripe || ! params?.stripe?.publishableKey ) {
		return null;
	}

	availabilityStripe = window.Stripe( params.stripe.publishableKey, {
		locale: params.stripe.locale || 'auto',
		...( params.stripe.accountId
			? { stripeAccount: params.stripe.accountId }
			: {} ),
	} );

	return availabilityStripe;
};

const checkAvailablePaymentMethods = ( cart ) => {
	const stripe = getStripe();

	if ( ! stripe ) {
		return Promise.resolve( {} );
	}

	const options = getAvailabilityElementsOptions( cart );
	if ( ! options.currency ) {
		return Promise.resolve( {} );
	}

	const cacheKey = [
		options.mode,
		options.amount || 0,
		options.currency,
		getPaymentMethodTypes().join( ',' ),
		params?.is_manual_capture ? 'manual' : 'automatic',
		params?.has_subscription ? 'subscription' : 'standard',
	].join( ':' );

	if ( availabilityCache.has( cacheKey ) ) {
		return availabilityCache.get( cacheKey );
	}

	const availabilityPromise = new Promise( ( resolve ) => {
		let container;
		let expressElement;
		let timeoutId;

		const cleanup = () => {
			window.clearTimeout( timeoutId );
			expressElement?.unmount?.();
			container?.remove?.();
		};

		try {
			container = document.createElement( 'div' );
			container.style.position = 'absolute';
			container.style.left = '-9999px';
			container.style.top = '-9999px';
			document.body.appendChild( container );

			const elements = stripe.elements( options );
			expressElement = elements.create( 'expressCheckout', {
				buttonType: {
					applePay: 'plain',
					googlePay: 'plain',
				},
				paymentMethods: {
					applePay: 'always',
					googlePay: 'always',
					amazonPay: getPaymentMethodTypes().includes( 'amazon_pay' )
						? 'auto'
						: 'never',
					link: 'never',
					paypal: 'never',
					klarna: 'never',
				},
			} );

			timeoutId = window.setTimeout( () => {
				cleanup();
				resolve( {} );
			}, 5000 );

			expressElement.on( 'ready', ( event ) => {
				cleanup();
				resolve( event?.availablePaymentMethods || {} );
			} );
			expressElement.on( 'loaderror', () => {
				cleanup();
				resolve( {} );
			} );
			expressElement.mount( container );
		} catch {
			cleanup();
			resolve( {} );
		}
	} );

	availabilityCache.set( cacheKey, availabilityPromise );

	return availabilityPromise;
};

const checkPaymentMethodIsAvailable = async ( method, cart ) => {
	const availablePaymentMethods = await checkAvailablePaymentMethods( cart );

	return Boolean( availablePaymentMethods?.[ method ] );
};

const splitName = ( name = '' ) => {
	const parts = name.trim().split( /\s+/ ).filter( Boolean );

	return {
		first_name: parts.shift() || '',
		last_name: parts.join( ' ' ),
	};
};

const normalizeAddress = ( address = {}, fallback = {} ) => ( {
	first_name: address.first_name || fallback.first_name || '',
	last_name: address.last_name || fallback.last_name || '',
	company: address.company || fallback.company || '',
	address_1: address.address_1 || address.line1 || fallback.address_1 || '',
	address_2: address.address_2 || address.line2 || fallback.address_2 || '',
	city: address.city || fallback.city || '',
	state: address.state || fallback.state || '',
	postcode:
		address.postcode || address.postal_code || fallback.postcode || '',
	country: address.country || fallback.country || '',
	email: address.email || fallback.email || '',
	phone: address.phone || fallback.phone || '',
} );

const getBillingAddress = ( eventBillingDetails, billing ) => {
	const nameParts = splitName( eventBillingDetails?.name || '' );

	return normalizeAddress(
		{
			...nameParts,
			email: eventBillingDetails?.email || '',
			phone: eventBillingDetails?.phone || '',
			...( eventBillingDetails?.address || {} ),
		},
		billing?.billingAddress || {}
	);
};

const getPaymentData = ( confirmationTokenId ) => [
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
		value: params.button_context || 'checkout',
	},
];

const redirectToOrder = ( response ) => {
	const redirectUrl = response?.payment_result?.redirect_url;

	if ( redirectUrl ) {
		window.location.href = redirectUrl;
	}
};

const ExpressCheckoutContent = ( {
	method,
	billing,
	shippingData,
	onClick,
	onClose,
	setExpressPaymentError,
} ) => {
	const containerRef = useRef( null );
	const stripeRef = useRef( null );
	const elementsRef = useRef( null );
	const expressElementRef = useRef( null );
	const methodConfig = METHOD_CONFIG[ method ];

	useEffect( () => {
		if (
			expressElementRef.current ||
			! containerRef.current ||
			! window.Stripe ||
			! params?.stripe?.publishableKey
		) {
			return;
		}

		stripeRef.current = window.Stripe( params.stripe.publishableKey, {
			locale: params.stripe.locale || 'auto',
			...( params.stripe.accountId
				? { stripeAccount: params.stripe.accountId }
				: {} ),
		} );
		elementsRef.current = stripeRef.current.elements(
			getStripeElementsOptions( billing )
		);
		expressElementRef.current = elementsRef.current.create(
			'expressCheckout',
			getExpressButtonOptions( method )
		);

		expressElementRef.current.on( 'ready', ( event ) => {
			if (
				event?.availablePaymentMethods?.[ method ] &&
				methodConfig.loadEvent
			) {
				recordWooPaymentsUserEvent(
					getTrackingSettings(),
					methodConfig.loadEvent,
					{ source: params.button_context || 'checkout' }
				);
			}
		} );

		expressElementRef.current.on( 'click', ( event ) => {
			onClick?.();
			if ( methodConfig.clickEvent ) {
				recordWooPaymentsUserEvent(
					getTrackingSettings(),
					methodConfig.clickEvent,
					{ source: params.button_context || 'checkout' }
				);
			}

			event.resolve( {
				business: {
					name: params.store_name || '',
				},
				emailRequired: true,
				phoneNumberRequired: Boolean(
					params?.checkout?.needs_payer_phone
				),
				shippingAddressRequired: Boolean(
					shippingData?.needsShipping ||
						params?.checkout?.needs_shipping
				),
				allowedShippingCountries:
					params?.checkout?.allowed_shipping_countries || [],
			} );
		} );

		expressElementRef.current.on( 'cancel', () => {
			onClose?.();
		} );

		expressElementRef.current.on( 'confirm', async ( event ) => {
			try {
				const submitResult = await elementsRef.current.submit();

				if ( submitResult?.error ) {
					throw new Error( submitResult.error.message );
				}

				const confirmationResult =
					await stripeRef.current.createConfirmationToken( {
						elements: elementsRef.current,
					} );

				if ( confirmationResult?.error ) {
					throw new Error( confirmationResult.error.message );
				}

				const response = await apiFetch( {
					method: 'POST',
					path: '/wc/store/v1/checkout',
					headers: {
						...getStoreApiHeaders(
							params.button_context === 'product'
						),
						'X-WooPayments-Tokenized-Cart': true,
					},
					data: {
						payment_method: PAYMENT_METHOD_NAME,
						billing_address: getBillingAddress(
							event?.billingDetails,
							billing
						),
						shipping_address:
							shippingData?.shippingAddress || undefined,
						payment_data: getPaymentData(
							confirmationResult.confirmationToken.id
						),
					},
				} );

				redirectToOrder( response );
			} catch ( error ) {
				setExpressPaymentError?.(
					error?.message ||
						__(
							'Unable to process this payment, please try again.',
							'woocommerce'
						)
				);
			}
		} );

		expressElementRef.current.mount( containerRef.current );
	}, [
		billing,
		method,
		methodConfig.clickEvent,
		methodConfig.loadEvent,
		onClick,
		onClose,
		setExpressPaymentError,
		shippingData,
	] );

	return (
		<div className="wcpay-core-express-checkout">
			<div
				ref={ containerRef }
				className="wcpay-core-express-checkout__element"
			/>
		</div>
	);
};

const getExpressPaymentMethod = ( method ) => {
	const methodConfig = METHOD_CONFIG[ method ];

	return {
		name: methodConfig.name,
		title: methodConfig.title,
		description: methodConfig.title,
		gatewayId: PAYMENT_METHOD_NAME,
		paymentMethodId: EXPRESS_CHECKOUT_PAYMENT_METHOD_NAME,
		content: <ExpressCheckoutContent method={ method } />,
		edit: <ExpressCheckoutContent method={ method } />,
		canMakePayment: ( { cart } ) => {
			if (
				! Array.isArray( params.enabled_methods ) ||
				! params.enabled_methods.includes( methodConfig.enabledMethod )
			) {
				return false;
			}

			return checkPaymentMethodIsAvailable( method, cart );
		},
		supports: {
			features: settings?.supports || [ 'products' ],
			style: [ 'height', 'borderRadius' ],
		},
	};
};

const registerWooPaymentsExpressCheckout = () => {
	availabilityCache.clear();
	availabilityStripe = null;

	if (
		Array.isArray( params.enabled_methods ) &&
		params.enabled_methods.includes( 'payment_request' )
	) {
		registerExpressPaymentMethod( getExpressPaymentMethod( 'applePay' ) );
		registerExpressPaymentMethod( getExpressPaymentMethod( 'googlePay' ) );
	}

	if (
		Array.isArray( params.enabled_methods ) &&
		params.enabled_methods.includes( 'amazon_pay' ) &&
		getPaymentMethodTypes().includes( 'amazon_pay' )
	) {
		registerExpressPaymentMethod( getExpressPaymentMethod( 'amazonPay' ) );
	}
};

registerWooPaymentsExpressCheckout();

export default registerWooPaymentsExpressCheckout;
