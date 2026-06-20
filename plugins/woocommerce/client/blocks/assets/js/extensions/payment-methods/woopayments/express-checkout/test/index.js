/**
 * External dependencies
 */
import { act, render, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { registerExpressPaymentMethod } from '@woocommerce/blocks-registry';
import apiFetch from '@wordpress/api-fetch';
import { addFilter, removeAllFilters } from '@wordpress/hooks';

jest.mock( '@woocommerce/blocks-registry', () => ( {
	registerExpressPaymentMethod: jest.fn(),
} ) );

const mockInvalidateResolutionForStore = jest.fn();
jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn( () => ( {
		invalidateResolutionForStore: mockInvalidateResolutionForStore,
	} ) ),
} ) );

jest.mock( '@woocommerce/block-data', () => ( {
	cartStore: 'wc/store/cart',
} ) );

const mockGetPaymentMethodData = jest.fn();
jest.mock( '@woocommerce/settings', () => ( {
	getPaymentMethodData: ( ...args ) => mockGetPaymentMethodData( ...args ),
} ) );

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const baseExpressCheckoutParams = {
	ajax_url: 'https://example.test/admin-ajax.php',
	enabled_methods: [ 'payment_request' ],
	button_context: 'checkout',
	store_name: 'Test Store',
	is_manual_capture: false,
	nonce: {
		store_api_nonce: 'store-api-nonce',
		tokenized_cart_nonce: 'cart-nonce',
		tokenized_cart_session_nonce: 'cart-session-nonce',
		platform_tracker: 'tracks-nonce',
	},
	checkout: {
		currency_code: 'usd',
		currency_decimals: 2,
		stripe_minor_unit: 2,
		country_code: 'US',
		needs_payer_phone: true,
		allowed_shipping_countries: [ 'US' ],
		display_prices_with_tax: false,
	},
	button: {
		type: 'buy',
		theme: 'dark',
		height: '48',
		radius: '4',
		size: 'medium',
	},
	stripe: {
		publishableKey: 'pk_test_123',
		accountId: 'acct_123',
		locale: 'en-us',
	},
	flags: {
		isEceUsingConfirmationTokens: true,
	},
	payment_method_types: [ 'card' ],
};

const billing = {
	billingAddress: {
		email: 'shopper@example.test',
		first_name: 'Ada',
		last_name: 'Lovelace',
		phone: '+15555550123',
		address_1: '1 Test Street',
		address_2: '',
		city: 'San Francisco',
		state: 'CA',
		postcode: '94107',
		country: 'US',
	},
	cartTotal: {
		value: 5000,
	},
	cartTotalItems: [
		{
			key: 'total_items',
			value: 4000,
			valueWithTax: 4000,
		},
		{
			key: 'total_fees',
			value: 0,
			valueWithTax: 0,
		},
		{
			key: 'total_discount',
			value: 0,
			valueWithTax: 0,
		},
		{
			key: 'total_tax',
			value: 300,
			valueWithTax: 300,
		},
		{
			key: 'total_shipping',
			value: 700,
			valueWithTax: 700,
		},
	],
	currency: {
		code: 'USD',
		minorUnit: 2,
	},
};

const blocksCart = {
	cartTotals: {
		total_price: '5000',
		total_refund: '0',
		total_shipping: '700',
		total_shipping_tax: '0',
		total_discount: '0',
		total_discount_tax: '0',
		total_fees: '0',
		total_fees_tax: '0',
		total_tax: '300',
		currency_code: 'USD',
		currency_minor_unit: 2,
	},
	totals: {
		total_price: '5000',
		total_refund: '0',
		total_shipping: '700',
		total_shipping_tax: '0',
		total_discount: '0',
		total_discount_tax: '0',
		total_fees: '0',
		total_fees_tax: '0',
		total_tax: '300',
		currency_code: 'USD',
		currency_minor_unit: 2,
	},
	cartItems: [
		{
			name: 'Beanie',
			quantity: 2,
			totals: {
				line_subtotal: '2000',
				line_subtotal_tax: '0',
				currency_minor_unit: 2,
			},
			prices: {
				price: '1000',
				currency_minor_unit: 2,
			},
		},
	],
	items: [
		{
			name: 'Beanie',
			quantity: 2,
			totals: {
				line_subtotal: '2000',
				line_subtotal_tax: '0',
				currency_minor_unit: 2,
			},
			prices: {
				price: '1000',
				currency_minor_unit: 2,
			},
			variation: [],
			item_data: [],
		},
	],
	shippingRates: [
		{
			package_id: 0,
			shipping_rates: [
				{
					rate_id: 'flat_rate:1',
					name: 'Flat rate',
					price: '700',
					taxes: '0',
					selected: true,
					currency_minor_unit: 2,
					meta_data: [],
				},
			],
		},
	],
	shipping_rates: [
		{
			shipping_rates: [
				{
					rate_id: 'flat_rate:1',
					name: 'Flat rate',
					price: '700',
					taxes: '0',
					selected: true,
					currency_minor_unit: 2,
					meta_data: [],
				},
			],
		},
	],
	extensions: {
		wcpay: {
			express_checkout_methods: [ 'payment_request' ],
		},
	},
};

