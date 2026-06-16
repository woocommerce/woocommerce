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
		cartTotal: 0,
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
		document.body.innerHTML = '';
		jest.clearAllMocks();
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

	it( 'submits Stripe Elements before creating a payment method', async () => {
		const calls = [];
		const elementsInstance = {
			create: jest.fn( () => ( {
				mount: jest.fn(),
			} ) ),
			submit: jest.fn( () => {
				calls.push( 'submit' );
				return Promise.resolve( {} );
			} ),
		};
		const createPaymentMethod = jest.fn( () => {
			calls.push( 'createPaymentMethod' );
			return Promise.resolve( {
				paymentMethod: {
					id: 'pm_123',
					card: {
						fingerprint: 'fp_123',
					},
				},
			} );
		} );
		document.body.innerHTML = `
			<input id="email" value="customer@example.test" />
			<input id="billing-first_name" value="Ada" />
			<input id="billing-last_name" value="Lovelace" />
			<input id="billing-address_1" value="1 Test Street" />
			<input id="billing-address_2" value="Suite 2" />
			<input id="billing-city" value="London" />
			<input id="billing-state" value="" />
			<input id="billing-postcode" value=" SW1A 1AA " />
			<input id="billing-phone" value="07123456789" />
			<select id="billing-country">
				<option value="GB" selected>United Kingdom</option>
			</select>
		`;
		window.Stripe = jest.fn( () => ( {
			elements: jest.fn( () => elementsInstance ),
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

		await expect( setupResult ).resolves.toMatchObject( {
			type: 'success',
			meta: {
				paymentMethodData: {
					'wcpay-payment-method': 'pm_123',
					'wcpay-fingerprint': 'fp_123',
				},
			},
		} );
		expect( elementsInstance.submit ).toHaveBeenCalled();
		expect( createPaymentMethod ).toHaveBeenCalledWith( {
			elements: elementsInstance,
			params: {
				billing_details: {
					name: 'Ada Lovelace',
					email: 'customer@example.test',
					phone: '07123456789',
					address: {
						city: 'London',
						country: 'GB',
						line1: '1 Test Street',
						line2: 'Suite 2',
						postal_code: 'SW1A 1AA',
						state: '',
					},
				},
			},
		} );
		expect( calls ).toEqual( [ 'submit', 'createPaymentMethod' ] );
	} );

	it( 'initializes Stripe Elements in setup mode for zero-total checkouts', async () => {
		const create = jest.fn( () => ( {
			mount: jest.fn(),
		} ) );
		const elements = jest.fn( () => ( {
			create,
		} ) );
		window.Stripe = jest.fn( () => ( {
			elements,
			createPaymentMethod: jest.fn().mockResolvedValue( {} ),
		} ) );
		const registration = registerWooPayments();
		const content = registration.content;

		render(
			createElement( content.type, {
				...content.props,
				eventRegistration: {
					onPaymentSetup: jest.fn(),
					onCheckoutSuccess: jest.fn(),
				},
				emitResponse: {
					responseTypes: {
						SUCCESS: 'success',
						ERROR: 'error',
					},
					noticeContexts: {
						PAYMENTS: 'payments',
					},
				},
			} )
		);

		await waitFor( () => {
			expect( elements ).toHaveBeenCalled();
		} );

		expect( elements ).toHaveBeenCalledWith(
			expect.objectContaining( {
				currency: 'usd',
				mode: 'setup',
				paymentMethodCreation: 'manual',
				paymentMethodTypes: [ 'card' ],
			} )
		);
		expect( elements.mock.calls[ 0 ][ 0 ] ).not.toHaveProperty( 'amount' );
		expect( create ).toHaveBeenCalledWith(
			'payment',
			expect.objectContaining( {
				fields: {
					billingDetails: {
						name: 'never',
						email: 'never',
						phone: 'never',
						address: {
							country: 'never',
							line1: 'never',
							line2: 'never',
							city: 'never',
							state: 'never',
							postalCode: 'never',
						},
					},
				},
				wallets: {
					applePay: 'never',
					googlePay: 'never',
					link: 'never',
				},
			} )
		);
	} );

	it( 'keeps checkout event subscriptions stable across parent rerenders', async () => {
		const registration = registerWooPayments();
		const unsubscribePaymentSetup = jest.fn();
		const unsubscribeCheckoutSuccess = jest.fn();
		const onPaymentSetup = jest.fn( () => unsubscribePaymentSetup );
		const onCheckoutSuccess = jest.fn( () => unsubscribeCheckoutSuccess );
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
		const createContent = () =>
			createElement( content.type, {
				...content.props,
				eventRegistration: {
					onPaymentSetup,
					onCheckoutSuccess,
				},
				emitResponse: {
					responseTypes: {
						...emitResponse.responseTypes,
					},
					noticeContexts: {
						...emitResponse.noticeContexts,
					},
				},
			} );

		const { rerender } = render( createContent() );

		await waitFor( () => {
			expect( onPaymentSetup ).toHaveBeenCalledTimes( 1 );
		} );
		await waitFor( () => {
			expect( onCheckoutSuccess ).toHaveBeenCalledTimes( 1 );
		} );

		rerender( createContent() );

		expect( onPaymentSetup ).toHaveBeenCalledTimes( 1 );
		expect( onCheckoutSuccess ).toHaveBeenCalledTimes( 1 );
		expect( unsubscribePaymentSetup ).not.toHaveBeenCalled();
		expect( unsubscribeCheckoutSuccess ).not.toHaveBeenCalled();
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

	it( 'handles PaymentIntent next actions from Blocks payment details redirects', async () => {
		const handleNextAction = jest.fn().mockResolvedValue( {
			paymentIntent: {
				id: 'pi_123',
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
				processingResponse: {
					paymentDetails: {
						redirect:
							'#wcpay-confirm-pi:123:pi_123_secret_abc:nonce_123',
					},
				},
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
		expect( handleNextAction ).toHaveBeenCalledWith( {
			clientSecret: 'pi_123_secret_abc',
		} );
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
