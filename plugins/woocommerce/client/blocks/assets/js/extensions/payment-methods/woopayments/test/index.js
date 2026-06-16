/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import registerWooPayments from '../index';

jest.mock( '@woocommerce/blocks-registry', () => ( {
	registerPaymentMethod: jest.fn(),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getPaymentMethodData: jest.fn( () => ( {
		title: 'WooPayments',
		supports: [ 'products' ],
		gatewayId: 'woocommerce_payments',
		publishableKey: 'pk_test_123',
		accountId: 'acct_123',
		currency: 'USD',
		isCoreNativeCheckoutAvailable: true,
		usesLegacyOrderStatusBridge: false,
		ajaxUrl: 'https://example.test/wp-admin/admin-ajax.php',
	} ) ),
} ) );

const originalFetch = window.fetch;

describe( 'wc-payment-method-woopayments', () => {
	afterEach( () => {
		delete window.Stripe;
		window.fetch = originalFetch;
	} );

	it( 'submits wcpay-payment-method metadata for a new card method', async () => {
		const registration = registerWooPayments();
		let setupResult;
		const onPaymentSetup = jest.fn( ( callback ) => {
			setupResult = callback();
		} );
		const emitResponse = {
			responseTypes: {
				SUCCESS: 'success',
				ERROR: 'error',
			},
			noticeContexts: {
				PAYMENTS: 'payments',
			},
		};

		const content = registration.content;

		render(
			createElement( content.type, {
				...content.props,
				eventRegistration: {
					onPaymentSetup,
					onCheckoutSuccess: jest.fn(),
				},
				emitResponse,
			} )
		);

		await waitFor( () => {
			expect( onPaymentSetup ).toHaveBeenCalled();
		} );

		await expect( setupResult ).resolves.toEqual( {
			type: 'success',
			meta: {
				paymentMethodData: {
					'wcpay-payment-method': '',
					'wcpay-payment-method-error-code': '',
					'wcpay-payment-method-error-message': '',
					'wcpay-fingerprint': '',
				},
			},
		} );
	} );

	it( 'enables WooCommerce saved-payment controls', () => {
		const registration = registerWooPayments();

		expect( registration.supports ).toEqual(
			expect.objectContaining( {
				features: [ 'products' ],
				showSavedCards: true,
				showSaveOption: true,
			} )
		);
	} );

	it( 'returns a payment notice when Stripe element validation fails', async () => {
		const createPaymentMethod = jest.fn().mockResolvedValue( {
			error: {
				code: 'incomplete_number',
				message: 'Your card number is incomplete.',
			},
		} );
		window.Stripe = jest.fn( () => ( {
			elements: jest.fn( () => ( {
				create: jest.fn( () => ( {
					mount: jest.fn(),
				} ) ),
			} ) ),
			createPaymentMethod,
		} ) );

		const registration = registerWooPayments();
		let setupResult;
		const onPaymentSetup = jest.fn( ( callback ) => {
			setupResult = callback();
		} );
		const emitResponse = {
			responseTypes: {
				SUCCESS: 'success',
				ERROR: 'error',
			},
			noticeContexts: {
				PAYMENTS: 'payments',
			},
		};

		const content = registration.content;

		render(
			createElement( content.type, {
				...content.props,
				eventRegistration: {
					onPaymentSetup,
					onCheckoutSuccess: jest.fn(),
				},
				emitResponse,
			} )
		);

		await waitFor( () => {
			expect( onPaymentSetup ).toHaveBeenCalled();
		} );

		await expect( setupResult ).resolves.toEqual( {
			type: 'error',
			message: 'Your card number is incomplete.',
			messageContext: 'payments',
		} );
	} );

	it( 'confirms full #wcpay-confirm-pi redirects through Stripe.js and updates the order status', async () => {
		const confirmPayment = jest.fn().mockResolvedValue( {} );
		window.fetch = jest.fn().mockResolvedValue( {
			json: jest.fn().mockResolvedValue( {
				return_url: 'https://example.test/checkout/order-received/123/',
			} ),
		} );
		window.Stripe = jest.fn( () => ( {
			elements: jest.fn( () => ( {
				create: jest.fn( () => ( {
					mount: jest.fn(),
				} ) ),
			} ) ),
			createPaymentMethod: jest.fn().mockResolvedValue( {} ),
			confirmPayment,
		} ) );

		const registration = registerWooPayments();
		let checkoutSuccessResult;
		const onCheckoutSuccess = jest.fn( ( callback ) => {
			checkoutSuccessResult = callback( {
				redirectUrl:
					'https://example.test/checkout/order-received/#wcpay-confirm-pi:123:pi_123_secret_abc:nonce_123',
			} );
		} );
		const emitResponse = {
			responseTypes: {
				SUCCESS: 'success',
				ERROR: 'error',
			},
			noticeContexts: {
				PAYMENTS: 'payments',
			},
		};

		const content = registration.content;

		render(
			createElement( content.type, {
				...content.props,
				eventRegistration: {
					onPaymentSetup: jest.fn(),
					onCheckoutSuccess,
				},
				emitResponse,
				shouldSavePayment: true,
			} )
		);

		await waitFor( () => {
			expect( onCheckoutSuccess ).toHaveBeenCalled();
		} );

		await expect( checkoutSuccessResult ).resolves.toEqual( {
			type: 'success',
			redirectUrl: 'https://example.test/checkout/order-received/123/',
			meta: {
				paymentMethodData: {},
			},
		} );
		expect( confirmPayment ).toHaveBeenCalledWith(
			expect.objectContaining( {
				clientSecret: 'pi_123_secret_abc',
				redirect: 'if_required',
			} )
		);
		expect( window.fetch ).toHaveBeenCalledWith(
			'https://example.test/wp-admin/admin-ajax.php',
			expect.objectContaining( {
				method: 'POST',
			} )
		);
		const requestBody = window.fetch.mock.calls[ 0 ][ 1 ].body;
		expect( requestBody.get( 'action' ) ).toBe( 'update_order_status' );
		expect( requestBody.get( 'order_id' ) ).toBe( '123' );
		expect( requestBody.get( '_ajax_nonce' ) ).toBe( 'nonce_123' );
		expect( requestBody.get( 'intent_id' ) ).toBe( 'pi_123' );
		expect( requestBody.get( 'should_save_payment_method' ) ).toBe(
			'true'
		);
	} );

	it( 'confirms full #wcpay-confirm-si redirects with confirmation tokens', async () => {
		const confirmSetup = jest.fn().mockResolvedValue( {
			setupIntent: {
				id: 'seti_123',
			},
		} );
		window.fetch = jest.fn().mockResolvedValue( {
			json: jest.fn().mockResolvedValue( {
				return_url: 'https://example.test/checkout/order-received/123/',
			} ),
		} );
		window.Stripe = jest.fn( () => ( {
			elements: jest.fn( () => ( {
				create: jest.fn( () => ( {
					mount: jest.fn(),
				} ) ),
			} ) ),
			createPaymentMethod: jest.fn().mockResolvedValue( {} ),
			confirmSetup,
		} ) );

		const registration = registerWooPayments();
		let checkoutSuccessResult;
		const onCheckoutSuccess = jest.fn( ( callback ) => {
			checkoutSuccessResult = callback( {
				redirectUrl:
					'https://example.test/checkout/order-received/#wcpay-confirm-si:123:seti_123_secret_abc:nonce_123:ctoken_123',
			} );
		} );
		const emitResponse = {
			responseTypes: {
				SUCCESS: 'success',
				ERROR: 'error',
			},
			noticeContexts: {
				PAYMENTS: 'payments',
			},
		};

		const content = registration.content;

		render(
			createElement( content.type, {
				...content.props,
				eventRegistration: {
					onPaymentSetup: jest.fn(),
					onCheckoutSuccess,
				},
				emitResponse,
			} )
		);

		await waitFor( () => {
			expect( onCheckoutSuccess ).toHaveBeenCalled();
		} );

		await expect( checkoutSuccessResult ).resolves.toEqual( {
			type: 'success',
			redirectUrl: 'https://example.test/checkout/order-received/123/',
			meta: {
				paymentMethodData: {},
			},
		} );
		expect( confirmSetup ).toHaveBeenCalledWith(
			expect.objectContaining( {
				clientSecret: 'seti_123_secret_abc',
				confirmParams: {
					confirmation_token: 'ctoken_123',
				},
				redirect: 'if_required',
			} )
		);
		const requestBody = window.fetch.mock.calls[ 0 ][ 1 ].body;
		expect( requestBody.get( 'action' ) ).toBe( 'update_order_status' );
		expect( requestBody.get( 'order_id' ) ).toBe( '123' );
		expect( requestBody.get( '_ajax_nonce' ) ).toBe( 'nonce_123' );
		expect( requestBody.get( 'intent_id' ) ).toBe( 'seti_123' );
		expect( requestBody.get( 'should_save_payment_method' ) ).toBe(
			'false'
		);
	} );

	it( 'handles SetupIntent next actions when no confirmation token is present', async () => {
		const handleNextAction = jest.fn().mockResolvedValue( {
			setupIntent: {
				id: 'seti_123',
			},
		} );
		window.fetch = jest.fn().mockResolvedValue( {
			json: jest.fn().mockResolvedValue( {
				return_url: 'https://example.test/checkout/order-received/123/',
			} ),
		} );
		window.Stripe = jest.fn( () => ( {
			elements: jest.fn( () => ( {
				create: jest.fn( () => ( {
					mount: jest.fn(),
				} ) ),
			} ) ),
			createPaymentMethod: jest.fn().mockResolvedValue( {} ),
			handleNextAction,
		} ) );

		const registration = registerWooPayments();
		let checkoutSuccessResult;
		const onCheckoutSuccess = jest.fn( ( callback ) => {
			checkoutSuccessResult = callback( {
				redirectUrl:
					'https://example.test/checkout/order-received/#wcpay-confirm-si:123:seti_123_secret_abc:nonce_123',
			} );
		} );
		const emitResponse = {
			responseTypes: {
				SUCCESS: 'success',
				ERROR: 'error',
			},
			noticeContexts: {
				PAYMENTS: 'payments',
			},
		};

		const content = registration.content;

		render(
			createElement( content.type, {
				...content.props,
				eventRegistration: {
					onPaymentSetup: jest.fn(),
					onCheckoutSuccess,
				},
				emitResponse,
			} )
		);

		await waitFor( () => {
			expect( onCheckoutSuccess ).toHaveBeenCalled();
		} );

		await expect( checkoutSuccessResult ).resolves.toEqual( {
			type: 'success',
			redirectUrl: 'https://example.test/checkout/order-received/123/',
			meta: {
				paymentMethodData: {},
			},
		} );
		expect( handleNextAction ).toHaveBeenCalledWith( {
			clientSecret: 'seti_123_secret_abc',
		} );
		const requestBody = window.fetch.mock.calls[ 0 ][ 1 ].body;
		expect( requestBody.get( 'action' ) ).toBe( 'update_order_status' );
		expect( requestBody.get( 'order_id' ) ).toBe( '123' );
		expect( requestBody.get( '_ajax_nonce' ) ).toBe( 'nonce_123' );
		expect( requestBody.get( 'intent_id' ) ).toBe( 'seti_123' );
	} );
} );
