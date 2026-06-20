/**
 * @jest-environment jest-fixed-jsdom
 */

describe( 'WooPayments WooPay checkout', () => {
	let bodyEventHandlers;
	const originalFetch = window.fetch;
	const originalLocation = window.location;

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
		jQueryMock.post = jest.fn( () => ( {
			done: jest.fn( ( callback ) => {
				callback( {
					result: 'success',
				} );
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
		document.body.innerHTML =
			'<form class="checkout">' +
			'<input id="billing_email" value="shopper@example.com" />' +
			'<input type="radio" name="payment_method" value="woocommerce_payments" checked />' +
			'<div id="wcpay-woopay-button"><div class="woopay-express-button is-placeholder"></div></div>' +
			'<p class="form-row place-order"></p>' +
			'</form>';

		const jQueryMock = createJQueryMock();
		global.jQuery = jQueryMock;
		global.$ = jQueryMock;
		window.jQuery = jQueryMock;
		window.$ = jQueryMock;
		window.wcpay_core_woopay_config = {
			ajaxUrl: 'https://example.test/admin-ajax.php',
			forceNetworkSavedCards: true,
			initWooPayNonce: 'init-nonce',
			isWooPayEnabled: true,
			isShopperTrackingEnabled: true,
			platformTrackerNonce: 'tracks-nonce',
			shouldShowWooPayButton: true,
			wcAjaxUrl: '/?wc-ajax=%%endpoint%%',
			woopayButton: {
				type: 'default',
				theme: 'dark',
				height: '48',
				radius: '4',
				size: 'default',
				context: 'checkout',
			},
			woopayUserSession: 'qwerty123',
			woopaySessionNonce: 'session-nonce',
			woopayPhoneLabel: 'Mobile phone number',
			woopaySaveUserLabel:
				'Securely save my information for 1-click checkout',
			PRE_CHECK_SAVE_MY_INFO: true,
		};
		window.fetch = jest.fn().mockResolvedValue( {
			json: jest.fn().mockResolvedValue( { success: true } ),
		} );
	} );

		afterEach( () => {
			jest.useRealTimers();
			delete global.jQuery;
			delete global.$;
			delete window.jQuery;
			delete window.$;
			delete window.wcpay_core_woopay_config;
			Object.defineProperty( window, 'location', {
				configurable: true,
				writable: true,
				value: originalLocation,
			} );
			window.localStorage.clear();
			window.fetch = originalFetch;
			document.body.innerHTML = '';
		} );

	test( 'renders a branded WooPay express button and initializes WooPay on click', async () => {
		require( '../woopayments-woopay' );

		const button = document.querySelector( '#wcpay-woopay-button button' );
		expect( button ).not.toBeNull();
		expect( button.classList.contains( 'woopay-express-button' ) ).toBe(
			true
		);
		expect( button.getAttribute( 'aria-label' ) ).toBe( 'WooPay' );
		expect( button.getAttribute( 'data-theme' ) ).toBe( 'dark' );
		expect( button.getAttribute( 'data-size' ) ).toBe( 'medium' );
		expect( button.querySelector( '.button-content' ) ).not.toBeNull();
		expect( button.querySelector( 'svg' ) ).not.toBeNull();
		expect(
			document.querySelector( '.woopay-express-button.is-placeholder' )
		).toBeNull();

		button.click();
		await flushPromises();

		expect( global.jQuery.post ).toHaveBeenCalledWith(
			'/?wc-ajax=wcpay_init_woopay',
			expect.objectContaining( {
				_wpnonce: 'init-nonce',
				email: 'shopper@example.com',
				user_session: 'qwerty123',
			} )
		);
	} );

	test( 'records WooPay express load and click events', async () => {
		require( '../woopayments-woopay' );

		document.querySelector( '#wcpay-woopay-button button' ).click();
		await flushPromises();

		expect( getTrackingEvents() ).toEqual(
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

	test( 'does not record WooPay tracking when shopper tracking is disabled', async () => {
		window.wcpay_core_woopay_config.isShopperTrackingEnabled = false;
		require( '../woopayments-woopay' );

		document.querySelector( '#wcpay-woopay-button button' ).click();
		await flushPromises();

		expect( getTrackingEvents() ).toEqual( [] );
	} );

	test( 'adds the selected product to the cart before product-page WooPay init', async () => {
		document.body.innerHTML =
			'<form class="cart">' +
			'<input type="hidden" name="product_id" value="123" />' +
			'<input type="number" name="quantity" value="2" />' +
			'<input type="text" name="addon-message" value="Gift" />' +
			'<button type="submit" class="single_add_to_cart_button" name="add-to-cart" value="123">Add to cart</button>' +
			'<div id="wcpay-woopay-button" data-product_page="1"><div class="woopay-express-button is-placeholder"></div></div>' +
			'</form>';
		window.wcpay_core_woopay_config.addToCartNonce = 'add-to-cart-nonce';
		window.wcpay_core_woopay_config.woopayButton.context = 'product';

		require( '../woopayments-woopay' );

		document.querySelector( '#wcpay-woopay-button button' ).click();
		await flushPromises();

		expect( global.jQuery.post ).toHaveBeenNthCalledWith(
			1,
			'/?wc-ajax=wcpay_add_to_cart',
			expect.objectContaining( {
				security: 'add-to-cart-nonce',
				product_id: '123',
				quantity: '2',
				'addon-message': 'Gift',
			} )
		);
			expect( global.jQuery.post ).toHaveBeenNthCalledWith(
				2,
				'/?wc-ajax=wcpay_init_woopay',
				expect.objectContaining( {
				_wpnonce: 'init-nonce',
				user_session: 'qwerty123',
				} )
			);
		} );

		test( 'sends first-party WooPay session data through WooPay Connect before redirecting', async () => {
			const postMessage = jest.fn();
			Object.defineProperty( window.HTMLIFrameElement.prototype, 'contentWindow', {
				configurable: true,
				get() {
					return {
						postMessage,
					};
				},
			} );
			Object.defineProperty( window, 'location', {
				configurable: true,
				writable: true,
				value: {
					href: 'https://store.test/checkout/',
				},
			} );
			window.wcpay_core_woopay_config.isWoopayFirstPartyAuthEnabled = true;
			window.wcpay_core_woopay_config.woopayHost = 'https://pay.woo.test';
			global.jQuery.post = jest.fn( ( url, data ) => ( {
				done: jest.fn( ( callback ) => {
					callback(
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
						);

					return {
						fail: jest.fn(),
					};
				} ),
				data,
			} ) );

			require( '../woopayments-woopay' );

			document.querySelector( '#wcpay-woopay-button a' ).click();
			await flushPromises();
			document
				.getElementById( 'woopay-connect-iframe' )
				.dispatchEvent( new window.Event( 'load' ) );
			await flushPromises();

			expect( global.jQuery.post ).toHaveBeenCalledWith(
				'/?wc-ajax=wcpay_get_woopay_session',
				expect.objectContaining( {
					_ajax_nonce: 'session-nonce',
				} )
			);
			expect( postMessage ).toHaveBeenCalledWith(
				{
					action: 'setPreemptiveSessionData',
					value: expect.objectContaining( {
						blog_id: '12345',
					} ),
				},
				'https://pay.woo.test'
			);
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
			await flushPromises();

			expect( window.location.href ).toBe(
				'https://pay.woo.test/checkout/session'
			);

			delete window.HTMLIFrameElement.prototype.contentWindow;
		} );

		test( 'falls back to the WooPay OTP flow when first-party Connect rejects the session', async () => {
			const postMessage = jest.fn();
			Object.defineProperty( window.HTMLIFrameElement.prototype, 'contentWindow', {
				configurable: true,
				get() {
					return {
						postMessage,
					};
				},
			} );
			window.wcpay_core_woopay_config.isWoopayFirstPartyAuthEnabled = true;
			window.wcpay_core_woopay_config.woopayHost = 'https://pay.woo.test';
			global.jQuery.post = jest.fn( ( url ) => ( {
				done: jest.fn( ( callback ) => {
					callback(
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
						);

					return {
						fail: jest.fn(),
					};
				} ),
			} ) );

			require( '../woopayments-woopay' );

			document.querySelector( '#wcpay-woopay-button a' ).click();
			await flushPromises();
			document
				.getElementById( 'woopay-connect-iframe' )
				.dispatchEvent( new window.Event( 'load' ) );
			await flushPromises();
			window.dispatchEvent(
				new window.MessageEvent( 'message', {
					origin: 'https://pay.woo.test',
					data: {
						action: 'set_preemptive_session_data_error',
					},
				} )
			);
			await flushPromises();

			expect(
				global.jQuery.post.mock.calls.some(
					( [ url ] ) => url === '/?wc-ajax=wcpay_init_woopay'
				)
			).toBe( true );

			delete window.HTMLIFrameElement.prototype.contentWindow;
		} );

		test( 'renders the cached preferred WooPay card on the express button', () => {
			window.localStorage.setItem(
				'woopay_preferred_card',
				JSON.stringify( {
					brand: 'visa',
					last4: '4242',
				} )
			);

			require( '../woopayments-woopay' );

			const button = document.querySelector( '#wcpay-woopay-button button' );
			expect( button.getAttribute( 'aria-label' ) ).toBe(
				'WooPay with Visa ending in 4242'
			);
			expect( button.textContent ).toContain( '4242' );
		} );

		test( 'clears the cached preferred WooPay card when Connect does not respond', async () => {
			jest.useFakeTimers();
			const postMessage = jest.fn();
			Object.defineProperty( window.HTMLIFrameElement.prototype, 'contentWindow', {
				configurable: true,
				get() {
					return {
						postMessage,
					};
				},
			} );
			window.wcpay_core_woopay_config.woopayHost = 'https://pay.woo.test';
			window.localStorage.setItem(
				'woopay_preferred_card',
				JSON.stringify( {
					brand: 'visa',
					last4: '4242',
				} )
			);

			require( '../woopayments-woopay' );

			expect(
				document
					.querySelector( '#wcpay-woopay-button button' )
					.getAttribute( 'aria-label' )
			).toBe( 'WooPay with Visa ending in 4242' );

			document
				.getElementById( 'woopay-connect-iframe' )
				.dispatchEvent( new window.Event( 'load' ) );
			await Promise.resolve();
			jest.advanceTimersByTime( 5000 );
			await Promise.resolve();

			expect( postMessage ).toHaveBeenCalledWith(
				{
					action: 'getPreferredPaymentMethod',
				},
				'https://pay.woo.test'
			);
			expect( window.localStorage.getItem( 'woopay_preferred_card' ) ).toBeNull();
			expect(
				document
					.querySelector( '#wcpay-woopay-button button' )
					.getAttribute( 'aria-label' )
			).toBe( 'WooPay' );

			delete window.HTMLIFrameElement.prototype.contentWindow;
		} );

		test( 'does not add disabled product forms to the cart before product-page WooPay init', async () => {
				document.body.innerHTML =
					'<form class="cart">' +
					'<input type="hidden" name="product_id" value="123" />' +
					'<button type="submit" class="single_add_to_cart_button disabled ' +
					'wc-variation-selection-needed" name="add-to-cart" value="123">Add to cart</button>' +
					'<div id="wcpay-woopay-button" data-product_page="1"><div class="woopay-express-button is-placeholder"></div></div>' +
					'<div id="wcpay-core-payment-errors" hidden></div>' +
					'</form>';
			window.wcpay_core_woopay_config.addToCartNonce = 'add-to-cart-nonce';
			window.wcpay_core_woopay_config.confirmationErrorMessage =
				'Choose product options before using WooPay.';
			window.wcpay_core_woopay_config.woopayButton.context = 'product';

			require( '../woopayments-woopay' );

			document.querySelector( '#wcpay-woopay-button button' ).click();
			await flushPromises();

			expect( global.jQuery.post ).not.toHaveBeenCalledWith(
				'/?wc-ajax=wcpay_add_to_cart',
				expect.anything()
			);
			expect(
				document.getElementById( 'wcpay-core-payment-errors' ).textContent
			).toBe( 'Choose product options before using WooPay.' );
		} );

		test( 'renders WooPay save-my-info fields and persists phone data on blur', async () => {
			require( '../woopayments-woopay' );

		const saveCheckbox = document.querySelector(
			'input[name="save_user_in_woopay"]'
		);
		const phoneField = document.querySelector(
			'input[name="woopay_user_phone_field[full]"]'
		);

		expect( saveCheckbox ).not.toBeNull();
		expect( saveCheckbox.checked ).toBe( true );
		expect( phoneField ).not.toBeNull();
		expect( saveCheckbox.closest( 'label' ).textContent ).toContain(
			'Securely save my information for 1-click checkout'
		);
		expect(
			document.querySelector(
				'label[for="woopay_user_phone_field_full"]'
			).textContent
		).toBe( 'Mobile phone number' );
		expect(
			document.querySelector( 'input[name="woopay_source_url"]' )
		).not.toBeNull();
		expect(
			document.querySelector( 'input[name="woopay_viewport"]' )
		).not.toBeNull();

		phoneField.value = '+15555550123';
		phoneField.dispatchEvent(
			new window.Event( 'blur', { bubbles: true, cancelable: true } )
		);
		await flushPromises();

		expect( global.jQuery.post ).toHaveBeenCalledWith(
			'/?wc-ajax=wcpay_set_woopay_phone_number',
			expect.objectContaining( {
				_wpnonce: 'session-nonce',
				save_user_in_woopay: 'true',
				woopay_is_blocks: 'false',
				woopay_user_phone_field: {
					full: '+15555550123',
				},
			} )
		);
	} );

	test( 'records WooPay save-info offer and checkbox events', () => {
		require( '../woopayments-woopay' );

		const saveCheckbox = document.querySelector(
			'input[name="save_user_in_woopay"]'
		);

		saveCheckbox.checked = false;
		saveCheckbox.dispatchEvent(
			new window.Event( 'change', { bubbles: true, cancelable: true } )
		);

		expect( getTrackingEvents() ).toEqual(
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
