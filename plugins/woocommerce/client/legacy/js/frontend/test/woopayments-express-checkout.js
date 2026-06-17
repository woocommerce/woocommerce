/**
 * @jest-environment jest-fixed-jsdom
 */

describe( 'WooPayments express checkout', () => {
	let bodyEventHandlers;
	let elements;
	let expressElement;
	let expressHandlers;
	let stripe;

	async function flushPromises() {
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	}

	function createJQueryMock() {
		const defaultResult = {
			length: 0,
			on: jest.fn( () => defaultResult ),
		};
		const bodyResult = {
			length: 1,
			on: jest.fn( ( event, handler ) => {
				bodyEventHandlers[ event ] = handler;
				return bodyResult;
			} ),
		};
		const jQueryMock = jest.fn( ( selectorOrCallback ) => {
			if ( typeof selectorOrCallback === 'function' ) {
				selectorOrCallback();
				return defaultResult;
			}

			if ( selectorOrCallback === document.body ) {
				return bodyResult;
			}

			return defaultResult;
		} );

		return jQueryMock;
	}

	function getBaseConfig() {
		return {
			ajax_url: 'https://example.test/admin-ajax.php',
			enabled_methods: [ 'payment_request' ],
			button_context: 'checkout',
			has_block: false,
			store_name: 'Test Store',
			nonce: {
				store_api_nonce: 'store-api-nonce',
				tokenized_cart_nonce: 'cart-nonce',
				tokenized_cart_session_nonce: 'cart-session-nonce',
				platform_tracker: 'tracks-nonce',
			},
			checkout: {
				currency_code: 'usd',
				needs_payer_phone: true,
				needs_shipping: true,
				allowed_shipping_countries: [ 'US' ],
			},
			button: {
				type: 'buy',
				theme: 'dark',
				height: '48',
				radius: '4',
				context: 'checkout',
			},
			stripe: {
				publishableKey: 'pk_test_123',
				accountId: 'acct_123',
				locale: 'en-us',
			},
			flags: {
				isEceUsingConfirmationTokens: true,
			},
		};
	}

	function getCartResponse() {
		return {
			needs_shipping: true,
			totals: {
				total_price: '5000',
				total_refund: '0',
				currency_code: 'USD',
			},
			billing_address: {
				email: 'shopper@example.test',
				first_name: 'Ada',
				last_name: 'Lovelace',
				address_1: '1 Test Street',
				city: 'San Francisco',
				state: 'CA',
				postcode: '94107',
				country: 'US',
			},
			shipping_address: {
				first_name: 'Ada',
				last_name: 'Lovelace',
				address_1: '1 Test Street',
				city: 'San Francisco',
				state: 'CA',
				postcode: '94107',
				country: 'US',
			},
		};
	}

	beforeEach( () => {
		jest.resetModules();
		bodyEventHandlers = {};
		expressHandlers = {};
		document.body.innerHTML =
			'<div class="woocommerce-notices-wrapper"></div>' +
			'<div class="wcpay-express-checkout-wrapper">' +
			'<div id="wcpay-express-checkout-element"></div>' +
			'<p id="wcpay-express-checkout-button-separator">OR</p>' +
			'</div>';

		const jQueryMock = createJQueryMock();
		global.jQuery = jQueryMock;
		global.$ = jQueryMock;
		window.jQuery = jQueryMock;
		window.$ = jQueryMock;
		window.wp = {
			apiFetch: jest.fn().mockResolvedValue( getCartResponse() ),
		};
		window.wcpayExpressCheckoutParams = getBaseConfig();
		expressElement = {
			mount: jest.fn(),
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
		delete global.jQuery;
		delete global.$;
		delete window.jQuery;
		delete window.$;
		delete window.wp;
		delete window.Stripe;
		delete window.wcpayExpressCheckoutParams;
		document.body.innerHTML = '';
	} );

	test( 'mounts Stripe ECE after checkout totals update with tokenized cart headers', async () => {
		require( '../woopayments-express-checkout' );

		expect( bodyEventHandlers.updated_checkout ).toBeDefined();
		await bodyEventHandlers.updated_checkout();
		await flushPromises();

		expect( window.wp.apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				method: 'GET',
				path: '/wc/store/v1/cart',
				headers: expect.objectContaining( {
					Nonce: 'store-api-nonce',
					'X-WooPayments-Tokenized-Cart-Nonce': 'cart-nonce',
				} ),
			} )
		);
		expect(
			window.wp.apiFetch.mock.calls[ 0 ][ 0 ].headers
		).not.toHaveProperty( 'X-WooPayments-Tokenized-Cart-Session-Nonce' );
		expect( window.Stripe ).toHaveBeenCalledWith( 'pk_test_123', {
			locale: 'en-us',
			stripeAccount: 'acct_123',
		} );
		expect( stripe.elements ).toHaveBeenCalledWith(
			expect.objectContaining( {
				mode: 'payment',
				amount: 5000,
				currency: 'usd',
				loader: 'never',
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
					googlePay: 'always',
					klarna: 'never',
				} ),
			} )
		);
		expect( expressElement.mount ).toHaveBeenCalledWith(
			'#wcpay-express-checkout-element'
		);
	} );

	test( 'places the classic checkout order with a confirmation token', async () => {
		window.wp.apiFetch
			.mockResolvedValueOnce( getCartResponse() )
			.mockResolvedValueOnce( {
				payment_result: {
					payment_status: 'success',
				},
			} );
		require( '../woopayments-express-checkout' );

		await bodyEventHandlers.updated_checkout();
		await flushPromises();

		await expressHandlers.confirm( {
			billingDetails: {
				email: 'shopper@example.test',
				name: 'Ada Lovelace',
			},
		} );

		expect( stripe.createConfirmationToken ).toHaveBeenCalledWith( {
			elements,
		} );
		expect( window.wp.apiFetch ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				method: 'POST',
				path: '/wc/store/v1/checkout',
				headers: expect.objectContaining( {
					Nonce: 'store-api-nonce',
					'X-WooPayments-Tokenized-Cart': true,
					'X-WooPayments-Tokenized-Cart-Nonce': 'cart-nonce',
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
					] ),
				} ),
			} )
		);
	} );

	test( 'does not initialize classic ECE on block checkout surfaces', async () => {
		window.wcpayExpressCheckoutParams.has_block = true;

		require( '../woopayments-express-checkout' );
		await flushPromises();

		expect( window.wp.apiFetch ).not.toHaveBeenCalled();
		expect( window.Stripe ).not.toHaveBeenCalled();
	} );
} );