const cartWithExpressMethods = ( methods, overrides = {} ) => ( {
	...blocksCart,
	...overrides,
	extensions: {
		...blocksCart.extensions,
		...overrides.extensions,
		wcpay: {
			...blocksCart.extensions.wcpay,
			...overrides.extensions?.wcpay,
			express_checkout_methods: methods,
		},
	},
} );

const shippingData = {
	needsShipping: true,
	shippingAddress: {
		first_name: 'Ada',
		last_name: 'Lovelace',
		address_1: '1 Test Street',
		address_2: '',
		city: 'San Francisco',
		state: 'CA',
		postcode: '94107',
		country: 'US',
	},
	shippingRates: blocksCart.shippingRates,
};

const getPaymentMethodInterfaceCartData = ( cart = blocksCart ) => ( {
	cartFees: [],
	cartItems: cart.items || cart.cartItems || [],
	extensions: cart.extensions || {},
} );

const getPaymentMethodInterfaceShippingData = ( cart = blocksCart ) => ( {
	...shippingData,
	shippingRates: cart.shipping_rates || cart.shippingRates || [],
} );

const getPaymentMethodInterfaceProps = ( cart = blocksCart ) => ( {
	cartData: getPaymentMethodInterfaceCartData( cart ),
	shippingData: getPaymentMethodInterfaceShippingData( cart ),
} );

