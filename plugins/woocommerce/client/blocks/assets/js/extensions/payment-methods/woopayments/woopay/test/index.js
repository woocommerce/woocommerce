/* global globalThis */
/**
 * External dependencies
 */
import {
	act,
	fireEvent,
	render,
	screen,
	waitFor,
} from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { registerExpressPaymentMethod } from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import registerWooPay from '../index';

jest.mock( '@woocommerce/blocks-registry', () => ( {
	registerExpressPaymentMethod: jest.fn(),
} ) );

jest.mock( '@woocommerce/settings', () => {
	globalThis.__wooPayPaymentMethodSettings = {
		gatewayId: 'woocommerce_payments',
		forceNetworkSavedCards: true,
		initWooPayNonce: 'init-nonce',
		isCoreNativeCheckoutAvailable: true,
		isWoopayFirstPartyAuthEnabled: false,
		isWooPayEnabled: true,
		shouldShowWooPayButton: true,
		supports: [ 'products', 'subscriptions' ],
		platformTrackerNonce: 'tracks-nonce',
		isShopperTrackingEnabled: true,
		ajaxUrl: 'https://example.test/wp-admin/admin-ajax.php',
		wcAjaxUrl: '/?wc-ajax=%%endpoint%%',
		woopayButton: {
			type: 'default',
			theme: 'dark',
			height: '48',
			radius: '4',
			size: 'default',
			context: 'checkout',
		},
		woopayAppearance: {
			theme: 'stripe',
			labels: 'floating',
		},
		woopayFontRules: [
			{
				cssSrc: 'https://fonts.wp.com/font.css',
				family: 'Inter',
			},
		],
		woopayHost: 'https://pay.woo.test',
		woopayUserSession: 'qwerty123',
		woopaySessionNonce: 'session-nonce',
		woopayPhoneLabel: 'WooPay phone number',
		woopaySaveUserLabel: 'Save to WooPay',
		PRE_CHECK_SAVE_MY_INFO: true,
	};

	return {
		getPaymentMethodData: jest.fn(
			() => globalThis.__wooPayPaymentMethodSettings
		),
	};
} );

const getMockPaymentMethodSettings = () =>
	globalThis.__wooPayPaymentMethodSettings;

const originalFetch = window.fetch;
const originalLocation = window.location;

