/**
 * External dependencies
 */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { registerExpressPaymentMethod } from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import registerWooPayments from '../index';
import { recordWooPaymentsUserEvent } from '../tracks';
import {
	getAppearance,
	getFieldStyles,
	normalizeAppearanceForStripe,
	normalizeAppearanceValueForStripe,
} from '../upe-styles';

jest.mock( '@woocommerce/blocks-registry', () => ( {
	registerPaymentMethod: jest.fn(),
	registerExpressPaymentMethod: jest.fn(),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getPaymentMethodData: jest.fn( () => ( {
		title: 'WooPayments',
		supports: [ 'products', 'subscriptions', 'multiple_subscriptions' ],
		gatewayId: 'woocommerce_payments',
		publishableKey: 'pk_test_123',
		accountId: 'acct_123',
		cartTotal: 0,
		stylesCacheVersion: 'styles-v1',
		currency: 'USD',
		forceNetworkSavedCards: true,
		isSavedCardsEnabled: false,
		initWooPayNonce: 'init-nonce',
		isCoreNativeCheckoutAvailable: true,
		isWooPayEnabled: true,
		shouldShowWooPayButton: true,
		testMode: true,
		platformTrackerNonce: 'tracks-nonce',
		isShopperTrackingEnabled: true,
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
		woopayUserSession: 'qwerty123',
		woopaySessionNonce: 'session-nonce',
		woopayPhoneLabel: 'WooPay phone number',
		woopaySaveUserLabel: 'Save to WooPay',
		PRE_CHECK_SAVE_MY_INFO: true,
		paymentMethodsConfig: {
			card: {
				title: 'Card',
				isReusable: true,
				showSaveOption: false,
				cardBrandIcons: [
					{
						id: 'visa',
						alt: 'Visa',
						src: 'https://example.test/visa.svg',
					},
					{
						id: 'mastercard',
						alt: 'Mastercard',
						src: 'https://example.test/mastercard.svg',
					},
					{
						id: 'amex',
						alt: 'American Express',
						src: 'https://example.test/amex.svg',
					},
					{
						id: 'discover',
						alt: 'Discover',
						src: 'https://example.test/discover.svg',
					},
					{
						id: 'jcb',
						alt: 'JCB',
						src: 'https://example.test/jcb.svg',
					},
					{
						id: 'unionpay',
						alt: 'Union Pay',
						src: 'https://example.test/unionpay.svg',
					},
				],
				testingInstructions:
					'Use test card <button type="button" class="js-woopayments-copy-test-number" aria-label="Click to copy the test number to clipboard" title="Copy to clipboard"><i></i><span>4242 4242 4242 4242</span></button> or refer to our <a href="https://woocommerce.com/document/woopayments/testing-and-troubleshooting/testing/#test-cards" target="_blank">testing guide</a>.',
			},
			link: {
				isReusable: false,
			},
		},
		usesLegacyOrderStatusBridge: false,
		ajaxUrl: 'https://example.test/wp-admin/admin-ajax.php',
	} ) ),
} ) );

const originalFetch = window.fetch;

