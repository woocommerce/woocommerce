/**
 * @jest-environment jest-fixed-jsdom
 */

describe( 'WooPayments checkout', () => {
	let bodyEventHandlers;
	let mountPaymentElement;
	let stripeMock;
	let stripeElementsOptions;
	let unmountPaymentElement;

	function createJQueryMock() {
		const defaultResult = {
			length: 0,
			filter: jest.fn( () => defaultResult ),
			find: jest.fn( () => defaultResult ),
			on: jest.fn( () => defaultResult ),
			trigger: jest.fn( () => defaultResult ),
			val: jest.fn(),
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
		jQueryMock.post = jest.fn( () => ( {
			done: jest.fn( ( callback ) => {
				callback( { status_code: 200 } );
				return {
					fail: jest.fn(),
				};
			} ),
		} ) );

		return jQueryMock;
	}

	beforeEach( () => {
		jest.resetModules();
		bodyEventHandlers = {};
		mountPaymentElement = jest.fn();
		stripeElementsOptions = null;
		unmountPaymentElement = jest.fn();
		document.body.innerHTML =
			'<form class="checkout">' +
			'<input type="radio" name="payment_method" value="woocommerce_payments" checked />' +
			'<div id="wcpay-core-payment-element"></div>' +
			'</form>';

		const jQueryMock = createJQueryMock();
		global.jQuery = jQueryMock;
		global.$ = jQueryMock;
		window.jQuery = jQueryMock;
		window.$ = jQueryMock;
		window.wcpay_core_checkout_config = {
			accountId: 'acct_test',
			ajaxUrl: 'https://example.test/admin-ajax.php',
			cartTotal: '5000',
			currency: 'GBP',
			gatewayId: 'woocommerce_payments',
			isCoreNativeCheckoutAvailable: true,
			locale: 'en-US',
			publishableKey: 'pk_test',
		};
		stripeMock = {
			elements: jest.fn( ( options ) => {
				stripeElementsOptions = options;
				return {
					submit: jest.fn( () => Promise.resolve( {} ) ),
					create: jest.fn( () => ( {
						mount: mountPaymentElement,
						unmount: unmountPaymentElement,
					} ) ),
				};
			} ),
			confirmPayment: jest.fn( () =>
				Promise.resolve( {
					paymentIntent: { id: 'pi_native' },
				} )
			),
			confirmSetup: jest.fn( () =>
				Promise.resolve( {
					setupIntent: { id: 'seti_native' },
				} )
			),
			handleNextAction: jest.fn( () =>
				Promise.resolve( {
					paymentIntent: { id: 'pi_native' },
				} )
			),
		};
		window.Stripe = jest.fn( () => stripeMock );
	} );

	afterEach( () => {
		delete global.jQuery;
		delete global.$;
		delete window.jQuery;
		delete window.$;
		delete window.wcpay_core_checkout_config;
		delete window.Stripe;
		document.body.innerHTML = '';
		window.location.hash = '';
	} );

	test( 'passes the checkout total to Stripe Elements as a number', () => {
		require( '../woopayments-checkout' );

		expect( stripeElementsOptions ).toMatchObject( {
			amount: 5000,
			currency: 'gbp',
			mode: 'payment',
			paymentMethodCreation: 'manual',
			paymentMethodTypes: [ 'card' ],
		} );
	} );

	test( 'uses setup mode when the checkout total is zero', () => {
		window.wcpay_core_checkout_config.cartTotal = '0';

		require( '../woopayments-checkout' );

		expect( stripeElementsOptions ).toMatchObject( {
			currency: 'gbp',
			mode: 'setup',
			paymentMethodCreation: 'manual',
			paymentMethodTypes: [ 'card' ],
		} );
		expect( stripeElementsOptions ).not.toHaveProperty( 'amount' );
	} );

	test( 'handles PaymentIntent confirmation hashes through next actions', async () => {
		window.location.hash = '#wcpay-confirm-pi:123:pi_native_secret_abc:nonce';

		require( '../woopayments-checkout' );

		await Promise.resolve();
		await Promise.resolve();

		expect( stripeMock.handleNextAction ).toHaveBeenCalledWith( {
			clientSecret: 'pi_native_secret_abc',
		} );
		expect( stripeMock.confirmPayment ).not.toHaveBeenCalled();
		expect( global.jQuery.post ).toHaveBeenCalledWith(
			'https://example.test/admin-ajax.php',
			expect.objectContaining( {
				action: 'update_order_status',
				order_id: '123',
				_ajax_nonce: 'nonce',
				intent_id: 'pi_native',
			} )
		);
	} );

	test( 'remounts the payment element after checkout updates replace the payment markup', () => {
		require( '../woopayments-checkout' );

		const initialContainer = document.getElementById(
			'wcpay-core-payment-element'
		);

		expect( mountPaymentElement ).toHaveBeenCalledWith( initialContainer );

		document.body.innerHTML =
			'<form class="checkout">' +
			'<input type="radio" name="payment_method" value="woocommerce_payments" checked />' +
			'<div id="wcpay-core-payment-element"></div>' +
			'</form>';

		const replacementContainer = document.getElementById(
			'wcpay-core-payment-element'
		);

		bodyEventHandlers.updated_checkout();

		expect( unmountPaymentElement ).toHaveBeenCalledTimes( 1 );
		expect( mountPaymentElement ).toHaveBeenCalledWith(
			replacementContainer
		);
	} );
} );
