/**
 * External dependencies
 */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { registerExpressPaymentMethod } from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import registerWooPay from '../index';

jest.mock( '@woocommerce/blocks-registry', () => ( {
	registerExpressPaymentMethod: jest.fn(),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getPaymentMethodData: jest.fn( () => ( {
		gatewayId: 'woocommerce_payments',
		forceNetworkSavedCards: true,
		initWooPayNonce: 'init-nonce',
		isCoreNativeCheckoutAvailable: true,
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
	} ) ),
} ) );

const originalFetch = window.fetch;

describe( 'wc-payment-method-woopayments-woopay', () => {
	afterEach( () => {
		window.fetch = originalFetch;
		document.body.innerHTML = '';
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
} );
