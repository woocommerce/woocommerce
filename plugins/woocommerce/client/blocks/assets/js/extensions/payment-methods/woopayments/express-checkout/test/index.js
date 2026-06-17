/**
 * External dependencies
 */
import { act, render, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { registerExpressPaymentMethod } from '@woocommerce/blocks-registry';
import apiFetch from '@wordpress/api-fetch';

jest.mock( '@woocommerce/blocks-registry', () => ( {
	registerExpressPaymentMethod: jest.fn(),
} ) );

const mockGetPaymentMethodData = jest.fn();
jest.mock( '@woocommerce/settings', () => ( {
	getPaymentMethodData: ( ...args ) => mockGetPaymentMethodData( ...args ),
} ) );

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const baseExpressCheckoutParams = {
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
		stripe_minor_unit: 100,
		country_code: 'US',
		needs_payer_phone: true,
		allowed_shipping_countries: [ 'US' ],
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
	currency: {
		code: 'USD',
		minorUnit: 2,
	},
};

const blocksCart = {
	cartTotals: {
		total_price: '5000',
		currency_code: 'USD',
		currency_minor_unit: 2,
	},
	cartItems: [],
	extensions: {},
};

describe( 'wc-payment-method-woopayments-express-checkout', () => {
	let expressElement;
	let expressHandlers;
	let registerExpressCheckout;
	let stripe;
	let elements;
	let availablePaymentMethods;
	let shouldLoadError;

	beforeAll( () => {
		mockGetPaymentMethodData.mockReturnValue( {
			expressCheckoutParams: baseExpressCheckoutParams,
		} );
		registerExpressCheckout = require( '../index' ).default;
	} );

	beforeEach( () => {
		jest.clearAllMocks();
		baseExpressCheckoutParams.enabled_methods = [ 'payment_request' ];
		baseExpressCheckoutParams.payment_method_types = [ 'card' ];
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
		delete window.Stripe;
		document.body.innerHTML = '';
	} );

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
		const applePayRegistration =
			registerExpressPaymentMethod.mock.calls.find(
				( [ registration ] ) =>
					registration.name ===
					'woocommerce_payments_express_checkout_applePay'
			)[ 0 ];

		render(
			createElement( applePayRegistration.content.type, {
				...applePayRegistration.content.props,
				billing,
				onClick: jest.fn(),
				onClose: jest.fn(),
				setExpressPaymentError: jest.fn(),
			} )
		);

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

	it( 'probes Stripe wallet availability before exposing each Blocks express method', async () => {
		registerExpressCheckout();
		const applePayRegistration =
			registerExpressPaymentMethod.mock.calls.find(
				( [ registration ] ) =>
					registration.name ===
					'woocommerce_payments_express_checkout_applePay'
			)[ 0 ];
		const googlePayRegistration =
			registerExpressPaymentMethod.mock.calls.find(
				( [ registration ] ) =>
					registration.name ===
					'woocommerce_payments_express_checkout_googlePay'
			)[ 0 ];

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
		const amazonPayRegistration =
			registerExpressPaymentMethod.mock.calls.find(
				( [ registration ] ) =>
					registration.name ===
					'woocommerce_payments_express_checkout_amazonPay'
			)[ 0 ];

		availablePaymentMethods = {
			amazonPay: true,
		};

		await expect(
			amazonPayRegistration.canMakePayment( { cart: blocksCart } )
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

	it( 'places the Blocks order with a confirmation token through Store API', async () => {
		registerExpressCheckout();
		const googlePayRegistration =
			registerExpressPaymentMethod.mock.calls.find(
				( [ registration ] ) =>
					registration.name ===
					'woocommerce_payments_express_checkout_googlePay'
			)[ 0 ];

		render(
			createElement( googlePayRegistration.content.type, {
				...googlePayRegistration.content.props,
				billing,
				onClick: jest.fn(),
				onClose: jest.fn(),
				setExpressPaymentError: jest.fn(),
			} )
		);

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
		const amazonPayRegistration =
			registerExpressPaymentMethod.mock.calls.find(
				( [ registration ] ) =>
					registration.name ===
					'woocommerce_payments_express_checkout_amazonPay'
			)[ 0 ];

		render(
			createElement( amazonPayRegistration.content.type, {
				...amazonPayRegistration.content.props,
				billing,
				onClick: jest.fn(),
				onClose: jest.fn(),
				setExpressPaymentError: jest.fn(),
			} )
		);

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
} );