describe( 'wc-payment-method-woopayments', () => {
	afterEach( () => {
		jest.useRealTimers();
		jest.restoreAllMocks();
		delete window.Stripe;
		delete window.navigator.clipboard;
		window.fetch = originalFetch;
		document.body.innerHTML = '';
		window.localStorage.clear();
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
					'wcpay-is-platform-payment-method': 'true',
				},
			},
		} );
	} );

	it( 'records a WooPayments place-order event when Blocks payment setup runs', async () => {
		window.fetch = jest.fn().mockResolvedValue( {
			json: jest.fn().mockResolvedValue( { success: true } ),
		} );
		const registration = registerWooPayments();
		let setupResult;
		const onPaymentSetup = jest.fn( ( callback ) => {
			setupResult = callback();
		} );
		const content = registration.content;

		render(
			createElement( content.type, {
				...content.props,
				eventRegistration: {
					onPaymentSetup,
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
			expect( onPaymentSetup ).toHaveBeenCalled();
		} );
		await setupResult;

		const trackingRequest = window.fetch.mock.calls.find(
			( [ url, options ] ) =>
				url === 'https://example.test/wp-admin/admin-ajax.php' &&
				options.body.get( 'action' ) === 'platform_tracks'
		);

		expect( trackingRequest ).toBeDefined();
		expect( trackingRequest[ 1 ].body.get( 'tracksNonce' ) ).toBe(
			'tracks-nonce'
		);
		expect( trackingRequest[ 1 ].body.get( 'tracksEventName' ) ).toBe(
			'checkout_place_order_button_click'
		);
		expect(
			JSON.parse( trackingRequest[ 1 ].body.get( 'tracksEventProp' ) )
		).toEqual( {} );
	} );

	it( 'does not record tracking events when shopper tracking is disabled', () => {
		window.fetch = jest.fn();

		recordWooPaymentsUserEvent(
			{
				ajaxUrl: 'https://example.test/wp-admin/admin-ajax.php',
				platformTrackerNonce: 'tracks-nonce',
				isShopperTrackingEnabled: false,
			},
			'checkout_place_order_button_click'
		);

		expect( window.fetch ).not.toHaveBeenCalled();
	} );

	it( 'honors backend saved-payment controls', () => {
		const registration = registerWooPayments();

		expect( registration.supports ).toEqual(
			expect.objectContaining( {
				features: expect.arrayContaining( [
					'products',
					'subscriptions',
					'multiple_subscriptions',
				] ),
				showSavedCards: false,
				showSaveOption: false,
			} )
		);
	} );

	it( 'shows the test mode badge in the payment method label', () => {
		const registration = registerWooPayments();
		const LabelComponent = registration.label.type;
		const PaymentMethodLabel = ( { text, icon } ) => (
			<span className="wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon">
				{ text }
				{ icon }
			</span>
		);

		render(
			createElement( LabelComponent, {
				components: {
					PaymentMethodLabel,
				},
			} )
		);

		expect( screen.getByText( 'Card' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Test Mode' ) ).toBeInTheDocument();
		expect( screen.getByAltText( 'Visa' ) ).toHaveAttribute(
			'src',
			'https://example.test/visa.svg'
		);
		expect( screen.getByAltText( 'Mastercard' ) ).toHaveAttribute(
			'src',
			'https://example.test/mastercard.svg'
		);
		expect( screen.getByText( '+ 2' ) ).toBeInTheDocument();
		expect(
			document.querySelector( '.payment-methods--logos' )
		).toBeInTheDocument();
		expect(
			document.querySelector( '.wcpay-core-card-brand-icons' )
		).not.toBeInTheDocument();
		expect( registration.ariaLabel ).toContain( 'Test Mode' );
	} );

	it( 'renders the WooPay save-my-info section after the Blocks payment step', async () => {
		document.body.innerHTML = `
			<div class="wc-block-checkout">
				<div class="wp-block-woocommerce-checkout-payment-block"></div>
			</div>
			<input id="billing-phone" value="5551234567" />
		`;

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
			expect(
				screen.getByRole( 'heading', { name: 'Save my info' } )
			).toBeInTheDocument();
		} );

		expect(
			document.querySelector( '#remember-me' )?.previousElementSibling
		).toHaveClass( 'wp-block-woocommerce-checkout-payment-block' );
		expect(
			screen.getByRole( 'checkbox', {
				name: 'Save to WooPay',
			} )
		).toBeChecked();
		expect(
			document.querySelector( 'input[name="woopay_viewport"]' )
		).toBeInTheDocument();
		expect(
			document.querySelector(
				'input[name="woopay_user_phone_field[full]"]'
			)
		).toHaveValue( '5551234567' );
	} );

	it( 'records WooPay save-info offer and checkbox events', async () => {
		window.fetch = jest.fn().mockResolvedValue( {
			json: jest.fn().mockResolvedValue( { success: true } ),
		} );
		document.body.innerHTML = `
			<div class="wc-block-checkout">
				<div class="wp-block-woocommerce-checkout-payment-block"></div>
			</div>
			<input id="billing-phone" value="5551234567" />
		`;

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
			expect(
				screen.getByRole( 'checkbox', { name: 'Save to WooPay' } )
			).toBeChecked();
		} );

		fireEvent.click(
			screen.getByRole( 'checkbox', { name: 'Save to WooPay' } )
		);

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
						name: 'checkout_woopay_save_my_info_offered',
						props: {},
					},
					{
						name: 'checkout_save_my_info_click',
						props: { status: 'checked' },
					},
					{
						name: 'checkout_save_my_info_click',
						props: { status: 'unchecked' },
					},
				] )
			);
		} );
	} );

	it( 'renders test card instructions while the account is in test mode', () => {
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

		expect( screen.getByText( /Use test card/ ) ).toBeInTheDocument();
		expect( screen.getByText( '4242 4242 4242 4242' ) ).toBeInTheDocument();
		expect( screen.getByText( /testing guide/ ) ).toBeInTheDocument();
	} );

	function renderWooPaymentsContent() {
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

		return screen.getByRole( 'button', {
			name: 'Click to copy the test number to clipboard',
		} );
	}

	it( 'prevents the default action when copying the test card number', () => {
		const writeText = jest.fn();
		Object.defineProperty( window.navigator, 'clipboard', {
			value: {
				writeText,
			},
			configurable: true,
		} );
		const button = renderWooPaymentsContent();
		const event = new window.MouseEvent( 'click', {
			bubbles: true,
			cancelable: true,
		} );

		button.dispatchEvent( event );

		expect( event.defaultPrevented ).toBe( true );
	} );

	it( 'copies the test card number with the Clipboard API', () => {
		const writeText = jest.fn();
		Object.defineProperty( window.navigator, 'clipboard', {
			value: {
				writeText,
			},
			configurable: true,
		} );
		const button = renderWooPaymentsContent();

		fireEvent.click( button );

		expect( writeText ).toHaveBeenCalledWith( '4242 4242 4242 4242' );
	} );

	it( 'shows the test card number in a prompt when the Clipboard API is unavailable', () => {
		const prompt = jest
			.spyOn( window, 'prompt' )
			.mockImplementation( () => null );
		const button = renderWooPaymentsContent();

		fireEvent.click( button );

		expect( prompt ).toHaveBeenCalledWith(
			'Copy test card number:',
			'4242 4242 4242 4242'
		);
	} );

	it( 'shows and clears the copied state after copying the test card number', () => {
		jest.useFakeTimers();
		const writeText = jest.fn();
		Object.defineProperty( window.navigator, 'clipboard', {
			value: {
				writeText,
			},
			configurable: true,
		} );
		const button = renderWooPaymentsContent();

		fireEvent.click( button );

		expect( button ).toHaveClass( 'state--success' );

		jest.advanceTimersByTime( 2000 );

		expect( button ).not.toHaveClass( 'state--success' );
	} );

	it( 'does not register WooPay express from the card payment method bundle', () => {
		registerWooPayments();

		expect( registerExpressPaymentMethod ).not.toHaveBeenCalled();
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
					'wcpay-is-platform-payment-method': 'true',
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
				loader: 'never',
				mode: 'setup',
				paymentMethodCreation: 'manual',
				paymentMethodTypes: [ 'card', 'link' ],
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
					link: 'auto',
				},
				terms: {
					card: 'never',
				},
			} )
		);
	} );

	it( 'initializes the card PaymentElement without a connected Stripe account when network saved cards are forced', async () => {
		const elements = jest.fn( () => ( {
			create: jest.fn( () => ( {
				mount: jest.fn(),
			} ) ),
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
			expect( window.Stripe ).toHaveBeenCalled();
		} );

		expect( window.Stripe ).toHaveBeenCalledWith( 'pk_test_123', {
			locale: 'auto',
		} );
		expect( window.Stripe.mock.calls[ 0 ][ 1 ] ).not.toHaveProperty(
			'stripeAccount'
		);
	} );

	it( 'initializes Stripe Elements with cached Blocks checkout appearance and font rules', async () => {
		const appearance = {
			theme: 'stripe',
			labels: 'floating',
			rules: {
				'.Input': {
					fontSize: '16px',
				},
			},
		};
		const create = jest.fn( () => ( {
			mount: jest.fn(),
		} ) );
		const elements = jest.fn( () => ( {
			create,
		} ) );
		const originalStyleSheets = document.styleSheets;
		Object.defineProperty( document, 'styleSheets', {
			configurable: true,
			value: [
				{
					href: 'https://fonts.wp.com/inter.css',
				},
				{
					href: 'https://example.test/theme.css',
				},
			],
		} );
		window.localStorage.setItem(
			'wcpay_appearance_blocks_checkout',
			JSON.stringify( {
				version: 'styles-v1',
				appearance,
			} )
		);
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
				appearance,
				fonts: [
					{
						cssSrc: 'https://fonts.wp.com/inter.css',
					},
				],
				loader: 'never',
			} )
		);

		Object.defineProperty( document, 'styleSheets', {
			configurable: true,
			value: originalStyleSheets,
		} );
	} );

	it( 'normalizes modern computed CSS colors before passing them to Stripe Elements', () => {
		expect(
			normalizeAppearanceValueForStripe(
				'1px solid color(srgb 0.168627 0.176471 0.184314 / 0.8)'
			)
		).toBe( '1px solid rgb(85, 87, 89)' );
		expect(
			normalizeAppearanceValueForStripe(
				'rgb(43 45 47 / 0.8) 0px 1px 2px'
			)
		).toBe( 'rgb(85, 87, 89) 0px 1px 2px' );
		expect(
			normalizeAppearanceForStripe( {
				rules: {
					'.Input': {
						borderColor:
							'color(srgb 0.168627 0.176471 0.184314 / 0.8)',
					},
				},
			} )
		).toEqual( {
			rules: {
				'.Input': {
					borderColor: 'rgb(85, 87, 89)',
				},
			},
		} );
	} );

	it( 'omits computed alpha color values from generated Stripe Elements appearance rules', () => {
		document.body.innerHTML = '<input id="wcpay-test-input" />';
		const originalGetComputedStyle = window.getComputedStyle;
		window.getComputedStyle = jest.fn( () => ( {
			getPropertyValue: ( property ) =>
				( {
					border: '1px solid color(srgb 0.168627 0.176471 0.184314 / 0.8)',
					'border-color':
						'color(srgb 0.168627 0.176471 0.184314 / 0.8)',
					'border-style': 'solid',
					'border-width': '1px',
					'box-shadow': 'rgb(43 45 47 / 0.8) 0px 1px 2px',
					color: 'rgb(43 45 47)',
					'font-size': '16px',
				}[ property ] || '' ),
		} ) );

		try {
			const rules = getFieldStyles( '#wcpay-test-input', '.Input' );

			expect( rules ).toMatchObject( {
				borderStyle: 'solid',
				borderWidth: '1px',
				color: 'rgb(43, 45, 47)',
				fontSize: '16px',
			} );
			expect( rules ).not.toHaveProperty( 'border' );
			expect( rules ).not.toHaveProperty( 'borderColor' );
			expect( rules ).not.toHaveProperty( 'boxShadow' );
		} finally {
			window.getComputedStyle = originalGetComputedStyle;
		}
	} );

	const makeBlocksAppearanceFixture = ( labelPosition = 'absolute' ) => {
		document.body.innerHTML = `
			<form class="wc-block-checkout__form">
				<div class="wc-block-checkout__contact-fields">
					<p class="wc-block-components-checkout-step__description">Pay with card.</p>
					<div class="wc-block-components-text-input is-active">
						<input id="email" value="shopper@example.test" />
						<label for="email">Email address</label>
					</div>
					<div class="wc-block-components-radio-control__label-group">Card</div>
				</div>
				<div id="payment-method" class="wc-block-components-radio-control-accordion-option"></div>
			</form>
		`;

		jest.spyOn( window, 'getComputedStyle' ).mockImplementation(
			( element ) => ( {
				getPropertyValue: ( property ) => {
					if (
						element.matches?.(
							'.wc-block-components-radio-control__label-group'
						)
					) {
						return (
							{
								'font-size': '12px',
								'background-color': 'rgba(0, 0, 0, 0)',
							}[ property ] || ''
						);
					}

					if ( element.tagName === 'LABEL' ) {
						return (
							{
								color: 'rgb(100, 105, 112)',
								'font-size': '10px',
								'line-height': '12px',
								position: labelPosition,
								transform: 'none',
							}[ property ] || ''
						);
					}

					if ( element.tagName === 'INPUT' ) {
						return (
							{
								color: 'rgb(29, 35, 39)',
								'font-size': '13px',
								'line-height': '18px',
								'padding-top': '10px',
								'padding-bottom': '10px',
							}[ property ] || ''
						);
					}

					return (
						{
							'background-color': 'rgb(255, 255, 255)',
							color: 'rgb(29, 35, 39)',
							'font-size': '13px',
						}[ property ] || ''
					);
				},
			} )
		);
	};

	it( 'keeps floating label padding compensation when the checkout label is positioned out of flow', () => {
		makeBlocksAppearanceFixture( 'absolute' );

		const appearance = getAppearance( 'blocks_checkout' );

		expect( appearance.labels ).toBe( 'floating' );
		expect( appearance.rules ).toHaveProperty( [ '.Label--floating' ] );
		expect( appearance.rules[ '.Input' ].paddingTop ).toBe(
			'calc(10px - 12px - 4px - 1px)'
		);
	} );

	it( 'uses above labels without padding compensation when the checkout label is static', () => {
		makeBlocksAppearanceFixture( 'static' );

		const appearance = getAppearance( 'blocks_checkout' );

		expect( appearance.labels ).toBe( 'above' );
		expect( appearance.rules ).not.toHaveProperty( [ '.Label--floating' ] );
		expect( appearance.rules[ '.Input' ].paddingTop ).toBe( '10px' );
		expect( appearance.rules[ '.Input' ].paddingBottom ).toBe( '10px' );
	} );

	it( 'does not clamp the PaymentElement base font size to the payment method label size', () => {
		makeBlocksAppearanceFixture( 'absolute' );

		const appearance = getAppearance( 'blocks_checkout' );

		expect( appearance.variables.fontSizeBase ).toBe( '13px' );
	} );

	it( 'shows reusable card terms when the shopper saves the payment method', async () => {
		const create = jest.fn( () => ( {
			mount: jest.fn(),
		} ) );
		window.Stripe = jest.fn( () => ( {
			elements: jest.fn( () => ( {
				create,
			} ) ),
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
				shouldSavePayment: true,
			} )
		);

		await waitFor( () => {
			expect( create ).toHaveBeenCalled();
		} );

		expect( create ).toHaveBeenCalledWith(
			'payment',
			expect.objectContaining( {
				terms: {
					card: 'always',
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

	it( 'returns a payment notice when Stripe does not provide a payment method', async () => {
		window.Stripe = jest.fn( () => ( {
			elements: jest.fn( () => ( {
				create: jest.fn( () => ( {
					mount: jest.fn(),
				} ) ),
			} ) ),
			createPaymentMethod: jest.fn().mockResolvedValue( {} ),
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
			message: 'There was a problem validating your payment details.',
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
		expect( window.Stripe ).toHaveBeenNthCalledWith( 1, 'pk_test_123', {
			locale: 'auto',
		} );
		expect( window.Stripe ).toHaveBeenNthCalledWith( 2, 'pk_test_123', {
			locale: 'auto',
			stripeAccount: 'acct_123',
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