describe( 'wc-payment-method-woopayments-woopay', () => {
	afterEach( () => {
		jest.useRealTimers();
		window.fetch = originalFetch;
		window.localStorage.clear();
		document.body.innerHTML = '';
		Object.defineProperty( window, 'location', {
			configurable: true,
			writable: true,
			value: originalLocation,
		} );
		Object.assign( getMockPaymentMethodSettings(), {
			isWoopayFirstPartyAuthEnabled: false,
			woopayButton: {
				...getMockPaymentMethodSettings().woopayButton,
				context: 'checkout',
			},
		} );
		jest.clearAllMocks();
	} );

	it( 'registers a branded WooPay express button and initializes WooPay on click', async () => {
		document.body.innerHTML =
			'<input id="email" value="shopper@example.com" />';
		window.fetch = jest.fn().mockResolvedValue( {
			json: jest.fn().mockResolvedValue( {
				result: 'success',
				url: 'https://pay.woo.test/session',
			} ),
		} );

		registerWooPay();

		expect( registerExpressPaymentMethod ).toHaveBeenCalledWith(
			expect.objectContaining( {
				name: 'woopay',
				ariaLabel: 'WooPay',
				supports: {
					features: [ 'products', 'subscriptions' ],
				},
			} )
		);

		const expressRegistration =
			registerExpressPaymentMethod.mock.calls[ 0 ][ 0 ];

		render( createElement( expressRegistration.content.type ) );

		const button = screen.getByRole( 'button', {
			name: 'WooPay',
		} );
		expect( button ).toHaveClass( 'woopay-express-button' );
		expect( button ).toHaveAttribute( 'data-theme', 'dark' );
		expect( button ).toHaveAttribute( 'data-size', 'medium' );
		expect( button.querySelector( '.button-content' ) ).not.toBeNull();
		expect( button.querySelector( 'svg' ) ).not.toBeNull();

		fireEvent.click( button );

		await waitFor( () => {
			expect(
				window.fetch.mock.calls.some(
					( [ url ] ) => url === '/?wc-ajax=wcpay_init_woopay'
				)
			).toBe( true );
		} );

		const initRequest = window.fetch.mock.calls.find(
			( [ url ] ) => url === '/?wc-ajax=wcpay_init_woopay'
		);
		expect( initRequest[ 1 ] ).toEqual(
			expect.objectContaining( {
				method: 'POST',
			} )
		);
		const requestBody = initRequest[ 1 ].body;
		expect( requestBody.get( '_wpnonce' ) ).toBe( 'init-nonce' );
		expect( requestBody.get( 'email' ) ).toBe( 'shopper@example.com' );
		expect( requestBody.get( 'user_session' ) ).toBe( 'qwerty123' );
		expect( JSON.parse( requestBody.get( 'appearance' ) ) ).toEqual( {
			theme: 'stripe',
			labels: 'floating',
		} );
		expect( JSON.parse( requestBody.get( 'font_rules' ) ) ).toEqual( [
			{
				cssSrc: 'https://fonts.wp.com/font.css',
				family: 'Inter',
			},
		] );
	} );

	it( 'records WooPay express load and click events', async () => {
		window.fetch = jest.fn().mockResolvedValue( {
			json: jest.fn().mockResolvedValue( {
				result: 'success',
				url: 'https://pay.woo.test/session',
			} ),
		} );

		registerWooPay();
		const expressRegistration =
			registerExpressPaymentMethod.mock.calls[ 0 ][ 0 ];

		render( createElement( expressRegistration.content.type ) );

		await waitFor( () => {
			expect(
				window.fetch.mock.calls.some(
					( [ url, options ] ) =>
						url ===
							'https://example.test/wp-admin/admin-ajax.php' &&
						options.body.get( 'tracksEventName' ) ===
							'woopay_button_load'
				)
			).toBe( true );
		} );

		fireEvent.click( screen.getByRole( 'button', { name: 'WooPay' } ) );

		await waitFor( () => {
			const events = window.fetch.mock.calls
				.filter(
					( [ url, options ] ) =>
						url ===
							'https://example.test/wp-admin/admin-ajax.php' &&
						options.body.get( 'action' ) === 'platform_tracks'
				)
				.map( ( [ , options ] ) => ( {
					name: options.body.get( 'tracksEventName' ),
					props: JSON.parse( options.body.get( 'tracksEventProp' ) ),
				} ) );

			expect( events ).toEqual(
				expect.arrayContaining( [
					{
						name: 'woopay_button_load',
						props: { source: 'checkout' },
					},
					{
						name: 'woopay_button_click',
						props: { source: 'checkout' },
					},
				] )
			);
		} );
	} );

	it( 'does not render card save-user controls in the express button surface', () => {
		window.fetch = jest.fn().mockResolvedValue( {
			json: jest.fn().mockResolvedValue( {
				result: 'success',
			} ),
		} );

		registerWooPay();
		const expressRegistration =
			registerExpressPaymentMethod.mock.calls[ 0 ][ 0 ];

		render( createElement( expressRegistration.content.type ) );

		expect( screen.queryByLabelText( 'WooPay phone number' ) ).toBeNull();
		expect( screen.queryByLabelText( 'Save to WooPay' ) ).toBeNull();
		expect(
			document.querySelector( '.wcpay-core-woopay-save-user' )
		).toBeNull();
		expect(
			window.fetch.mock.calls.some(
				( [ url ] ) => url === '/?wc-ajax=wcpay_init_woopay'
			)
		).toBe( false );
	} );

	it( 'sends first-party WooPay session data through WooPay Connect before redirecting', async () => {
		const postMessage = jest.fn();
		Object.defineProperty(
			window.HTMLIFrameElement.prototype,
			'contentWindow',
			{
				configurable: true,
				get() {
					return {
						postMessage,
					};
				},
			}
		);
		getMockPaymentMethodSettings().isWoopayFirstPartyAuthEnabled = true;
		Object.defineProperty( window, 'location', {
			configurable: true,
			writable: true,
			value: {
				href: 'https://store.test/checkout/',
			},
		} );
		window.fetch = jest.fn().mockResolvedValue( {
			json: jest.fn().mockResolvedValue( {
				blog_id: '12345',
				data: {
					session: 'session',
					iv: 'iv',
					hash: 'hash',
				},
			} ),
		} );

		registerWooPay();
		const expressRegistration =
			registerExpressPaymentMethod.mock.calls[ 0 ][ 0 ];

		render( createElement( expressRegistration.content.type ) );

		fireEvent.click( screen.getByRole( 'link', { name: 'WooPay' } ) );
		await waitFor( () => {
			expect(
				window.fetch.mock.calls.some(
					( [ url ] ) => url === '/?wc-ajax=wcpay_get_woopay_session'
				)
			).toBe( true );
		} );
		await waitFor( () => {
			expect(
				document.getElementById( 'woopay-connect-iframe' )
			).not.toBeNull();
		} );
		document
			.getElementById( 'woopay-connect-iframe' )
			.dispatchEvent( new window.Event( 'load' ) );

		await waitFor( () => {
			expect( postMessage ).toHaveBeenCalledWith(
				{
					action: 'setPreemptiveSessionData',
					value: expect.objectContaining( {
						blog_id: '12345',
					} ),
				},
				'https://pay.woo.test'
			);
		} );
		window.dispatchEvent(
			new window.MessageEvent( 'message', {
				origin: 'https://pay.woo.test',
				data: {
					action: 'set_preemptive_session_data_success',
					value: {
						redirect_url: 'https://pay.woo.test/checkout/session',
					},
				},
			} )
		);
		await waitFor( () => {
			expect( window.location.href ).toBe(
				'https://pay.woo.test/checkout/session'
			);
		} );

		delete window.HTMLIFrameElement.prototype.contentWindow;
	} );

	it( 'falls back to the WooPay OTP flow when first-party Connect rejects the session', async () => {
		const postMessage = jest.fn();
		Object.defineProperty(
			window.HTMLIFrameElement.prototype,
			'contentWindow',
			{
				configurable: true,
				get() {
					return {
						postMessage,
					};
				},
			}
		);
		getMockPaymentMethodSettings().isWoopayFirstPartyAuthEnabled = true;
		window.fetch = jest.fn().mockImplementation( ( url ) =>
			Promise.resolve( {
				json: jest.fn().mockResolvedValue(
					url === '/?wc-ajax=wcpay_get_woopay_session'
						? {
								blog_id: '12345',
								data: {
									session: 'session',
									iv: 'iv',
									hash: 'hash',
								},
						  }
						: {
								result: 'success',
						  }
				),
			} )
		);

		registerWooPay();
		const expressRegistration =
			registerExpressPaymentMethod.mock.calls[ 0 ][ 0 ];

		render( createElement( expressRegistration.content.type ) );

		fireEvent.click( screen.getByRole( 'link', { name: 'WooPay' } ) );
		await waitFor( () => {
			expect(
				window.fetch.mock.calls.some(
					( [ url ] ) => url === '/?wc-ajax=wcpay_get_woopay_session'
				)
			).toBe( true );
		} );
		await waitFor( () => {
			expect(
				document.getElementById( 'woopay-connect-iframe' )
			).not.toBeNull();
		} );
		document
			.getElementById( 'woopay-connect-iframe' )
			.dispatchEvent( new window.Event( 'load' ) );
		await waitFor( () => {
			expect( postMessage ).toHaveBeenCalledWith(
				expect.objectContaining( {
					action: 'setPreemptiveSessionData',
				} ),
				'https://pay.woo.test'
			);
		} );
		window.dispatchEvent(
			new window.MessageEvent( 'message', {
				origin: 'https://pay.woo.test',
				data: {
					action: 'set_preemptive_session_data_error',
				},
			} )
		);

		await waitFor( () => {
			expect(
				window.fetch.mock.calls.some(
					( [ url ] ) => url === '/?wc-ajax=wcpay_init_woopay'
				)
			).toBe( true );
		} );

		delete window.HTMLIFrameElement.prototype.contentWindow;
	} );

	it( 'renders the cached preferred WooPay card on the express button', () => {
		window.localStorage.setItem(
			'woopay_preferred_card',
			JSON.stringify( {
				brand: 'visa',
				last4: '4242',
			} )
		);

		registerWooPay();
		const expressRegistration =
			registerExpressPaymentMethod.mock.calls[ 0 ][ 0 ];

		render( createElement( expressRegistration.content.type ) );

		const button = screen.getByRole( 'button', {
			name: 'WooPay with Visa ending in 4242',
		} );
		expect( button ).toHaveTextContent( '4242' );
	} );

	it( 'clears the cached preferred WooPay card when Connect does not respond', async () => {
		jest.useFakeTimers();
		const postMessage = jest.fn();
		Object.defineProperty(
			window.HTMLIFrameElement.prototype,
			'contentWindow',
			{
				configurable: true,
				get() {
					return {
						postMessage,
					};
				},
			}
		);
		window.localStorage.setItem(
			'woopay_preferred_card',
			JSON.stringify( {
				brand: 'visa',
				last4: '4242',
			} )
		);

		registerWooPay();
		const expressRegistration =
			registerExpressPaymentMethod.mock.calls[ 0 ][ 0 ];

		render( createElement( expressRegistration.content.type ) );

		expect(
			screen.getByRole( 'button', {
				name: 'WooPay with Visa ending in 4242',
			} )
		).toHaveTextContent( '4242' );
		await act( async () => {
			await Promise.resolve();
		} );
		document
			.getElementById( 'woopay-connect-iframe' )
			.dispatchEvent( new window.Event( 'load' ) );
		await Promise.resolve();

		await act( async () => {
			jest.advanceTimersByTime( 5000 );
			await Promise.resolve();
		} );

		expect( postMessage ).toHaveBeenCalledWith(
			{
				action: 'getPreferredPaymentMethod',
			},
			'https://pay.woo.test'
		);
		expect(
			window.localStorage.getItem( 'woopay_preferred_card' )
		).toBeNull();
		expect(
			screen.getByRole( 'button', { name: 'WooPay' } )
		).toBeVisible();

		delete window.HTMLIFrameElement.prototype.contentWindow;
	} );

	it( 'does not initialize WooPay from disabled product forms', async () => {
		document.body.innerHTML =
			'<form class="cart">' +
			'<input type="hidden" name="product_id" value="123" />' +
			'<button type="submit" class="single_add_to_cart_button disabled wc-variation-selection-needed" name="add-to-cart" value="123">Add to cart</button>' +
			'</form>';
		getMockPaymentMethodSettings().woopayButton = {
			...getMockPaymentMethodSettings().woopayButton,
			context: 'product',
		};
		window.fetch = jest.fn().mockResolvedValue( {
			json: jest.fn().mockResolvedValue( {
				result: 'success',
				url: 'https://pay.woo.test/session',
			} ),
		} );

		registerWooPay();
		const expressRegistration =
			registerExpressPaymentMethod.mock.calls[ 0 ][ 0 ];

		render( createElement( expressRegistration.content.type ) );

		await waitFor( () => {
			expect(
				window.fetch.mock.calls.some(
					( [ url, options ] ) =>
						url ===
							'https://example.test/wp-admin/admin-ajax.php' &&
						options.body.get( 'tracksEventName' ) ===
							'woopay_button_load'
				)
			).toBe( true );
		} );
		window.fetch.mockClear();

		fireEvent.click( screen.getByRole( 'button', { name: 'WooPay' } ) );
		await Promise.resolve();

		expect(
			window.fetch.mock.calls.some(
				( [ url ] ) => url === '/?wc-ajax=wcpay_init_woopay'
			)
		).toBe( false );
	} );
} );