describe( 'wc-payment-method-woopayments-express-checkout', () => {
	let expressElement;
	let expressHandlers;
	let registerExpressCheckout;
	let stripe;
	let elements;
	let availablePaymentMethods;
	let shouldLoadError;
	let originalFetch;

	beforeAll( () => {
		originalFetch = window.fetch;
		mockGetPaymentMethodData.mockReturnValue( {
			expressCheckoutParams: baseExpressCheckoutParams,
		} );
		registerExpressCheckout = require( '../index' ).default;
	} );

	beforeEach( () => {
		jest.clearAllMocks();
		baseExpressCheckoutParams.enabled_methods = [ 'payment_request' ];
		baseExpressCheckoutParams.payment_method_types = [ 'card' ];
		baseExpressCheckoutParams.is_manual_capture = false;
		baseExpressCheckoutParams.has_subscription = false;
		baseExpressCheckoutParams.checkout.currency_code = 'usd';
		baseExpressCheckoutParams.checkout.currency_decimals = 2;
		baseExpressCheckoutParams.checkout.stripe_minor_unit = 2;
		billing.cartTotal.value = 5000;
		billing.currency.code = 'USD';
		billing.currency.minorUnit = 2;
		delete baseExpressCheckoutParams.isShopperTrackingEnabled;
		delete baseExpressCheckoutParams.is_shopper_tracking_enabled;
		apiFetch.mockResolvedValue( {
			payment_result: {
				payment_status: 'success',
			},
		} );
		expressHandlers = {};
		availablePaymentMethods = undefined;
		shouldLoadError = false;
		expressElement = {
			mount: jest.fn( () => {
				if ( shouldLoadError ) {
					expressHandlers.loaderror?.();
					return;
				}

				if ( availablePaymentMethods ) {
					expressHandlers.ready?.( { availablePaymentMethods } );
				}
			} ),
			unmount: jest.fn(),
			on: jest.fn( ( eventName, handler ) => {
				expressHandlers[ eventName ] = handler;
			} ),
		};
		elements = {
			create: jest.fn( () => expressElement ),
			submit: jest.fn().mockResolvedValue( {} ),
			update: jest.fn().mockResolvedValue( {} ),
		};
		stripe = {
			elements: jest.fn( () => elements ),
			createConfirmationToken: jest.fn().mockResolvedValue( {
				confirmationToken: {
					id: 'ctoken_123',
				},
			} ),
		};
		window.Stripe = jest.fn( () => stripe );
	} );

	afterEach( () => {
		removeAllFilters( 'wcpay.express-checkout.shipping-rates' );
		removeAllFilters( 'wcpay.express-checkout.shipping-package-id' );
		delete window.Stripe;
		window.fetch = originalFetch;
		document.body.innerHTML = '';
	} );

	const getRegistration = ( name ) =>
		registerExpressPaymentMethod.mock.calls.find(
			( [ registration ] ) => registration.name === name
		)?.[ 0 ];

	const renderExpressPaymentMethod = ( registration, props = {} ) =>
		render(
			createElement( registration.content.type, {
				...registration.content.props,
				billing,
				...getPaymentMethodInterfaceProps(),
				onClick: jest.fn(),
				onClose: jest.fn(),
				setExpressPaymentError: jest.fn(),
				...props,
			} )
		);

	const getPlatformTracksRequests = () =>
		( window.fetch?.mock?.calls || [] ).filter(
			( [ , options ] ) =>
				options?.body?.get?.( 'action' ) === 'platform_tracks'
		);

	it( 'registers separate Apple Pay and Google Pay express methods', () => {
		registerExpressCheckout();

		expect( registerExpressPaymentMethod ).toHaveBeenCalledTimes( 2 );
		expect( registerExpressPaymentMethod ).toHaveBeenCalledWith(
			expect.objectContaining( {
				name: 'woocommerce_payments_express_checkout_applePay',
				gatewayId: 'woocommerce_payments',
				paymentMethodId: 'woocommerce_payments_express_checkout',
				supports: expect.objectContaining( {
					features: expect.arrayContaining( [ 'products' ] ),
					style: [ 'height', 'borderRadius' ],
				} ),
			} )
		);
		expect( registerExpressPaymentMethod ).toHaveBeenCalledWith(
			expect.objectContaining( {
				name: 'woocommerce_payments_express_checkout_googlePay',
				gatewayId: 'woocommerce_payments',
				paymentMethodId: 'woocommerce_payments_express_checkout',
				supports: expect.objectContaining( {
					features: expect.arrayContaining( [ 'products' ] ),
					style: [ 'height', 'borderRadius' ],
				} ),
			} )
		);
	} );

	it( 'registers Amazon Pay as a separate express method when server config enables it', () => {
		baseExpressCheckoutParams.enabled_methods = [
			'payment_request',
			'amazon_pay',
		];
		baseExpressCheckoutParams.payment_method_types = [
			'card',
			'amazon_pay',
		];

		registerExpressCheckout();

		expect( registerExpressPaymentMethod ).toHaveBeenCalledWith(
			expect.objectContaining( {
				name: 'woocommerce_payments_express_checkout_amazonPay',
				gatewayId: 'woocommerce_payments',
				paymentMethodId: 'woocommerce_payments_express_checkout',
			} )
		);
		expect( registerExpressPaymentMethod ).toHaveBeenCalledTimes( 3 );
	} );

	it( 'does not register Amazon Pay when Stripe method types exclude it', () => {
		baseExpressCheckoutParams.enabled_methods = [
			'payment_request',
			'amazon_pay',
		];
		baseExpressCheckoutParams.payment_method_types = [ 'card' ];

		registerExpressCheckout();

		expect( registerExpressPaymentMethod ).not.toHaveBeenCalledWith(
			expect.objectContaining( {
				name: 'woocommerce_payments_express_checkout_amazonPay',
			} )
		);
		expect( registerExpressPaymentMethod ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'mounts Stripe ECE with method-specific button options', async () => {
		registerExpressCheckout();
		const applePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_applePay'
		);

		renderExpressPaymentMethod( applePayRegistration );

		await waitFor( () => {
			expect( expressElement.mount ).toHaveBeenCalled();
		} );

		expect( window.Stripe ).toHaveBeenCalledWith( 'pk_test_123', {
			locale: 'en-us',
			stripeAccount: 'acct_123',
		} );
		expect( stripe.elements ).toHaveBeenCalledWith(
			expect.objectContaining( {
				mode: 'payment',
				amount: 5000,
				currency: 'usd',
				paymentMethodTypes: [ 'card' ],
			} )
		);
		expect( elements.create ).toHaveBeenCalledWith(
			'expressCheckout',
			expect.objectContaining( {
				buttonHeight: 48,
				buttonTheme: {
					applePay: 'black',
					googlePay: 'black',
				},
				buttonType: {
					applePay: 'buy',
					googlePay: 'buy',
				},
				paymentMethods: expect.objectContaining( {
					applePay: 'always',
					googlePay: 'never',
					klarna: 'never',
				} ),
			} )
		);
	} );

	it( 'mounts Stripe ECE with zero-decimal Stripe amount conversion', async () => {
		baseExpressCheckoutParams.checkout.currency_code = 'jpy';
		baseExpressCheckoutParams.checkout.currency_decimals = 1;
		baseExpressCheckoutParams.checkout.stripe_minor_unit = 0;
		billing.cartTotal.value = 180;
		billing.currency.code = 'JPY';
		billing.currency.minorUnit = 1;
		registerExpressCheckout();
		const applePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_applePay'
		);

		renderExpressPaymentMethod( applePayRegistration );

		await waitFor( () => {
			expect( expressElement.mount ).toHaveBeenCalled();
		} );

		expect( stripe.elements ).toHaveBeenCalledWith(
			expect.objectContaining( {
				mode: 'payment',
				amount: 18,
				currency: 'jpy',
				paymentMethodTypes: [ 'card' ],
			} )
		);
	} );

	it( 'mounts Stripe ECE with special-case currency amount conversion', async () => {
		baseExpressCheckoutParams.checkout.currency_code = 'ugx';
		baseExpressCheckoutParams.checkout.currency_decimals = 0;
		baseExpressCheckoutParams.checkout.stripe_minor_unit = 2;
		billing.cartTotal.value = 379;
		billing.currency.code = 'UGX';
		billing.currency.minorUnit = 0;
		registerExpressCheckout();
		const applePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_applePay'
		);

		renderExpressPaymentMethod( applePayRegistration );

		await waitFor( () => {
			expect( expressElement.mount ).toHaveBeenCalled();
		} );

		expect( stripe.elements ).toHaveBeenCalledWith(
			expect.objectContaining( {
				mode: 'payment',
				amount: 37900,
				currency: 'ugx',
				paymentMethodTypes: [ 'card' ],
			} )
		);
	} );

	it( 'mounts Stripe ECE with cart-aware Elements options and checkout appearance', async () => {
		baseExpressCheckoutParams.enabled_methods = [
			'payment_request',
			'amazon_pay',
		];
		baseExpressCheckoutParams.payment_method_types = [
			'card',
			'amazon_pay',
		];
		baseExpressCheckoutParams.is_manual_capture = true;
		baseExpressCheckoutParams.has_subscription = true;
		registerExpressCheckout();
		const amazonPayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_amazonPay'
		);

		renderExpressPaymentMethod( amazonPayRegistration, {
			...getPaymentMethodInterfaceProps(
				cartWithExpressMethods( [ 'amazon_pay' ] )
			),
		} );

		await waitFor( () => {
			expect( expressElement.mount ).toHaveBeenCalled();
		} );

		expect( stripe.elements ).toHaveBeenCalledWith(
			expect.objectContaining( {
				mode: 'payment',
				amount: 5000,
				currency: 'usd',
				paymentMethodTypes: [ 'amazon_pay' ],
				loader: 'never',
				captureMethod: 'manual',
				setupFutureUsage: 'off_session',
				locale: 'en-us',
				appearance: expect.any( Object ),
			} )
		);
	} );

	it( 'probes Stripe wallet availability before exposing each Blocks express method', async () => {
		registerExpressCheckout();
		const applePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_applePay'
		);
		const googlePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_googlePay'
		);

		baseExpressCheckoutParams.enabled_methods = [];

		expect(
			applePayRegistration.canMakePayment( { cart: blocksCart } )
		).toBe( false );
		expect( elements.create ).not.toHaveBeenCalled();

		baseExpressCheckoutParams.enabled_methods = [ 'payment_request' ];
		availablePaymentMethods = {
			applePay: true,
			googlePay: false,
		};

		await expect(
			applePayRegistration.canMakePayment( { cart: blocksCart } )
		).resolves.toBe( true );
		await expect(
			googlePayRegistration.canMakePayment( { cart: blocksCart } )
		).resolves.toBe( false );

		expect( elements.create ).toHaveBeenCalledWith(
			'expressCheckout',
			expect.objectContaining( {
				paymentMethods: expect.objectContaining( {
					applePay: 'always',
					googlePay: 'always',
					amazonPay: 'never',
					klarna: 'never',
				} ),
			} )
		);
		expect( expressElement.unmount ).toHaveBeenCalled();

		availablePaymentMethods = {};

		await expect(
			applePayRegistration.canMakePayment( {
				cart: {
					...blocksCart,
					cartTotals: {
						...blocksCart.cartTotals,
						total_price: '5100',
					},
					totals: {
						...blocksCart.totals,
						total_price: '5100',
					},
				},
			} )
		).resolves.toBe( false );
	} );

	it( 'probes Stripe Amazon Pay availability before exposing the Blocks Amazon method', async () => {
		baseExpressCheckoutParams.enabled_methods = [
			'payment_request',
			'amazon_pay',
		];
		baseExpressCheckoutParams.payment_method_types = [
			'card',
			'amazon_pay',
		];
		registerExpressCheckout();
		const amazonPayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_amazonPay'
		);

		availablePaymentMethods = {
			amazonPay: true,
		};

		await expect(
			amazonPayRegistration.canMakePayment( {
				cart: cartWithExpressMethods( [
					'payment_request',
					'amazon_pay',
				] ),
			} )
		).resolves.toBe( true );

		expect( stripe.elements ).toHaveBeenCalledWith(
			expect.objectContaining( {
				paymentMethodTypes: [ 'card', 'amazon_pay' ],
			} )
		);
		expect( elements.create ).toHaveBeenCalledWith(
			'expressCheckout',
			expect.objectContaining( {
				paymentMethods: expect.objectContaining( {
					applePay: 'always',
					googlePay: 'always',
					amazonPay: 'auto',
				} ),
			} )
		);
	} );

	it( 'uses Store API cart extension methods when probing wallet availability', async () => {
		baseExpressCheckoutParams.enabled_methods = [
			'payment_request',
			'amazon_pay',
		];
		baseExpressCheckoutParams.payment_method_types = [
			'card',
			'amazon_pay',
		];
		registerExpressCheckout();
		const applePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_applePay'
		);
		const amazonPayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_amazonPay'
		);

		availablePaymentMethods = {
			applePay: true,
			amazonPay: true,
		};

		await expect(
			Promise.resolve(
				applePayRegistration.canMakePayment( {
					cart: cartWithExpressMethods( [ 'amazon_pay' ] ),
				} )
			)
		).resolves.toBe( false );
		await expect(
			Promise.resolve(
				amazonPayRegistration.canMakePayment( {
					cart: cartWithExpressMethods( [] ),
				} )
			)
		).resolves.toBe( false );
		await expect(
			amazonPayRegistration.canMakePayment( {
				cart: cartWithExpressMethods( [ 'amazon_pay' ] ),
			} )
		).resolves.toBe( true );

		expect( stripe.elements ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				paymentMethodTypes: [ 'amazon_pay' ],
			} )
		);
	} );

	it( 'surfaces a Blocks error when wallet shipping address has no available rates', async () => {
		const setExpressPaymentError = jest.fn();
		const updatedCart = cartWithExpressMethods( [ 'payment_request' ], {
			shipping_rates: [
				{
					shipping_rates: [],
				},
			],
		} );
		apiFetch.mockResolvedValueOnce( updatedCart );
		registerExpressCheckout();
		const applePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_applePay'
		);

		renderExpressPaymentMethod( applePayRegistration, {
			...getPaymentMethodInterfaceProps(
				cartWithExpressMethods( [ 'payment_request' ] )
			),
			setExpressPaymentError,
		} );

		await waitFor( () => {
			expect( expressHandlers.shippingaddresschange ).toBeDefined();
		} );

		const event = {
			name: 'Ada Lovelace',
			address: {
				line1: '2 Wallet Way',
				city: 'New York',
				state: 'NY',
				postal_code: '10001',
				country: 'US',
			},
			resolve: jest.fn(),
			reject: jest.fn(),
		};

		await act( async () => {
			await expressHandlers.shippingaddresschange( event );
		} );

		expect( event.reject ).toHaveBeenCalled();
		expect( event.resolve ).not.toHaveBeenCalled();
		expect( setExpressPaymentError ).toHaveBeenCalledWith(
			'No shipping options are available for the selected address. Choose a different shipping address, or use the regular checkout.'
		);
	} );

	it( 'refreshes Blocks cart data when a mutated wallet flow is canceled', async () => {
		const updatedCart = cartWithExpressMethods( [ 'payment_request' ], {
			totals: {
				...blocksCart.totals,
				total_price: '5700',
			},
		} );
		apiFetch.mockResolvedValueOnce( updatedCart );
		registerExpressCheckout();
		const applePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_applePay'
		);
		const onClose = jest.fn();

		renderExpressPaymentMethod( applePayRegistration, {
			...getPaymentMethodInterfaceProps(
				cartWithExpressMethods( [ 'payment_request' ] )
			),
			onClose,
		} );

		await waitFor( () => {
			expect( expressHandlers.shippingaddresschange ).toBeDefined();
		} );

		await act( async () => {
			await expressHandlers.shippingaddresschange( {
				name: 'Ada Lovelace',
				address: {
					line1: '2 Wallet Way',
					city: 'New York',
					state: 'NY',
					postal_code: '10001',
					country: 'US',
				},
				resolve: jest.fn(),
				reject: jest.fn(),
			} );
		} );

		expressHandlers.cancel();

		expect( onClose ).toHaveBeenCalled();
		expect( mockInvalidateResolutionForStore ).toHaveBeenCalled();
	} );

	it( 'places the Blocks order with a confirmation token through Store API', async () => {
		registerExpressCheckout();
		const googlePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_googlePay'
		);

		renderExpressPaymentMethod( googlePayRegistration );

		await waitFor( () => {
			expect( expressHandlers.confirm ).toBeDefined();
		} );

		await act( async () => {
			await expressHandlers.confirm( {
				billingDetails: {
					email: 'shopper@example.test',
					name: 'Ada Lovelace',
				},
			} );
		} );

		const checkoutRequest = apiFetch.mock.calls[ 0 ][ 0 ];

		expect( checkoutRequest ).toEqual(
			expect.objectContaining( {
				method: 'POST',
				path: '/wc/store/v1/checkout',
				headers: expect.objectContaining( {
					Nonce: 'store-api-nonce',
					'X-WooPayments-Tokenized-Cart-Nonce': 'cart-nonce',
					'X-WooPayments-Tokenized-Cart': true,
				} ),
				data: expect.objectContaining( {
					payment_method: 'woocommerce_payments',
					payment_data: expect.arrayContaining( [
						{
							key: 'wcpay-confirmation-token',
							value: 'ctoken_123',
						},
						{
							key: 'wcpay-is-platform-payment-method',
							value: 'true',
						},
						{
							key: 'wcpay-express-payment-method-types',
							value: JSON.stringify( [ 'card' ] ),
						},
						{
							key: 'wcpay-express-checkout-context',
							value: 'checkout',
						},
					] ),
				} ),
			} )
		);
		expect( checkoutRequest.headers ).not.toHaveProperty(
			'X-WooPayments-Tokenized-Cart-Session-Nonce'
		);
	} );

	it( 'resolves wallet clicks with cart line items and shipping rates', async () => {
		const onClick = jest.fn();
		const resolve = jest.fn();
		registerExpressCheckout();
		const googlePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_googlePay'
		);

		renderExpressPaymentMethod( googlePayRegistration, {
			...getPaymentMethodInterfaceProps(
				cartWithExpressMethods( [ 'payment_request' ] )
			),
			onClick,
		} );

		await waitFor( () => {
			expect( expressHandlers.click ).toBeDefined();
		} );

		act( () => {
			expressHandlers.click( { resolve } );
		} );

		expect( resolve ).toHaveBeenCalledWith(
			expect.objectContaining( {
				lineItems: expect.arrayContaining( [
					expect.objectContaining( {
						name: 'Beanie (x2)',
						amount: 2000,
					} ),
					expect.objectContaining( {
						name: 'Shipping',
						amount: 700,
					} ),
					expect.objectContaining( {
						name: 'Tax',
						amount: 300,
					} ),
				] ),
				shippingRates: expect.arrayContaining( [
					expect.objectContaining( {
						id: 'flat_rate:1',
						displayName: 'Flat rate',
						amount: 700,
					} ),
				] ),
			} )
		);
		expect( onClick ).toHaveBeenCalled();
	} );

	it( 'updates the Store API cart and Elements when the wallet shipping address changes', async () => {
		baseExpressCheckoutParams.has_subscription = true;
		const updatedCart = cartWithExpressMethods( [ 'payment_request' ], {
			items: [
				{
					name: 'Wallet Beanie',
					quantity: 3,
					totals: {
						line_subtotal: '3300',
						line_subtotal_tax: '0',
						currency_minor_unit: 2,
					},
					prices: {
						price: '1100',
						currency_minor_unit: 2,
					},
					variation: [],
					item_data: [],
				},
			],
			totals: {
				...blocksCart.totals,
				total_price: '5700',
				total_shipping: '900',
			},
			shipping_rates: [
				{
					shipping_rates: [
						{
							rate_id: 'wallet_rate:1',
							name: 'Wallet rate',
							price: '900',
							taxes: '0',
							selected: true,
							currency_minor_unit: 2,
							meta_data: [],
						},
					],
				},
			],
		} );
		apiFetch.mockResolvedValueOnce( updatedCart );
		registerExpressCheckout();
		const applePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_applePay'
		);

		renderExpressPaymentMethod( applePayRegistration, {
			...getPaymentMethodInterfaceProps(
				cartWithExpressMethods( [ 'payment_request' ] )
			),
		} );

		await waitFor( () => {
			expect( expressHandlers.shippingaddresschange ).toBeDefined();
		} );

		const event = {
			name: 'Ada Lovelace',
			address: {
				line1: '2 Wallet Way',
				city: 'New York',
				state: 'NY',
				postal_code: '10001',
				country: 'US',
			},
			resolve: jest.fn(),
			reject: jest.fn(),
		};

		await act( async () => {
			await expressHandlers.shippingaddresschange( event );
		} );

		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				method: 'POST',
				path: '/wc/store/v1/cart/update-customer',
				headers: expect.objectContaining( {
					Nonce: 'store-api-nonce',
					'X-WooPayments-Tokenized-Cart-Nonce': 'cart-nonce',
				} ),
				data: {
					shipping_address: expect.objectContaining( {
						first_name: 'Ada',
						last_name: 'Lovelace',
						address_1: '2 Wallet Way',
						city: 'New York',
						state: 'NY',
						postcode: '10001',
						country: 'US',
					} ),
				},
			} )
		);
		expect( elements.update ).toHaveBeenCalledWith(
			expect.objectContaining( {
				amount: 5700,
				setupFutureUsage: 'off_session',
			} )
		);
		expect( event.resolve ).toHaveBeenCalledWith(
			expect.objectContaining( {
				lineItems: expect.arrayContaining( [
					expect.objectContaining( { name: 'Wallet Beanie (x3)' } ),
				] ),
				shippingRates: expect.arrayContaining( [
					expect.objectContaining( { id: 'wallet_rate:1' } ),
				] ),
			} )
		);
		expect( event.reject ).not.toHaveBeenCalled();
	} );

	it( 'selects Store API shipping rates and updates Elements when the wallet shipping rate changes', async () => {
		const updatedCart = cartWithExpressMethods( [ 'payment_request' ], {
			items: [
				{
					name: 'Wallet Tote',
					quantity: 1,
					totals: {
						line_subtotal: '4000',
						line_subtotal_tax: '0',
						currency_minor_unit: 2,
					},
					prices: {
						price: '4000',
						currency_minor_unit: 2,
					},
					variation: [],
					item_data: [],
				},
			],
			totals: {
				...blocksCart.totals,
				total_price: '4300',
				total_shipping: '0',
				total_tax: '300',
			},
			shipping_rates: [
				{
					shipping_rates: [
						{
							rate_id: 'free_shipping:1',
							name: 'Free shipping',
							price: '0',
							taxes: '0',
							selected: true,
							currency_minor_unit: 2,
							meta_data: [],
						},
					],
				},
			],
		} );
		apiFetch.mockResolvedValueOnce( updatedCart );
		registerExpressCheckout();
		const googlePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_googlePay'
		);

		renderExpressPaymentMethod( googlePayRegistration, {
			...getPaymentMethodInterfaceProps(
				cartWithExpressMethods( [ 'payment_request' ] )
			),
		} );

		await waitFor( () => {
			expect( expressHandlers.shippingratechange ).toBeDefined();
		} );

		const event = {
			shippingRate: {
				id: 'free_shipping:1',
			},
			resolve: jest.fn(),
			reject: jest.fn(),
		};

		await act( async () => {
			await expressHandlers.shippingratechange( event );
		} );

		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				method: 'POST',
				path: '/wc/store/v1/cart/select-shipping-rate',
				data: {
					package_id: 0,
					rate_id: 'free_shipping:1',
				},
			} )
		);
		expect( elements.update ).toHaveBeenCalledWith(
			expect.objectContaining( {
				amount: 4300,
			} )
		);
		expect( event.resolve ).toHaveBeenCalledWith(
			expect.objectContaining( {
				lineItems: expect.arrayContaining( [
					expect.objectContaining( { name: 'Wallet Tote' } ),
				] ),
			} )
		);
		expect( event.reject ).not.toHaveBeenCalled();
	} );

	it( 'selects the filtered subscription package when the wallet shipping rate comes from subscription extension data', async () => {
		const subscriptionShippingRates = [
			{
				package_id: 'sub_month_0',
				shipping_rates: [
					{
						rate_id: 'subscription_rate:1',
						name: 'Subscription shipping',
						price: '500',
						taxes: '0',
						selected: true,
						currency_minor_unit: 2,
						meta_data: [],
					},
				],
			},
		];
		const subscriptionCart = cartWithExpressMethods(
			[ 'payment_request' ],
			{
				shipping_rates: [
					{
						package_id: 0,
						shipping_rates: [],
					},
				],
				extensions: {
					subscriptions: [
						{
							shipping_rates: subscriptionShippingRates,
						},
					],
				},
			}
		);
		const updatedCart = cartWithExpressMethods( [ 'payment_request' ], {
			shipping_rates: subscriptionShippingRates,
		} );
		addFilter(
			'wcpay.express-checkout.shipping-rates',
			'woocommerce/native-woopayments/test-subscription-rates',
			( rates, cart ) =>
				rates.length
					? rates
					: cart.extensions.subscriptions[ 0 ].shipping_rates[ 0 ]
							.shipping_rates
		);
		addFilter(
			'wcpay.express-checkout.shipping-package-id',
			'woocommerce/native-woopayments/test-subscription-package',
			( packageId, cart, rateId ) =>
				rateId === 'subscription_rate:1' ? 'sub_month_0' : packageId
		);
		apiFetch.mockResolvedValueOnce( updatedCart );
		registerExpressCheckout();
		const googlePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_googlePay'
		);

		renderExpressPaymentMethod( googlePayRegistration, {
			...getPaymentMethodInterfaceProps( subscriptionCart ),
		} );

		await waitFor( () => {
			expect( expressHandlers.click ).toBeDefined();
		} );
		await waitFor( () => {
			expect( expressHandlers.shippingratechange ).toBeDefined();
		} );

		const clickResolve = jest.fn();
		act( () => {
			expressHandlers.click( { resolve: clickResolve } );
		} );
		expect( clickResolve ).toHaveBeenCalledWith(
			expect.objectContaining( {
				shippingRates: expect.arrayContaining( [
					expect.objectContaining( {
						id: 'subscription_rate:1',
						displayName: 'Subscription shipping',
					} ),
				] ),
			} )
		);

		const event = {
			shippingRate: {
				id: 'subscription_rate:1',
			},
			resolve: jest.fn(),
			reject: jest.fn(),
		};

		await act( async () => {
			await expressHandlers.shippingratechange( event );
		} );

		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				method: 'POST',
				path: '/wc/store/v1/cart/select-shipping-rate',
				data: {
					package_id: 'sub_month_0',
					rate_id: 'subscription_rate:1',
				},
			} )
		);
		expect( event.resolve ).toHaveBeenCalled();
		expect( event.reject ).not.toHaveBeenCalled();
	} );

	it( 'surfaces unsuccessful Store API payment statuses to the Blocks error area', async () => {
		const setExpressPaymentError = jest.fn();
		const paymentFailed = jest.fn();
		apiFetch.mockResolvedValueOnce( {
			message: 'Payment failed.',
			payment_result: {
				payment_status: 'failure',
				payment_details: [
					{
						key: 'errorMessage',
						value: 'Card declined.',
					},
				],
			},
		} );
		registerExpressCheckout();
		const googlePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_googlePay'
		);

		renderExpressPaymentMethod( googlePayRegistration, {
			setExpressPaymentError,
		} );

		await waitFor( () => {
			expect( expressHandlers.confirm ).toBeDefined();
		} );

		await act( async () => {
			await expressHandlers.confirm( {
				paymentFailed,
				billingDetails: {
					email: 'shopper@example.test',
					name: 'Ada Lovelace',
				},
			} );
		} );

		expect( setExpressPaymentError ).toHaveBeenCalledWith(
			'Card declined.'
		);
		expect( paymentFailed ).toHaveBeenCalledWith( {
			reason: 'fail',
			message: 'Card declined.',
		} );
	} );

	it( 'refreshes Blocks cart data when a mutated wallet confirmation fails', async () => {
		const setExpressPaymentError = jest.fn();
		const updatedCart = cartWithExpressMethods( [ 'payment_request' ], {
			totals: {
				...blocksCart.totals,
				total_price: '5700',
			},
		} );
		apiFetch.mockResolvedValueOnce( updatedCart ).mockResolvedValueOnce( {
			message: 'Payment failed.',
			payment_result: {
				payment_status: 'failure',
				payment_details: [
					{
						key: 'errorMessage',
						value: 'Card declined.',
					},
				],
			},
		} );
		registerExpressCheckout();
		const googlePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_googlePay'
		);

		renderExpressPaymentMethod( googlePayRegistration, {
			...getPaymentMethodInterfaceProps(
				cartWithExpressMethods( [ 'payment_request' ] )
			),
			setExpressPaymentError,
		} );

		await waitFor( () => {
			expect( expressHandlers.shippingaddresschange ).toBeDefined();
		} );
		await waitFor( () => {
			expect( expressHandlers.confirm ).toBeDefined();
		} );

		await act( async () => {
			await expressHandlers.shippingaddresschange( {
				name: 'Ada Lovelace',
				address: {
					line1: '2 Wallet Way',
					city: 'New York',
					state: 'NY',
					postal_code: '10001',
					country: 'US',
				},
				resolve: jest.fn(),
				reject: jest.fn(),
			} );
			await expressHandlers.confirm( {
				billingDetails: {
					email: 'shopper@example.test',
					name: 'Ada Lovelace',
				},
			} );
		} );

		expect( setExpressPaymentError ).toHaveBeenCalledWith(
			'Card declined.'
		);
		expect( mockInvalidateResolutionForStore ).toHaveBeenCalled();
	} );

	it( 'places the Blocks Amazon Pay order with express payment method types', async () => {
		baseExpressCheckoutParams.enabled_methods = [
			'payment_request',
			'amazon_pay',
		];
		baseExpressCheckoutParams.payment_method_types = [
			'card',
			'amazon_pay',
		];
		registerExpressCheckout();
		const amazonPayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_amazonPay'
		);

		renderExpressPaymentMethod( amazonPayRegistration, {
			...getPaymentMethodInterfaceProps(
				cartWithExpressMethods( [ 'payment_request', 'amazon_pay' ] )
			),
		} );

		await waitFor( () => {
			expect( expressHandlers.confirm ).toBeDefined();
		} );

		await act( async () => {
			await expressHandlers.confirm( {
				billingDetails: {
					email: 'shopper@example.test',
					name: 'Ada Lovelace',
				},
			} );
		} );

		const checkoutRequest = apiFetch.mock.calls[ 0 ][ 0 ];

		expect( checkoutRequest.data.payment_data ).toEqual(
			expect.arrayContaining( [
				{
					key: 'wcpay-express-payment-method-types',
					value: JSON.stringify( [ 'card', 'amazon_pay' ] ),
				},
				{
					key: 'wcpay-express-checkout-context',
					value: 'checkout',
				},
			] )
		);
	} );

	it( 'records only available method load tracking events', async () => {
		window.fetch = jest.fn().mockResolvedValue( {} );
		availablePaymentMethods = {
			applePay: false,
			googlePay: true,
		};
		registerExpressCheckout();
		const applePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_applePay'
		);

		renderExpressPaymentMethod( applePayRegistration );

		await waitFor( () => {
			expect( expressElement.mount ).toHaveBeenCalled();
		} );

		expect( getPlatformTracksRequests() ).toHaveLength( 0 );
	} );

	it( 'records Google Pay load and click tracking events', async () => {
		const onClick = jest.fn();
		window.fetch = jest.fn().mockResolvedValue( {} );
		availablePaymentMethods = {
			googlePay: true,
		};
		registerExpressCheckout();
		const googlePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_googlePay'
		);

		renderExpressPaymentMethod( googlePayRegistration, { onClick } );

		await waitFor( () => {
			expect( expressElement.mount ).toHaveBeenCalled();
		} );

		act( () => {
			expressHandlers.click( { resolve: jest.fn() } );
		} );

		const requests = getPlatformTracksRequests();

		expect( requests ).toHaveLength( 2 );
		expect(
			requests.map( ( [ , options ] ) =>
				options.body.get( 'tracksEventName' )
			)
		).toEqual( [ 'gpay_button_load', 'gpay_button_click' ] );
		expect(
			requests.map( ( [ , options ] ) =>
				JSON.parse( options.body.get( 'tracksEventProp' ) )
			)
		).toEqual( [ { source: 'checkout' }, { source: 'checkout' } ] );
		expect( onClick ).toHaveBeenCalled();
	} );

	it( 'does not record Blocks express checkout tracking when shopper tracking is disabled', async () => {
		window.fetch = jest.fn().mockResolvedValue( {} );
		baseExpressCheckoutParams.is_shopper_tracking_enabled = false;
		availablePaymentMethods = {
			googlePay: true,
		};
		registerExpressCheckout();
		const googlePayRegistration = getRegistration(
			'woocommerce_payments_express_checkout_googlePay'
		);

		renderExpressPaymentMethod( googlePayRegistration );

		await waitFor( () => {
			expect( expressElement.mount ).toHaveBeenCalled();
		} );

		act( () => {
			expressHandlers.click( { resolve: jest.fn() } );
		} );

		expect( getPlatformTracksRequests() ).toHaveLength( 0 );
	} );
} );
