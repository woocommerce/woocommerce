/**
 * @jest-environment jest-fixed-jsdom
 */

describe( 'checkout login form', () => {
	let loginClickHandler;
	let $loginForm;

	beforeEach( () => {
		loginClickHandler = null;
		$loginForm = createJQueryResult( 0 );

		const $checkoutForm = createJQueryResult( 1 );
		const $orderReview = createJQueryResult( 0 );
		const $body = createJQueryResult( 1 );
		$body.on = jest.fn( ( event, selector, handler ) => {
			if ( event === 'click' && selector === 'a.showlogin' ) {
				loginClickHandler = handler;
			}
			return $body;
		} );

		const jQueryMock = jest.fn( ( selectorOrCallback ) => {
			if ( typeof selectorOrCallback === 'function' ) {
				selectorOrCallback( jQueryMock );
				return jQueryMock;
			}
			if ( selectorOrCallback === document.body ) {
				return $body;
			}
			if ( selectorOrCallback === 'form.checkout' ) {
				return $checkoutForm;
			}
			if ( selectorOrCallback === '#order_review' ) {
				return $orderReview;
			}
			if (
				selectorOrCallback ===
				'form.login, form.woocommerce-form--login'
			) {
				return $loginForm;
			}
			return createJQueryResult( 0 );
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
				__cleanup: jest.fn(),
				__getForm: jest.fn( () => $checkoutForm ),
				__maybeHideDefaultButtonOnInit: jest.fn(),
				__maybeShow: jest.fn(),
			},
		};

		jest.resetModules();
		require( '../checkout' );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'intercepts the link when an inline login form exists', () => {
		$loginForm = createJQueryResult( 1 );

		const result = loginClickHandler();

		expect( result ).toBe( false );
		expect( $loginForm.slideToggle ).toHaveBeenCalled();
	} );

	test( 'preserves link navigation when no inline login form exists', () => {
		const result = loginClickHandler();

		expect( result ).toBeUndefined();
		expect( $loginForm.slideToggle ).not.toHaveBeenCalled();
	} );
} );

function createJQueryResult( length ) {
	const result = {
		length,
		addClass: jest.fn( () => result ),
		animate: jest.fn( () => result ),
		attr: jest.fn( () => result ),
		children: jest.fn( () => createJQueryResult( 0 ) ),
		closest: jest.fn( () => createJQueryResult( 0 ) ),
		data: jest.fn(),
		each: jest.fn( () => result ),
		eq: jest.fn( () => createJQueryResult( 0 ) ),
		filter: jest.fn( () => createJQueryResult( 0 ) ),
		find: jest.fn( () => createJQueryResult( 0 ) ),
		first: jest.fn( () => createJQueryResult( 0 ) ),
		hasClass: jest.fn( () => false ),
		hide: jest.fn( () => result ),
		is: jest.fn( () => false ),
		off: jest.fn( () => result ),
		on: jest.fn( () => result ),
		offset: jest.fn( () => ( { top: 0 } ) ),
		parent: jest.fn( () => createJQueryResult( 0 ) ),
		prop: jest.fn( () => result ),
		removeClass: jest.fn( () => result ),
		serialize: jest.fn( () => '' ),
		slideToggle: jest.fn( () => result ),
		trigger: jest.fn( () => result ),
		val: jest.fn(),
	};
	return result;
}
