/**
 * @jest-environment jest-fixed-jsdom
 */

describe( 'createCheckoutPlaceOrderApi', () => {
	let $form;
	let $termsCheckbox;
	let $termsRow;
	let capturedApi;

	beforeEach( () => {
		capturedApi = null;

		// used to track whether terms checkbox is checked
		let termsChecked = false;

		const termsRowClasses = new Set();
		const formInvalidElements = new Set();

		$termsRow = {
			addClass: jest.fn( ( cls ) => {
				cls.split( ' ' ).forEach( ( c ) => formInvalidElements.add( 'terms-row' ) );
				cls.split( ' ' ).forEach( ( c ) => termsRowClasses.add( c ) );
				return $termsRow;
			} ),
			removeClass: jest.fn( ( cls ) => {
				cls.split( ' ' ).forEach( ( c ) => termsRowClasses.delete( c ) );
				if ( cls.includes( 'woocommerce-invalid' ) ) {
					formInvalidElements.delete( 'terms-row' );
				}
				return $termsRow;
			} ),
			hasClass: jest.fn( ( cls ) => termsRowClasses.has( cls ) ),
			length: 1,
			offset: jest.fn( () => ( { top: 100 } ) ),
		};

		$termsCheckbox = {
			length: 1,
			is: jest.fn( ( selector ) => {
				if ( selector === ':checked' ) {
					return termsChecked;
				}
				return false;
			} ),
			closest: jest.fn( () => $termsRow ),
			trigger: jest.fn(),
		};

		// a helper to set the checkbox's state.
		$termsCheckbox.setChecked = ( checked ) => {
			termsChecked = checked;
		};

		$form = {
			length: 1,
			find: jest.fn( ( selector ) => {
				if ( selector === 'input[name="terms"]:visible' ) {
					return $termsCheckbox;
				}
				if ( selector === '.input-text, select, input:checkbox' ) {
					return { trigger: jest.fn() };
				}
				if ( selector === '.woocommerce-invalid' ) {
					return {
						length: formInvalidElements.size,
						first: jest.fn( () => ( {
							length: formInvalidElements.size > 0 ? 1 : 0,
							offset: jest.fn( () => ( { top: 100 } ) ),
						} ) ),
					};
				}
				if ( selector === '.validate-required:visible' ) {
					return { each: jest.fn() };
				}
				if ( selector === 'input[name="payment_method"]:checked' ) {
					return { val: jest.fn( () => 'test-gateway' ) };
				}
				return { length: 0, trigger: jest.fn() };
			} ),
			trigger: jest.fn(),
		};

		// Add methods to $form for checkout.js initialization
		$form.on = jest.fn( () => $form );
		$form.attr = jest.fn( () => $form );

		// Default mock for unhandled selectors - provides all common jQuery methods
		const createDefaultMock = () => {
			const mock = {
				length: 0,
				on: jest.fn( () => mock ),
				off: jest.fn( () => mock ),
				attr: jest.fn( () => mock ),
				find: jest.fn( () => createDefaultMock() ),
				first: jest.fn( () => createDefaultMock() ),
				filter: jest.fn( () => createDefaultMock() ),
				eq: jest.fn( () => createDefaultMock() ),
				trigger: jest.fn( () => mock ),
				val: jest.fn(),
				prop: jest.fn( () => mock ),
				each: jest.fn( () => mock ),
				data: jest.fn(),
				serialize: jest.fn( () => '' ),
				addClass: jest.fn( () => mock ),
				removeClass: jest.fn( () => mock ),
				hasClass: jest.fn( () => false ),
				is: jest.fn( () => false ),
				get: jest.fn( () => [] ),
				text: jest.fn( () => '' ),
				html: jest.fn( () => '' ),
				closest: jest.fn( () => createDefaultMock() ),
				parent: jest.fn( () => createDefaultMock() ),
				parents: jest.fn( () => createDefaultMock() ),
				siblings: jest.fn( () => createDefaultMock() ),
				children: jest.fn( () => createDefaultMock() ),
				append: jest.fn( () => mock ),
				prepend: jest.fn( () => mock ),
				remove: jest.fn( () => mock ),
				empty: jest.fn( () => mock ),
				show: jest.fn( () => mock ),
				hide: jest.fn( () => mock ),
				css: jest.fn( () => mock ),
				slideUp: jest.fn( () => mock ),
				slideDown: jest.fn( () => mock ),
				fadeIn: jest.fn( () => mock ),
				fadeOut: jest.fn( () => mock ),
				offset: jest.fn( () => ( { top: 0, left: 0 } ) ),
				width: jest.fn( () => 0 ),
				height: jest.fn( () => 0 ),
				outerWidth: jest.fn( () => 0 ),
				outerHeight: jest.fn( () => 0 ),
				scrollTop: jest.fn( () => 0 ),
				focus: jest.fn( () => mock ),
				blur: jest.fn( () => mock ),
				block: jest.fn( () => mock ),
				unblock: jest.fn( () => mock ),
			};
			return mock;
		};

		// Simple event system for document.body to enable event-based API capture
		const bodyEventHandlers = {};
		const mockBody = {
			on: jest.fn( ( event, handler ) => {
				if ( ! bodyEventHandlers[ event ] ) {
					bodyEventHandlers[ event ] = [];
				}
				bodyEventHandlers[ event ].push( handler );
				return mockBody;
			} ),
			trigger: jest.fn( ( event, args ) => {
				const handlers = bodyEventHandlers[ event ] || [];
				handlers.forEach( ( handler ) => handler( {}, ...( args || [] ) ) );
				return mockBody;
			} ),
			hasClass: jest.fn( () => false ),
		};

		// Mock jQuery - needs to handle document ready pattern: jQuery(function($) { ... })
		const jQueryMock = jest.fn( ( selectorOrCallback ) => {
			// Handle document ready: jQuery(function($) { ... })
			if ( typeof selectorOrCallback === 'function' ) {
				// Execute immediately with jQuery mock as argument
				selectorOrCallback( jQueryMock );
				return jQueryMock;
			}
			if ( selectorOrCallback === 'form.checkout' ) {
				return $form;
			}
			if ( selectorOrCallback === '#order_review' ) {
				return { length: 0, on: jest.fn(), attr: jest.fn(), find: jest.fn( () => ( { length: 0, val: jest.fn() } ) ) };
			}
			if ( selectorOrCallback === 'html, body' ) {
				return { animate: jest.fn() };
			}
			if ( selectorOrCallback === document.body ) {
				return mockBody;
			}
			// Return a default mock for any other selector
			return createDefaultMock();
		} );
		jQueryMock.blockUI = { defaults: { overlayCSS: {} } };

		global.window.jQuery = jQueryMock;
		global.window.$ = jQueryMock;
		global.jQuery = jQueryMock;
		global.$ = jQueryMock;

		global.window.wc_checkout_params = {
			gateways_with_custom_place_order_button: [ 'test-gateway' ],
		};

		global.window.wc = {
			customPlaceOrderButton: {
				__getForm: jest.fn( () => $form ),
				__maybeShow: jest.fn( ( gatewayId, api ) => {
					capturedApi = api;
				} ),
				__maybeHideDefaultButtonOnInit: jest.fn(),
				__cleanup: jest.fn(),
			},
		};

		// requiring checkout.js - this will execute the jQuery wrapper
		jest.resetModules();
		require( '../checkout' );

		// Trigger the event to capture the API via __maybeShow
		// This simulates a gateway registering after page load
		mockBody.trigger( 'wc_custom_place_order_button_registered', [ 'test-gateway' ] );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'Terms checkbox validation', () => {
		test( 'should return hasError: true when terms checkbox is not checked', async () => {
			$termsCheckbox.setChecked( false );

			const result = await capturedApi.validate();

			expect( result.hasError ).toBe( true );
			expect( $termsRow.addClass ).toHaveBeenCalledWith( 'woocommerce-invalid' );
		} );

		test( 'should return hasError: false when terms checkbox is checked', async () => {
			$termsCheckbox.setChecked( true );

			const result = await capturedApi.validate();

			expect( result.hasError ).toBe( false );
		} );

		test( 'should clear stale invalid state before re-validating terms', async () => {
			// First validation: terms not checked
			$termsCheckbox.setChecked( false );
			await capturedApi.validate();

			expect( $termsRow.addClass ).toHaveBeenCalledWith( 'woocommerce-invalid' );

			// clearing the mock history so the expectations are clearer.
			$termsRow.removeClass.mockClear();
			$termsRow.addClass.mockClear();

			// Second validation: marking the terms as checked
			$termsCheckbox.setChecked( true );
			const result = await capturedApi.validate();

			// Should have cleared the invalid state first
			expect( $termsRow.removeClass ).toHaveBeenCalledWith( 'woocommerce-invalid' );
			// Should NOT have re-added the invalid class
			expect( $termsRow.addClass ).not.toHaveBeenCalledWith( 'woocommerce-invalid' );
			// Should pass validation
			expect( result.hasError ).toBe( false );
		} );

		test( 'should allow submission after checking terms following a failed validation', async () => {
			// First attempt: terms not checked - should fail
			$termsCheckbox.setChecked( false );
			const firstResult = await capturedApi.validate();
			expect( firstResult.hasError ).toBe( true );

			// pretending the user checked the terms checkbox
			$termsCheckbox.setChecked( true );

			// Second attempt: should pass on first try (not require double-click)
			const secondResult = await capturedApi.validate();
			expect( secondResult.hasError ).toBe( false );
		} );
	} );
} );
