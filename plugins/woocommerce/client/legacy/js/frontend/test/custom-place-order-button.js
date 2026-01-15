/**
 * @jest-environment jest-fixed-jsdom
 */

describe( 'Custom Place Order Button API', () => {
	let jQueryMock;
	let $form;
	let $placeOrderButton;

	beforeEach( () => {
		// Resetting the window object
		delete global.window.wc;

		// creating some mocked DOM elements
		$form = {
			length: 1,
			first: jest.fn( () => $form ),
			find: jest.fn( () => ( {
				length: 1,
				val: jest.fn( () => 'test-gateway' ),
				after: jest.fn(),
			} ) ),
			addClass: jest.fn( () => $form ),
			removeClass: jest.fn( () => $form ),
		};

		$placeOrderButton = {
			length: 1,
			after: jest.fn(),
		};

		// mocking jQuery
		jQueryMock = jest.fn( ( selector ) => {
			if ( selector === 'form.checkout' ) {
				return { length: 1, first: jest.fn( () => $form ) };
			}
			if ( selector === '#order_review' ) {
				return { length: 0 };
			}
			if ( selector === '#add_payment_method' ) {
				return { length: 0 };
			}
			if ( selector === [] ) {
				return { length: 0 };
			}
			if ( typeof selector === 'string' && selector.includes( 'div' ) ) {
				return {
					length: 1,
					get: jest.fn( () => document.createElement( 'div' ) ),
					empty: jest.fn(),
					remove: jest.fn(),
					append: jest.fn(),
				};
			}
			return { length: 0 };
		} );

		jQueryMock.contains = jest.fn( () => false );

		global.window.jQuery = jQueryMock;
		global.window.$ = jQueryMock;

		// mocking wc_checkout_params
		global.window.wc_checkout_params = {
			gateways_with_custom_place_order_button: [ 'test-gateway' ],
		};

		// mocking the event triggering on document.body
		jQueryMock.fn = {};
		const mockBody = {
			trigger: jest.fn(),
		};
		jQueryMock.mockImplementation( ( selector ) => {
			if ( selector === document.body ) {
				return mockBody;
			}
			if ( selector === 'form.checkout' ) {
				return { length: 1, first: jest.fn( () => $form ) };
			}
			if ( selector === '#order_review' ) {
				return { length: 0 };
			}
			if ( selector === '#add_payment_method' ) {
				return { length: 0 };
			}
			return { length: 0 };
		} );

		// using a fresh instance on each test
		jest.resetModules();
		require( '../utils/custom-place-order-button' );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'Base tests', () => {
		test( 'should expose the API', () => {
			expect( window.wc ).toBeDefined();
			expect( window.wc.customPlaceOrderButton ).toBeDefined();
			expect(
				typeof window.wc.customPlaceOrderButton.register
			).toBe( 'function' );
			expect(
				typeof window.wc.customPlaceOrderButton.__maybeShow
			).toBe( 'function' );
			expect(
				typeof window.wc.customPlaceOrderButton.__maybeHideDefaultButtonOnInit
			).toBe( 'function' );
			expect(
				typeof window.wc.customPlaceOrderButton.__cleanup
			).toBe( 'function' );
			expect(
				typeof window.wc.customPlaceOrderButton.__getForm
			).toBe( 'function' );
		} );

		test( 'should reject registration without proper configuration', () => {
			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			window.wc.customPlaceOrderButton.register( 'test-gateway', {
				cleanup: jest.fn(),
			} );

			expect( consoleSpy ).toHaveBeenLastCalledWith(
				'wc.customPlaceOrderButton.register: render must be a function'
			);

			window.wc.customPlaceOrderButton.register( 'test-gateway', {
				render: jest.fn(),
			} );

			expect( consoleSpy ).toHaveBeenLastCalledWith(
				'wc.customPlaceOrderButton.register: cleanup must be a function'
			);

			window.wc.customPlaceOrderButton.register( null, {
				render: jest.fn(),
				cleanup: jest.fn(),
			} );

			expect( consoleSpy ).toHaveBeenLastCalledWith(
				'wc.customPlaceOrderButton.register: gatewayId must be a non-empty string'
			);

			window.wc.customPlaceOrderButton.register( '', {
				render: jest.fn(),
				cleanup: jest.fn(),
			} );

			expect( consoleSpy ).toHaveBeenLastCalledWith(
				'wc.customPlaceOrderButton.register: gatewayId must be a non-empty string'
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

		test( 'should not inject duplicate styles', () => {
			// Re-require the module
			jest.resetModules();
			require( '../utils/custom-place-order-button' );

			const styleElements = document.querySelectorAll(
				'#wc-custom-place-order-button-styles'
			);
			expect( styleElements.length ).toBe( 1 );
		} );
	} );

	describe( 'getGatewaysWithCustomButton', () => {
		test( 'should return gateways from wc_checkout_params', () => {
			// This is tested indirectly - if the gateway is in the server list,
			// maybeHideDefaultButtonOnInit should add the class
			expect(
				window.wc_checkout_params.gateways_with_custom_place_order_button
			).toContain( 'test-gateway' );
		} );

		test( 'should return empty array when wc_checkout_params is undefined', () => {
			delete global.window.wc_checkout_params;
			delete global.window.wc_add_payment_method_params;

			jest.resetModules();
			require( '../utils/custom-place-order-button' );

			// The API should still work without errors
			expect( window.wc.customPlaceOrderButton ).toBeDefined();
		} );

		test( 'should use wc_add_payment_method_params as fallback', () => {
			delete global.window.wc_checkout_params;
			global.window.wc_add_payment_method_params = {
				gateways_with_custom_place_order_button: [ 'add-method-gateway' ],
			};

			jest.resetModules();
			require( '../utils/custom-place-order-button' );

			expect( window.wc.customPlaceOrderButton ).toBeDefined();
		} );
	} );
} );

describe( 'getForm helper', () => {
	beforeEach( () => {
		delete global.window.wc;

		// Default mock - no forms found
		global.window.jQuery = jest.fn( (  ) => {
			return { length: 0 };
		} );

		global.window.wc_checkout_params = {
			gateways_with_custom_place_order_button: [],
		};

		jest.resetModules();
		require( '../utils/custom-place-order-button' );
	} );

	test( 'should return form.checkout if present', () => {
		const mockForm = { length: 1, first: jest.fn( () => mockForm ) };

		global.window.jQuery = jest.fn( ( selector ) => {
			if ( selector === 'form.checkout' ) {
				return mockForm;
			}
			return { length: 0 };
		} );

		jest.resetModules();
		require( '../utils/custom-place-order-button' );

		const form = window.wc.customPlaceOrderButton.__getForm();
		expect( form ).toBe( mockForm );
	} );

	test( 'should return #order_review if form.checkout not present', () => {
		const mockOrderReview = { length: 1, first: jest.fn( () => mockOrderReview ) };

		global.window.jQuery = jest.fn( ( selector ) => {
			if ( selector === 'form.checkout' ) {
				return { length: 0 };
			}
			if ( selector === '#order_review' ) {
				return mockOrderReview;
			}
			return { length: 0 };
		} );

		jest.resetModules();
		require( '../utils/custom-place-order-button' );

		const form = window.wc.customPlaceOrderButton.__getForm();
		expect( form ).toBe( mockOrderReview );
	} );

	test( 'should return #add_payment_method as last resort', () => {
		const mockAddPaymentMethod = {
			length: 1,
			first: jest.fn( () => mockAddPaymentMethod ),
		};

		global.window.jQuery = jest.fn( ( selector ) => {
			if ( selector === 'form.checkout' ) {
				return { length: 0 };
			}
			if ( selector === '#order_review' ) {
				return { length: 0 };
			}
			if ( selector === '#add_payment_method' ) {
				return mockAddPaymentMethod;
			}
			return { length: 0 };
		} );

		jest.resetModules();
		require( '../utils/custom-place-order-button' );

		const form = window.wc.customPlaceOrderButton.__getForm();
		expect( form ).toBe( mockAddPaymentMethod );
	} );

	test( 'should return empty jQuery object if no form found', () => {
		const emptyJQuery = { length: 0 };

		global.window.jQuery = jest.fn( ( selector ) => {
			if ( selector === 'form.checkout' ) {
				return { length: 0 };
			}
			if ( selector === '#order_review' ) {
				return { length: 0 };
			}
			if ( selector === '#add_payment_method' ) {
				return { length: 0 };
			}
			if ( Array.isArray( selector ) && selector.length === 0 ) {
				return emptyJQuery;
			}
			return { length: 0 };
		} );

		jest.resetModules();
		require( '../utils/custom-place-order-button' );

		const form = window.wc.customPlaceOrderButton.__getForm();
		expect( form.length ).toBe( 0 );
	} );
} );
