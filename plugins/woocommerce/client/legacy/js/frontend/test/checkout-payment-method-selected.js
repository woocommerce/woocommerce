/**
 * @jest-environment jest-fixed-jsdom
 *
 * Regression tests for the payment method radio click handler in
 * `checkout.js`. See issue woo#25887 (RSMAPGJ-312): clicking a payment method
 * radio must keep the HTML `checked` attribute in sync with the selection so
 * that CSS / jQuery selectors targeting `[checked]` reflect the current state.
 */

describe( 'checkout.js payment_method_selected', () => {
	let $form;
	let $paymentRadios;
	let paymentClickHandler;
	let nodeWrappers;

	beforeEach( () => {
		nodeWrappers = new Map();

		// Reset window state.
		delete global.window.wc;

		// Tracks attr/removeAttr calls on the radio collection.
		$paymentRadios = {
			length: 2,
			removeAttr: jest.fn( () => $paymentRadios ),
		};

		const createDefaultMock = () => {
			const mock = {
				length: 0,
				on: jest.fn( () => mock ),
				off: jest.fn( () => mock ),
				attr: jest.fn( () => mock ),
				removeAttr: jest.fn( () => mock ),
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

		// Capture the delegated click handler bound to the payment method radios.
		paymentClickHandler = null;
		$form = {
			length: 1,
			on: jest.fn( ( event, selectorOrHandler, maybeHandler ) => {
				if (
					event === 'click' &&
					selectorOrHandler === 'input[name="payment_method"]'
				) {
					paymentClickHandler = maybeHandler;
				}
				return $form;
			} ),
			attr: jest.fn( () => $form ),
			trigger: jest.fn( () => $form ),
			find: jest.fn( () => createDefaultMock() ),
		};

		const bodyEventHandlers = {};
		const mockBody = {
			on: jest.fn( ( event, handler ) => {
				bodyEventHandlers[ event ] = ( bodyEventHandlers[ event ] || [] ).concat(
					handler
				);
				return mockBody;
			} ),
			trigger: jest.fn( ( event, args ) => {
				( bodyEventHandlers[ event ] || [] ).forEach( ( h ) =>
					h( {}, ...( args || [] ) )
				);
				return mockBody;
			} ),
			hasClass: jest.fn( () => false ),
		};

		const jQueryMock = jest.fn( ( selectorOrCallback ) => {
			if ( typeof selectorOrCallback === 'function' ) {
				selectorOrCallback( jQueryMock );
				return jQueryMock;
			}
			if (
				selectorOrCallback &&
				typeof selectorOrCallback === 'object' &&
				nodeWrappers.has( selectorOrCallback )
			) {
				return nodeWrappers.get( selectorOrCallback );
			}
			if ( selectorOrCallback === 'form.checkout' ) {
				return $form;
			}
			if ( selectorOrCallback === '#order_review' ) {
				return {
					length: 0,
					on: jest.fn(),
					attr: jest.fn(),
					find: jest.fn( () => ( { length: 0, val: jest.fn() } ) ),
				};
			}
			if ( selectorOrCallback === 'html, body' ) {
				return { animate: jest.fn() };
			}
			if ( selectorOrCallback === document.body ) {
				return mockBody;
			}
			if ( selectorOrCallback === 'input[name="payment_method"]' ) {
				return $paymentRadios;
			}
			return createDefaultMock();
		} );
		jQueryMock.blockUI = { defaults: { overlayCSS: {} } };

		global.window.jQuery = jQueryMock;
		global.window.$ = jQueryMock;
		global.jQuery = jQueryMock;
		global.$ = jQueryMock;

		global.window.wc_checkout_params = {
			gateways_with_custom_place_order_button: [],
		};

		global.window.wc = {
			customPlaceOrderButton: {
				__getForm: jest.fn( () => $form ),
				__maybeShow: jest.fn(),
				__maybeHideDefaultButtonOnInit: jest.fn(),
				__cleanup: jest.fn(),
			},
		};

		jest.resetModules();
		require( '../checkout' );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'binds a click handler on payment method radios during init', () => {
		expect( paymentClickHandler ).toEqual( expect.any( Function ) );
	} );

	test( 'clears the checked attribute on all payment method radios when a method is clicked', () => {
		// Mock `this` (the clicked radio) with the minimum jQuery surface used
		// by the handler.
		const $clickedRadio = {
			attr: jest.fn( () => $clickedRadio ),
			is: jest.fn( () => true ),
			val: jest.fn( () => 'cod' ),
			data: jest.fn(),
		};

		// The handler calls `$( this )` — register the raw node so the
		// captured `$` returns our mock wrapper.
		const rawNode = {};
		nodeWrappers.set( rawNode, $clickedRadio );

		paymentClickHandler.call( rawNode, { stopPropagation: jest.fn() } );

		expect( $paymentRadios.removeAttr ).toHaveBeenCalledWith( 'checked' );
		expect( $clickedRadio.attr ).toHaveBeenCalledWith(
			'checked',
			'checked'
		);
	} );
} );
