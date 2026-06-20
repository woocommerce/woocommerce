/**
 * @jest-environment jest-fixed-jsdom
 */

describe( 'WooPayments checkout', () => {
	let bodyEventHandlers;
	let elementsMock;
	let mountPaymentElement;
	let paymentElementOptions;
	let stripeMock;
	let stripeElementsOptions;
	let submitElements;
	let unmountPaymentElement;
	let updatePaymentElement;
	const originalFetch = window.fetch;

	async function flushPromises() {
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
	}

	function getTrackingEvents() {
		return window.fetch.mock.calls
			.filter(
				( [ url, options ] ) =>
					url === 'https://example.test/admin-ajax.php' &&
					options.body.get( 'action' ) === 'platform_tracks'
			)
			.map( ( [ , options ] ) => ( {
				name: options.body.get( 'tracksEventName' ),
				props: JSON.parse( options.body.get( 'tracksEventProp' ) ),
			} ) );
	}

	function createJQueryMock() {
		const defaultResult = {
			length: 0,
			filter: jest.fn( () => defaultResult ),
			find: jest.fn( () => defaultResult ),
			on: jest.fn( () => defaultResult ),
			appendTo: jest.fn( () => defaultResult ),
			trigger: jest.fn( () => defaultResult ),
			val: jest.fn(),
		};
		const selectedGatewayResult = {
			length: 1,
			find: jest.fn( () => defaultResult ),
			on: jest.fn( () => selectedGatewayResult ),
			appendTo: jest.fn( () => selectedGatewayResult ),
			filter: jest.fn( () => selectedGatewayResult ),
			trigger: jest.fn( () => selectedGatewayResult ),
			val: jest.fn( () => 'woocommerce_payments' ),
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

			if (
				typeof selectorOrCallback === 'string' &&
				selectorOrCallback.indexOf( 'input[name="payment_method"]' ) !==
					-1
			) {
				return selectedGatewayResult;
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
		submitElements = jest.fn( () => Promise.resolve( {} ) );
		mountPaymentElement = jest.fn();
		paymentElementOptions = null;
		stripeElementsOptions = null;
		unmountPaymentElement = jest.fn();
		updatePaymentElement = jest.fn();
		document.body.innerHTML =
			'<form class="checkout">' +
			'<input type="radio" name="payment_method" value="woocommerce_payments" checked />' +
			'<div id="wcpay-core-payment-element"></div>' +
			'<button id="place_order" type="button">Place order</button>' +
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
			isShopperTrackingEnabled: true,
			locale: 'en-US',
			stylesCacheVersion: 'styles-v1',
			platformTrackerNonce: 'tracks-nonce',
			paymentMethodsConfig: {
				card: {
					isReusable: true,
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
				},
				link: {
					isReusable: false,
				},
			},
			publishableKey: 'pk_test',
			woopayPhoneLabel: 'Mobile phone number',
			woopaySaveUserLabel:
				'Securely save my information for 1-click checkout',
		};
		window.fetch = jest.fn().mockResolvedValue( {
			json: jest.fn().mockResolvedValue( { success: true } ),
		} );
		stripeMock = {
			elements: jest.fn( ( options ) => {
				stripeElementsOptions = options;
				elementsMock = {
					submit: submitElements,
					create: jest.fn( ( type, options ) => {
						paymentElementOptions = options;
						return {
							mount: mountPaymentElement,
							unmount: unmountPaymentElement,
							update: updatePaymentElement,
						};
					} ),
				};
				return elementsMock;
			} ),
			createPaymentMethod: jest.fn( () =>
				Promise.resolve( {
					paymentMethod: { id: 'pm_native' },
				} )
			),
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
		jest.useRealTimers();
		jest.restoreAllMocks();
		delete global.jQuery;
		delete global.$;
		delete window.jQuery;
		delete window.$;
		delete window.wcpay_core_checkout_config;
		delete window.Stripe;
		delete window.navigator.clipboard;
		window.fetch = originalFetch;
		window.localStorage.clear();
		document.body.innerHTML = '';
		window.location.hash = '';
	} );

	test( 'does not record place-order tracking when shopper tracking is disabled', () => {
		window.wcpay_core_checkout_config.isShopperTrackingEnabled = false;
		require( '../woopayments-checkout' );

		document.getElementById( 'place_order' ).dispatchEvent(
			new window.MouseEvent( 'click', { bubbles: true, cancelable: true } )
		);

		expect( getTrackingEvents() ).toEqual( [] );
	} );

	test( 'passes the checkout total to Stripe Elements as a number', () => {
		require( '../woopayments-checkout' );

		expect( stripeElementsOptions ).toMatchObject( {
			amount: 5000,
			currency: 'gbp',
			loader: 'never',
			mode: 'payment',
			paymentMethodCreation: 'manual',
			paymentMethodTypes: [ 'card', 'link' ],
		} );
	} );

	test( 'initializes classic Stripe Elements with cached appearance and font rules', () => {
		const appearance = {
			theme: 'stripe',
			labels: 'floating',
			rules: {
				'.Input': {
					fontSize: '16px',
				},
			},
		};
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
			'wcpay_appearance_classic_checkout',
			JSON.stringify( {
				version: 'styles-v1',
				appearance,
			} )
		);

		try {
			require( '../woopayments-checkout' );

			expect( stripeElementsOptions ).toMatchObject( {
				appearance,
				fonts: [
					{
						cssSrc: 'https://fonts.wp.com/inter.css',
					},
				],
				loader: 'never',
			} );
		} finally {
			Object.defineProperty( document, 'styleSheets', {
				configurable: true,
				value: originalStyleSheets,
			} );
		}
	} );

	test( 'omits computed alpha color values from generated classic Stripe Elements appearance rules', () => {
		document.body.innerHTML =
			'<form class="checkout">' +
			'<input type="radio" name="payment_method" value="woocommerce_payments" checked />' +
			'<p class="form-row"><label for="billing_first_name">First name</label><input id="billing_first_name" type="text" /></p>' +
			'<div id="wcpay-core-payment-element"></div>' +
			'<button id="place_order" type="button">Place order</button>' +
			'</form>';
		jest.spyOn( window, 'getComputedStyle' ).mockImplementation(
			( element ) => ( {
				getPropertyValue: ( property ) => {
					if ( element.id === 'billing_first_name' ) {
						return (
							{
								border:
									'1px solid color(srgb 0.168627 0.176471 0.184314 / 0.8)',
								'border-color':
									'color(srgb 0.168627 0.176471 0.184314 / 0.8)',
								'border-style': 'solid',
								'border-width': '1px',
								'box-shadow':
									'rgb(43 45 47 / 0.8) 0px 1px 2px',
								color: 'rgb(43 45 47)',
								'font-size': '16px',
							}[ property ] || ''
						);
					}

					return (
						{
							'background-color': 'rgb(255, 255, 255)',
							color: 'rgb(43 45 47)',
							'font-size': '16px',
						}[ property ] || ''
					);
				},
			} )
		);

		require( '../woopayments-checkout' );

		expect( stripeElementsOptions.appearance.rules[ '.Input' ] ).toMatchObject(
			{
				borderStyle: 'solid',
				borderWidth: '1px',
				color: 'rgb(43, 45, 47)',
				fontSize: '16px',
			}
		);
		expect(
			stripeElementsOptions.appearance.rules[ '.Input' ]
		).not.toHaveProperty( 'border' );
		expect(
			stripeElementsOptions.appearance.rules[ '.Input' ]
		).not.toHaveProperty( 'borderColor' );
		expect(
			stripeElementsOptions.appearance.rules[ '.Input' ]
		).not.toHaveProperty( 'boxShadow' );
	} );

	test( 'uses setup mode when the checkout total is zero', () => {
		window.wcpay_core_checkout_config.cartTotal = '0';

		require( '../woopayments-checkout' );

		expect( stripeElementsOptions ).toMatchObject( {
			currency: 'gbp',
			mode: 'setup',
			paymentMethodCreation: 'manual',
			paymentMethodTypes: [ 'card', 'link' ],
		} );
		expect( stripeElementsOptions ).not.toHaveProperty( 'amount' );
	} );

	test( 'passes card PaymentElement fields, wallets, and terms options', () => {
		require( '../woopayments-checkout' );

		expect( elementsMock.create ).toHaveBeenCalledWith(
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
		expect( paymentElementOptions ).toMatchObject( {
			wallets: {
				link: 'auto',
			},
			terms: {
				card: 'never',
			},
		} );
	} );

	test( 'uses setup mode on the add-payment-method form', () => {
		document.body.innerHTML =
			'<form id="add_payment_method">' +
			'<input type="radio" name="payment_method" value="woocommerce_payments" checked />' +
			'<div id="wcpay-core-payment-element"></div>' +
			'</form>';

		require( '../woopayments-checkout' );

		expect( stripeElementsOptions ).toMatchObject( {
			currency: 'gbp',
			mode: 'setup',
			paymentMethodCreation: 'manual',
			paymentMethodTypes: [ 'card', 'link' ],
		} );
		expect( stripeElementsOptions ).not.toHaveProperty( 'amount' );
		expect( paymentElementOptions ).toMatchObject( {
			wallets: {
				link: 'never',
			},
		} );
	} );

	test( 'updates reusable card terms when the save-payment checkbox changes', () => {
		document.body.innerHTML =
			'<form class="checkout">' +
			'<input type="radio" name="payment_method" value="woocommerce_payments" checked />' +
			'<input id="wc-woocommerce_payments-new-payment-method" type="checkbox" checked />' +
			'<div id="wcpay-core-payment-element"></div>' +
			'</form>';

		require( '../woopayments-checkout' );

		document
			.getElementById( 'wc-woocommerce_payments-new-payment-method' )
			.dispatchEvent(
				new window.Event( 'change', {
					bubbles: true,
					cancelable: true,
				} )
			);

		expect( updatePaymentElement ).toHaveBeenCalledWith( {
			terms: {
				card: 'always',
			},
		} );
	} );

	test( 'handles PaymentIntent confirmation hashes through next actions', async () => {
		window.location.hash =
			'#wcpay-confirm-pi:123:pi_native_secret_abc:nonce';

		require( '../woopayments-checkout' );

		await flushPromises();

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

	test( 'submits Stripe Elements before creating a checkout payment method', async () => {
		require( '../woopayments-checkout' );

		expect(
			bodyEventHandlers.checkout_place_order_woocommerce_payments()
		).toBe( false );

		await flushPromises();

		expect( submitElements ).toHaveBeenCalled();
		expect( stripeMock.createPaymentMethod ).toHaveBeenCalledWith( {
			elements: elementsMock,
		} );
		expect( submitElements.mock.invocationCallOrder[ 0 ] ).toBeLessThan(
			stripeMock.createPaymentMethod.mock.invocationCallOrder[ 0 ]
		);
	} );

	test( 'records a place-order event when the shopper clicks the classic checkout button', () => {
		require( '../woopayments-checkout' );

		document.getElementById( 'place_order' ).dispatchEvent(
			new window.MouseEvent( 'click', { bubbles: true, cancelable: true } )
		);

		expect( getTrackingEvents() ).toEqual(
			expect.arrayContaining( [
				{
					name: 'checkout_place_order_button_click',
					props: {},
				},
			] )
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

	function renderTestNumberButton() {
		document.body.innerHTML +=
			'<button type="button" class="js-woopayments-copy-test-number">' +
			'<i></i><span>4242 4242 4242 4242</span></button>';

		return document.querySelector( '.js-woopayments-copy-test-number' );
	}

	test( 'prevents the default action when copying the test card number', () => {
		const writeText = jest.fn();
		Object.defineProperty( window.navigator, 'clipboard', {
			value: {
				writeText,
			},
			configurable: true,
		} );
		const button = renderTestNumberButton();
		const event = new window.MouseEvent( 'click', {
			bubbles: true,
			cancelable: true,
		} );

		require( '../woopayments-checkout' );

		button.dispatchEvent( event );

		expect( event.defaultPrevented ).toBe( true );
	} );

	test( 'copies the test card number with the Clipboard API', () => {
		const writeText = jest.fn();
		Object.defineProperty( window.navigator, 'clipboard', {
			value: {
				writeText,
			},
			configurable: true,
		} );
		const button = renderTestNumberButton();

		require( '../woopayments-checkout' );

		button.click();

		expect( writeText ).toHaveBeenCalledWith( '4242 4242 4242 4242' );
	} );

	test( 'shows the test card number in a prompt when the Clipboard API is unavailable', () => {
		const prompt = jest
			.spyOn( window, 'prompt' )
			.mockImplementation( () => null );
		const button = renderTestNumberButton();

		require( '../woopayments-checkout' );

		button.click();

		expect( prompt ).toHaveBeenCalledWith(
			'Copy test card number:',
			'4242 4242 4242 4242'
		);
	} );

	test( 'shows and clears the copied state after copying the test card number', () => {
		jest.useFakeTimers();
		const writeText = jest.fn();
		Object.defineProperty( window.navigator, 'clipboard', {
			value: {
				writeText,
			},
			configurable: true,
		} );
		const button = renderTestNumberButton();

		require( '../woopayments-checkout' );

		button.click();

		expect( button.classList.contains( 'state--success' ) ).toBe( true );

		jest.advanceTimersByTime( 2000 );

		expect( button.classList.contains( 'state--success' ) ).toBe( false );
	} );

	test( 'hydrates card brand icons with a keyboard accessible popover', () => {
		document.body.innerHTML =
			'<form class="checkout">' +
			'<input id="payment_method_woocommerce_payments" type="radio" ' +
			'name="payment_method" value="woocommerce_payments" checked />' +
			'<label for="payment_method_woocommerce_payments">Card ' +
			'<span class="wcpay-core-card-brand-icons payment-methods--logos">' +
			'<img src="https://example.test/visa.svg" alt="Visa" />' +
			'</span></label>' +
			'<div id="wcpay-core-payment-element"></div>' +
			'</form>';

		require( '../woopayments-checkout' );

		const logos = document.querySelector(
			'[data-testid="payment-methods-logos"]'
		);

		expect( logos ).not.toBeNull();
		expect(
			Array.from( logos.querySelectorAll( 'img' ) ).map(
				( img ) => img.alt
			)
		).toEqual( [ 'Visa', 'Mastercard', 'American Express', 'Discover' ] );
		expect(
			logos.querySelector( '.payment-methods--logos-count' ).textContent
		).toBe( '+ 2' );
		expect( logos.getAttribute( 'aria-haspopup' ) ).toBe( 'dialog' );
		expect( logos.getAttribute( 'aria-label' ) ).toBe(
			'Show all supported credit card brands'
		);

		logos.dispatchEvent(
			new window.KeyboardEvent( 'keydown', {
				key: 'Enter',
				bubbles: true,
				cancelable: true,
			} )
		);

		const popover = document.querySelector( '.logo-popover' );

		expect( popover ).not.toBeNull();
		expect( popover.getAttribute( 'aria-label' ) ).toBe(
			'Supported credit card brands'
		);
		expect( popover.getAttribute( 'tabindex' ) ).toBe( '-1' );
		expect(
			document.getElementById( popover.getAttribute( 'aria-describedby' ) )
				.textContent
		).toBe( 'JCB, Union Pay' );
		expect( document.activeElement ).toBe( popover );
		expect(
			Array.from( popover.querySelectorAll( 'img' ) ).map(
				( img ) => img.alt
			)
		).toEqual( [ 'JCB', 'Union Pay' ] );

		document.dispatchEvent(
			new window.KeyboardEvent( 'keydown', {
				key: 'Escape',
				bubbles: true,
			} )
		);

		expect( document.querySelector( '.logo-popover' ) ).toBeNull();
		expect( document.activeElement ).toBe( logos );
	} );

	test( 'cleans up card brand logo resize handlers when checkout fragments replace payment markup', () => {
		const addEventListener = jest.spyOn( window, 'addEventListener' );
		const removeEventListener = jest.spyOn( window, 'removeEventListener' );
		document.body.innerHTML =
			'<form class="checkout">' +
			'<input id="payment_method_woocommerce_payments" type="radio" ' +
			'name="payment_method" value="woocommerce_payments" checked />' +
			'<label for="payment_method_woocommerce_payments">Card ' +
			'<span class="wcpay-core-card-brand-icons payment-methods--logos">' +
			'<img src="https://example.test/visa.svg" alt="Visa" />' +
			'</span></label>' +
			'<div id="wcpay-core-payment-element"></div>' +
			'</form>';

		require( '../woopayments-checkout' );

		const resizeHandlers = addEventListener.mock.calls
			.filter( ( [ eventName ] ) => eventName === 'resize' )
			.map( ( [ , handler ] ) => handler );

		expect( resizeHandlers ).toHaveLength( 1 );

		document.body.innerHTML =
			'<form class="checkout">' +
			'<input id="payment_method_woocommerce_payments" type="radio" ' +
			'name="payment_method" value="woocommerce_payments" checked />' +
			'<label for="payment_method_woocommerce_payments">Card ' +
			'<span class="wcpay-core-card-brand-icons payment-methods--logos">' +
			'<img src="https://example.test/visa.svg" alt="Visa" />' +
			'</span></label>' +
			'<div id="wcpay-core-payment-element"></div>' +
			'</form>';

		bodyEventHandlers.updated_checkout();

		expect( removeEventListener ).toHaveBeenCalledWith(
			'resize',
			resizeHandlers[ 0 ]
		);
		expect(
			addEventListener.mock.calls.filter(
				( [ eventName ] ) => eventName === 'resize'
			)
		).toHaveLength( 2 );
	} );

	test( 'does not render WooPay express markup from the card checkout bundle', () => {
		document.body.innerHTML =
			'<form class="checkout">' +
			'<input id="billing_email" value="shopper@example.com" />' +
			'<input type="radio" name="payment_method" value="woocommerce_payments" checked />' +
			'<div id="wcpay-core-payment-element"></div>' +
			'<div id="wcpay-woopay-button"><div class="woopay-express-button is-placeholder"></div></div>' +
			'</form>';

		require( '../woopayments-checkout' );

		expect( document.querySelector( '#wcpay-woopay-button button' ) ).toBeNull();
		expect(
			document.querySelector( '.woopay-express-button.is-placeholder' )
		).not.toBeNull();
	} );

	test( 'adds a setup intent field before submitting the add-payment-method form', async () => {
		const addPaymentMethodForm = document.createElement( 'form' );
		addPaymentMethodForm.id = 'add_payment_method';
		addPaymentMethodForm.submit = jest.fn();
		addPaymentMethodForm.innerHTML =
			'<input type="radio" name="payment_method" value="woocommerce_payments" checked />' +
			'<div id="wcpay-core-payment-element"></div>';
		document.body.innerHTML = '';
		document.body.appendChild( addPaymentMethodForm );
		window.wcpay_core_checkout_config.cartTotal = '0';
		window.wcpay_core_checkout_config.createSetupIntentNonce =
			'setup_nonce';
		global.jQuery.post.mockReturnValueOnce( {
			done: jest.fn( ( callback ) => {
				callback( {
					success: true,
					data: {
						id: 'seti_native',
						status: 'succeeded',
					},
				} );
				return {
					fail: jest.fn(),
				};
			} ),
		} );

		require( '../woopayments-checkout' );

		addPaymentMethodForm.dispatchEvent(
			new window.Event( 'submit', { bubbles: true, cancelable: true } )
		);

		await flushPromises();

		const setupIntentField = addPaymentMethodForm.querySelector(
			'input[name="wcpay-setup-intent"]'
		);

		expect( global.jQuery.post ).toHaveBeenCalledWith(
			'https://example.test/admin-ajax.php',
			expect.objectContaining( {
				action: 'create_setup_intent',
				_ajax_nonce: 'setup_nonce',
				'wcpay-payment-method': expect.any( String ),
			} )
		);
		expect( submitElements ).toHaveBeenCalled();
		expect( submitElements.mock.invocationCallOrder[ 0 ] ).toBeLessThan(
			stripeMock.createPaymentMethod.mock.invocationCallOrder[ 0 ]
		);
		expect( stripeMock.confirmSetup ).not.toHaveBeenCalled();
		expect( setupIntentField ).not.toBeNull();
		expect( setupIntentField.value ).toBe( 'seti_native' );
		expect( addPaymentMethodForm.submit ).toHaveBeenCalled();
	} );

	test( 'preserves add-payment-method payment method errors', async () => {
		const addPaymentMethodForm = document.createElement( 'form' );
		addPaymentMethodForm.id = 'add_payment_method';
		addPaymentMethodForm.submit = jest.fn();
		addPaymentMethodForm.innerHTML =
			'<input type="radio" name="payment_method" value="woocommerce_payments" checked />' +
			'<div id="wcpay-core-payment-element"></div>' +
			'<div id="wcpay-core-payment-errors" hidden></div>';
		document.body.innerHTML = '';
		document.body.appendChild( addPaymentMethodForm );
		window.wcpay_core_checkout_config.cartTotal = '0';
		window.wcpay_core_checkout_config.confirmationErrorMessage =
			'Unable to add payment method.';
		stripeMock.createPaymentMethod.mockResolvedValueOnce( {
			error: {
				message: 'Your card number is incomplete.',
			},
		} );

		require( '../woopayments-checkout' );

		addPaymentMethodForm.dispatchEvent(
			new window.Event( 'submit', { bubbles: true, cancelable: true } )
		);

		await flushPromises();

		expect(
			document.getElementById( 'wcpay-core-payment-errors' ).textContent
		).toBe( 'Your card number is incomplete.' );
		expect( global.jQuery.post ).not.toHaveBeenCalled();
		expect( addPaymentMethodForm.submit ).not.toHaveBeenCalled();
	} );
} );
