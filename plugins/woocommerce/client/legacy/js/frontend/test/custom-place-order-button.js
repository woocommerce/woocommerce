import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';

/**
 * @vitest-environment jsdom
 */

describe( 'Custom Place Order Button API', () => {
	let jQueryMock;
	let $form;
	let $placeOrderButton;
	beforeEach( async () => {
		// Resetting the window object
		delete global.window.wc;

		// creating some mocked DOM elements
		$form = {
			length: 1,
			first: vi.fn( () => $form ),
			find: vi.fn( () => ( {
				length: 1,
				val: vi.fn( () => 'test-gateway' ),
				after: vi.fn(),
			} ) ),
			addClass: vi.fn( () => $form ),
			removeClass: vi.fn( () => $form ),
		};
		$placeOrderButton = {
			length: 1,
			after: vi.fn(),
		};
		jQueryMock = vi.fn( ( selector ) => {
			if ( selector === 'form.checkout' ) {
				return {
					length: 1,
					first: vi.fn( () => $form ),
				};
			}
			if ( selector === '#order_review' ) {
				return {
					length: 0,
				};
			}
			if ( selector === '#add_payment_method' ) {
				return {
					length: 0,
				};
			}
			if ( typeof selector === 'string' && selector.includes( 'div' ) ) {
				return {
					length: 1,
					get: vi.fn( () => document.createElement( 'div' ) ),
					empty: vi.fn(),
					remove: vi.fn(),
					append: vi.fn(),
				};
			}
			return {
				length: 0,
			};
		} );
		jQueryMock.contains = vi.fn( () => false );
		global.window.jQuery = jQueryMock;
		global.window.$ = jQueryMock;
		global.window.wc_checkout_params = {
			gateways_with_custom_place_order_button: [ 'test-gateway' ],
		};

		// mocking the event triggering on document.body
		jQueryMock.fn = {};
		const mockBody = {
			trigger: vi.fn(),
		};
		jQueryMock.mockImplementation( ( selector ) => {
			if ( selector === document.body ) {
				return mockBody;
			}
			if ( selector === 'form.checkout' ) {
				return {
					length: 1,
					first: vi.fn( () => $form ),
				};
			}
			if ( selector === '#order_review' ) {
				return {
					length: 0,
				};
			}
			if ( selector === '#add_payment_method' ) {
				return {
					length: 0,
				};
			}
			return {
				length: 0,
			};
		} );

		// using a fresh instance on each test
		vi.resetModules();
		await import( '../utils/custom-place-order-button' );
	} );
	afterEach( () => {
		vi.clearAllMocks();
	} );
	describe( 'Base tests', () => {
		test( 'should expose the API', () => {
			expect( window.wc ).toBeDefined();
			expect( window.wc.customPlaceOrderButton ).toBeDefined();
			expect( typeof window.wc.customPlaceOrderButton.register ).toBe(
				'function'
			);
			expect( typeof window.wc.customPlaceOrderButton.__maybeShow ).toBe(
				'function'
			);
			expect(
				typeof window.wc.customPlaceOrderButton
					.__maybeHideDefaultButtonOnInit
			).toBe( 'function' );
			expect( typeof window.wc.customPlaceOrderButton.__cleanup ).toBe(
				'function'
			);
			expect( typeof window.wc.customPlaceOrderButton.__getForm ).toBe(
				'function'
			);
		} );
		test( 'should reject registration without proper configuration', () => {
			const consoleSpy = vi
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			window.wc.customPlaceOrderButton.register( 'test-gateway', {
				cleanup: vi.fn(),
			} );
			expect( consoleSpy ).toHaveBeenLastCalledWith(
				'wc.customPlaceOrderButton.register: render must be a function'
			);
			window.wc.customPlaceOrderButton.register( 'test-gateway', {
				render: vi.fn(),
			} );
			expect( consoleSpy ).toHaveBeenLastCalledWith(
				'wc.customPlaceOrderButton.register: cleanup must be a function'
			);
			window.wc.customPlaceOrderButton.register( null, {
				render: vi.fn(),
				cleanup: vi.fn(),
			} );
			expect( consoleSpy ).toHaveBeenLastCalledWith(
				'wc.customPlaceOrderButton.register: gatewayId must be a non-empty string'
			);
			window.wc.customPlaceOrderButton.register( '', {
				render: vi.fn(),
				cleanup: vi.fn(),
			} );
			expect( consoleSpy ).toHaveBeenLastCalledWith(
				'wc.customPlaceOrderButton.register: gatewayId must be a non-empty string'
			);
			window.wc.customPlaceOrderButton.register( 'test-gateway', null );
			expect( consoleSpy ).toHaveBeenLastCalledWith(
				'wc.customPlaceOrderButton.register: config must be an object'
			);
			window.wc.customPlaceOrderButton.register(
				'test-gateway',
				undefined
			);
			expect( consoleSpy ).toHaveBeenLastCalledWith(
				'wc.customPlaceOrderButton.register: config must be an object'
			);
			window.wc.customPlaceOrderButton.register(
				'test-gateway',
				'not-an-object'
			);
			expect( consoleSpy ).toHaveBeenLastCalledWith(
				'wc.customPlaceOrderButton.register: config must be an object'
			);
			consoleSpy.mockRestore();
		} );
		test( 'should inject critical CSS on load', () => {
			const styleElement = document.getElementById(
				'wc-custom-place-order-button-styles'
			);
			expect( styleElement ).toBeTruthy();
			expect( styleElement.textContent ).toContain(
				'.has-custom-place-order-button #place_order'
			);
			expect( styleElement.textContent ).toContain( 'display: none' );
		} );
		test( 'should not inject duplicate styles', async () => {
			// Re-require the module
			vi.resetModules();
			await import( '../utils/custom-place-order-button' );
			const styleElements = document.querySelectorAll(
				'#wc-custom-place-order-button-styles'
			);
			expect( styleElements.length ).toBe( 1 );
		} );
	} );
	describe( 'getGatewaysWithCustomButton', () => {
		test( 'should hide default button for gateway in wc_checkout_params list', () => {
			// Gateway 'test-gateway' is in the server list, so maybeHideDefaultButtonOnInit
			// should add the class to hide the default button
			window.wc.customPlaceOrderButton.__maybeHideDefaultButtonOnInit(
				'test-gateway'
			);
			expect( $form.addClass ).toHaveBeenCalledWith(
				'has-custom-place-order-button'
			);
		} );
		test( 'should not hide default button for gateway not in list', () => {
			// Gateway 'unknown-gateway' is NOT in the server list
			window.wc.customPlaceOrderButton.__maybeHideDefaultButtonOnInit(
				'unknown-gateway'
			);
			expect( $form.addClass ).not.toHaveBeenCalled();
		} );
		test( 'should not hide default button when wc_checkout_params is undefined', async () => {
			delete global.window.wc_checkout_params;
			delete global.window.wc_add_payment_method_params;
			vi.resetModules();
			await import( '../utils/custom-place-order-button' );
			window.wc.customPlaceOrderButton.__maybeHideDefaultButtonOnInit(
				'test-gateway'
			);
			expect( $form.addClass ).not.toHaveBeenCalled();
		} );
		test( 'should use wc_add_payment_method_params as fallback', async () => {
			delete global.window.wc_checkout_params;
			global.window.wc_add_payment_method_params = {
				gateways_with_custom_place_order_button: [
					'add-method-gateway',
				],
			};
			vi.resetModules();
			await import( '../utils/custom-place-order-button' );
			window.wc.customPlaceOrderButton.__maybeHideDefaultButtonOnInit(
				'add-method-gateway'
			);
			expect( $form.addClass ).toHaveBeenCalledWith(
				'has-custom-place-order-button'
			);
		} );
		test( 'should prefer wc_checkout_params over wc_add_payment_method_params', async () => {
			global.window.wc_checkout_params = {
				gateways_with_custom_place_order_button: [ 'checkout-gateway' ],
			};
			global.window.wc_add_payment_method_params = {
				gateways_with_custom_place_order_button: [
					'add-method-gateway',
				],
			};
			vi.resetModules();
			await import( '../utils/custom-place-order-button' );
			window.wc.customPlaceOrderButton.__maybeHideDefaultButtonOnInit(
				'checkout-gateway'
			);
			expect( $form.addClass ).toHaveBeenCalledWith(
				'has-custom-place-order-button'
			);
			$form.addClass.mockClear();
			window.wc.customPlaceOrderButton.__maybeHideDefaultButtonOnInit(
				'add-method-gateway'
			);
			expect( $form.addClass ).not.toHaveBeenCalled();
		} );
	} );
	describe( 'Gateway switching behavior', () => {
		let $form;
		let selectedGateway;
		let mockContainer;
		let mockApi;
		beforeEach( async () => {
			delete global.window.wc;
			selectedGateway = 'gateway-a';
			mockApi = {
				validate: vi.fn(),
				submit: vi.fn(),
			};
			$form = {
				length: 1,
				first: vi.fn( function () {
					return this;
				} ),
				find: vi.fn( ( selector ) => {
					if ( selector === 'input[name="payment_method"]:checked' ) {
						return {
							length: 1,
							val: vi.fn( () => selectedGateway ),
						};
					}
					if ( selector === '#place_order' ) {
						return {
							length: 1,
							after: vi.fn(),
						};
					}
					return {
						length: 0,
					};
				} ),
				addClass: vi.fn( function () {
					return this;
				} ),
				removeClass: vi.fn( function () {
					return this;
				} ),
			};
			mockContainer = {
				length: 1,
				get: vi.fn( () => document.createElement( 'div' ) ),
				empty: vi.fn(),
				remove: vi.fn(),
				append: vi.fn(),
			};
			const mockBody = {
				trigger: vi.fn(),
			};
			global.window.jQuery = vi.fn( ( selector ) => {
				if ( selector === document.body ) {
					return mockBody;
				}
				if ( selector === 'form.checkout' ) {
					return {
						length: 1,
						first: vi.fn( () => $form ),
					};
				}
				if ( selector === '#order_review' ) {
					return {
						length: 0,
					};
				}
				if ( selector === '#add_payment_method' ) {
					return {
						length: 0,
					};
				}
				if (
					typeof selector === 'string' &&
					selector.includes( 'div' )
				) {
					return mockContainer;
				}
				return {
					length: 0,
				};
			} );
			global.window.jQuery.fn = {};
			global.window.jQuery.contains = vi.fn( () => true );
			global.window.$ = global.window.jQuery;
			global.window.wc_checkout_params = {
				gateways_with_custom_place_order_button: [
					'gateway-a',
					'gateway-b',
				],
			};
			vi.resetModules();
			await import( '../utils/custom-place-order-button' );
		} );
		afterEach( () => {
			vi.clearAllMocks();
		} );
		test( 'should call cleanup when switching between two gateways with custom buttons', () => {
			const renderA = vi.fn();
			const cleanupA = vi.fn();
			const renderB = vi.fn();
			const cleanupB = vi.fn();
			window.wc.customPlaceOrderButton.register( 'gateway-a', {
				render: renderA,
				cleanup: cleanupA,
			} );
			window.wc.customPlaceOrderButton.register( 'gateway-b', {
				render: renderB,
				cleanup: cleanupB,
			} );

			// Simulating to select `gateway-a`
			selectedGateway = 'gateway-a';
			window.wc.customPlaceOrderButton.__maybeShow(
				selectedGateway,
				mockApi
			);
			expect( renderA ).toHaveBeenCalledTimes( 1 );
			expect( cleanupA ).not.toHaveBeenCalled();
			expect( $form.addClass ).toHaveBeenCalledWith(
				'has-custom-place-order-button'
			);

			// Simulating to switch to `gateway-b`
			selectedGateway = 'gateway-b';
			window.wc.customPlaceOrderButton.__maybeShow(
				selectedGateway,
				mockApi
			);
			expect( cleanupA ).toHaveBeenCalledTimes( 1 );
			expect( renderB ).toHaveBeenCalledTimes( 1 );
			expect( cleanupB ).not.toHaveBeenCalled();
		} );
		test( 'should call cleanup when switching from custom button gateway to regular gateway', () => {
			const renderA = vi.fn();
			const cleanupA = vi.fn();
			window.wc.customPlaceOrderButton.register( 'gateway-a', {
				render: renderA,
				cleanup: cleanupA,
			} );

			// Simulating to selecting `gateway-a` (which has a custom button)
			selectedGateway = 'gateway-a';
			window.wc.customPlaceOrderButton.__maybeShow(
				selectedGateway,
				mockApi
			);
			expect( renderA ).toHaveBeenCalledTimes( 1 );
			expect( $form.addClass ).toHaveBeenCalledWith(
				'has-custom-place-order-button'
			);

			// Reset mocks to track new calls
			$form.addClass.mockClear();
			$form.removeClass.mockClear();

			// Simulating to switch to `no-custom-button-gateway`
			selectedGateway = 'no-custom-button-gateway';
			window.wc.customPlaceOrderButton.__maybeShow(
				selectedGateway,
				mockApi
			);
			expect( cleanupA ).toHaveBeenCalledTimes( 1 );
			expect( $form.removeClass ).toHaveBeenCalledWith(
				'has-custom-place-order-button'
			);
		} );
		test( 'should show custom button when switching from regular gateway to custom button gateway', () => {
			const renderA = vi.fn();
			const cleanupA = vi.fn();
			window.wc.customPlaceOrderButton.register( 'gateway-a', {
				render: renderA,
				cleanup: cleanupA,
			} );

			// Starting with `no-custom-button-gateway`
			selectedGateway = 'no-custom-button-gateway';
			window.wc.customPlaceOrderButton.__maybeShow(
				selectedGateway,
				mockApi
			);
			expect( renderA ).not.toHaveBeenCalled();
			expect( $form.addClass ).not.toHaveBeenCalledWith(
				'has-custom-place-order-button'
			);

			// Simulating to switch to `gateway-a` (which has custom button)
			selectedGateway = 'gateway-a';
			window.wc.customPlaceOrderButton.__maybeShow(
				selectedGateway,
				mockApi
			);
			expect( renderA ).toHaveBeenCalledTimes( 1 );
			expect( $form.addClass ).toHaveBeenCalledWith(
				'has-custom-place-order-button'
			);
		} );
	} );
} );
describe( 'getForm helper', () => {
	beforeEach( async () => {
		delete global.window.wc;

		// Default mock - no forms found
		global.window.jQuery = vi.fn( () => {
			return {
				length: 0,
			};
		} );
		global.window.wc_checkout_params = {
			gateways_with_custom_place_order_button: [],
		};
		vi.resetModules();
		await import( '../utils/custom-place-order-button' );
	} );
	test( 'should return form.checkout if present', async () => {
		const mockForm = {
			length: 1,
			first: vi.fn( () => mockForm ),
		};
		global.window.jQuery = vi.fn( ( selector ) => {
			if ( selector === 'form.checkout' ) {
				return mockForm;
			}
			return {
				length: 0,
			};
		} );
		vi.resetModules();
		await import( '../utils/custom-place-order-button' );
		const form = window.wc.customPlaceOrderButton.__getForm();
		expect( form ).toBe( mockForm );
	} );
	test( 'should return #order_review if form.checkout not present', async () => {
		const mockOrderReview = {
			length: 1,
			first: vi.fn( () => mockOrderReview ),
		};
		global.window.jQuery = vi.fn( ( selector ) => {
			if ( selector === 'form.checkout' ) {
				return {
					length: 0,
				};
			}
			if ( selector === '#order_review' ) {
				return mockOrderReview;
			}
			return {
				length: 0,
			};
		} );
		vi.resetModules();
		await import( '../utils/custom-place-order-button' );
		const form = window.wc.customPlaceOrderButton.__getForm();
		expect( form ).toBe( mockOrderReview );
	} );
	test( 'should return #add_payment_method as last resort', async () => {
		const mockAddPaymentMethod = {
			length: 1,
			first: vi.fn( () => mockAddPaymentMethod ),
		};
		global.window.jQuery = vi.fn( ( selector ) => {
			if ( selector === 'form.checkout' ) {
				return {
					length: 0,
				};
			}
			if ( selector === '#order_review' ) {
				return {
					length: 0,
				};
			}
			if ( selector === '#add_payment_method' ) {
				return mockAddPaymentMethod;
			}
			return {
				length: 0,
			};
		} );
		vi.resetModules();
		await import( '../utils/custom-place-order-button' );
		const form = window.wc.customPlaceOrderButton.__getForm();
		expect( form ).toBe( mockAddPaymentMethod );
	} );
	test( 'should return empty jQuery object if no form found', async () => {
		const emptyJQuery = {
			length: 0,
		};
		global.window.jQuery = vi.fn( ( selector ) => {
			if ( selector === 'form.checkout' ) {
				return {
					length: 0,
				};
			}
			if ( selector === '#order_review' ) {
				return {
					length: 0,
				};
			}
			if ( selector === '#add_payment_method' ) {
				return {
					length: 0,
				};
			}
			if ( Array.isArray( selector ) && selector.length === 0 ) {
				return emptyJQuery;
			}
			return {
				length: 0,
			};
		} );
		vi.resetModules();
		await import( '../utils/custom-place-order-button' );
		const form = window.wc.customPlaceOrderButton.__getForm();
		expect( form.length ).toBe( 0 );
	} );
} );
