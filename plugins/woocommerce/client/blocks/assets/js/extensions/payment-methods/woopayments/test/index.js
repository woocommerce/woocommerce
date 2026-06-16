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
	} ) ),
} ) );

describe( 'wc-payment-method-woopayments', () => {
	afterEach( () => {
		delete window.Stripe;
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

	it( 'confirms #wcpay-confirm-pi redirects through Stripe.js before returning success', async () => {
		const confirmPayment = jest.fn().mockResolvedValue( {} );
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
					'https://example.test/checkout/order-received/#wcpay-confirm-pi:pi_123_secret_abc',
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
	} );
} );
