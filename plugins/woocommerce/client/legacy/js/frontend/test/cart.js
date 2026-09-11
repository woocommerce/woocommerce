/**
 * @jest-environment jest-fixed-jsdom
 */

// Fixtures mirror what jQuery's serialize() emits on trunk: every other
// reserved character percent-encoded, spaces as `+`, apostrophes literal.
// City and state are free-text inputs in the shipping calculator; the coupon
// code is free text in the cart form. `reference` carries a pre-existing %27
// to prove the helper does not double-encode.
const CART_URL = 'https://example.test/cart/';

const SHIPPING_FORM_SERIALIZED =
	'calc_shipping_country=US' +
	"&calc_shipping_state=O'State" +
	'&calc_shipping_postcode=63366' +
	"&calc_shipping_city=O'Fallon" +
	'&woocommerce-shipping-calculator-nonce=abc123' +
	'&_wp_http_referer=%2Fcart%2F' +
	'&calc_shipping=x';

const SHIPPING_FORM_ENCODED =
	'calc_shipping_country=US' +
	'&calc_shipping_state=O%27State' +
	'&calc_shipping_postcode=63366' +
	'&calc_shipping_city=O%27Fallon' +
	'&woocommerce-shipping-calculator-nonce=abc123' +
	'&_wp_http_referer=%2Fcart%2F' +
	'&calc_shipping=x';

const CART_FORM_SERIALIZED =
	'cart%5Babc123%5D%5Bqty%5D=2' +
	"&coupon_code=SAVE'10" +
	"&order'note=Leave+at+O'Brien's+door" +
	'&reference=already%27encoded' +
	'&woocommerce-cart-nonce=abc123' +
	'&_wp_http_referer=%2Fcart%2F';

const CART_FORM_ENCODED =
	'cart%5Babc123%5D%5Bqty%5D=2' +
	'&coupon_code=SAVE%2710' +
	'&order%27note=Leave+at+O%27Brien%27s+door' +
	'&reference=already%27encoded' +
	'&woocommerce-cart-nonce=abc123' +
	'&_wp_http_referer=%2Fcart%2F';

describe( 'cart.js request encoding', () => {
	let capturedAjaxRequests;
	let documentHandlers;
	let findDocumentHandler;
	let $cartForm;
	let $shippingForm;
	let clickedSubmitName;

	// Sentinels standing in for the DOM elements jQuery would hand to a
	// delegated handler as `evt.currentTarget`.
	const cartFormElement = { id: 'cart-form' };
	const shippingFormElement = { id: 'shipping-form' };

	beforeEach( () => {
		capturedAjaxRequests = [];
		documentHandlers = [];
		clickedSubmitName = null;

		// Default mock for selectors the tests do not care about. Every method
		// chains back to the mock so block()/unblock() and friends run through.
		const createDefaultMock = () => {
			const mock = {
				length: 0,
				addClass: jest.fn( () => mock ),
				appendTo: jest.fn( () => mock ),
				attr: jest.fn( () => mock ),
				block: jest.fn( () => mock ),
				closest: jest.fn( () => createDefaultMock() ),
				each: jest.fn( () => mock ),
				find: jest.fn( () => createDefaultMock() ),
				hide: jest.fn( () => mock ),
				is: jest.fn( () => false ),
				on: jest.fn( () => mock ),
				parents: jest.fn( () => createDefaultMock() ),
				prop: jest.fn( () => mock ),
				removeClass: jest.fn( () => mock ),
				trigger: jest.fn( () => mock ),
				unblock: jest.fn( () => mock ),
				val: jest.fn(),
			};
			return mock;
		};

		// cart.js binds everything off $( document ). Direct registrations look
		// like on( 'a b', handler ); delegated ones look like
		// on( 'submit', selector, handler ). Store one entry per event name so a
		// test can pick a handler by ( event, selector ).
		const $document = {
			on: jest.fn( ( events, selectorOrHandler, delegatedHandler ) => {
				const isDelegated = typeof delegatedHandler === 'function';
				events.split( ' ' ).forEach( ( event ) => {
					documentHandlers.push( {
						event,
						selector: isDelegated ? selectorOrHandler : null,
						handler: isDelegated ? delegatedHandler : selectorOrHandler,
					} );
				} );
				return $document;
			} ),
		};

		findDocumentHandler = ( event, selector = null ) => {
			const entry = documentHandlers.find(
				( candidate ) =>
					candidate.event === event && candidate.selector === selector
			);
			if ( ! entry ) {
				throw new Error(
					'No ' + event + ' handler' + ( selector ? ' for ' + selector : '' )
				);
			}
			return entry.handler;
		};

		const formAttributes = { method: 'post', action: CART_URL };

		$cartForm = createDefaultMock();
		$cartForm.length = 1;
		$cartForm.attr = jest.fn( ( name ) => formAttributes[ name ] );
		$cartForm.serialize = jest.fn( () => CART_FORM_SERIALIZED );
		// cart_submit() bails unless the target is a form with cart contents.
		$cartForm.is = jest.fn( ( selector ) => selector === 'form' );
		$cartForm.find = jest.fn( ( selector ) =>
			selector === '.woocommerce-cart-form__contents'
				? { length: 1 }
				: createDefaultMock()
		);

		$shippingForm = createDefaultMock();
		$shippingForm.length = 1;
		$shippingForm.attr = jest.fn( ( name ) => formAttributes[ name ] );
		$shippingForm.serialize = jest.fn( () => SHIPPING_FORM_SERIALIZED );

		// cart_submit() routes on which submit button was clicked.
		const $clickedSubmit = {
			is: jest.fn(
				( selector ) =>
					clickedSubmitName !== null &&
					selector === ':input[name="' + clickedSubmitName + '"]'
			),
		};

		const jQueryMock = jest.fn( ( arg ) => {
			// Document ready: jQuery( function ( $ ) { ... } ).
			if ( typeof arg === 'function' ) {
				arg( jQueryMock );
				return jQueryMock;
			}
			if ( arg === document ) {
				return $document;
			}
			if (
				arg === '.woocommerce-cart-form' ||
				arg === cartFormElement ||
				arg === $cartForm
			) {
				return $cartForm;
			}
			if ( arg === shippingFormElement || arg === $shippingForm ) {
				return $shippingForm;
			}
			if ( arg === ':input[type=submit][clicked=true]' ) {
				return $clickedSubmit;
			}
			return createDefaultMock();
		} );
		jQueryMock.ajax = jest.fn( ( options ) => {
			capturedAjaxRequests.push( options );
			return { abort: jest.fn() };
		} );

		global.window.jQuery = jQueryMock;
		global.window.$ = jQueryMock;
		global.jQuery = jQueryMock;
		global.$ = jQueryMock;

		global.window.wc_cart_params = {
			ajax_url: '/wp-admin/admin-ajax.php',
			wc_ajax_url: '/?wc-ajax=%%endpoint%%',
			update_shipping_method_nonce: 'nonce',
			apply_coupon_nonce: 'nonce',
			remove_coupon_nonce: 'nonce',
		};

		// Requiring cart.js runs the jQuery wrapper and binds the handlers.
		jest.resetModules();
		require( '../cart' );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'should encode apostrophes in shipping calculator data', () => {
		const submit = findDocumentHandler(
			'submit',
			'form.woocommerce-shipping-calculator'
		);
		const evt = { preventDefault: jest.fn(), currentTarget: shippingFormElement };

		submit( evt );

		expect( evt.preventDefault ).toHaveBeenCalled();
		expect( capturedAjaxRequests ).toHaveLength( 1 );

		const request = capturedAjaxRequests[ 0 ];
		expect( request.url ).toBe( CART_URL );
		expect( request.data ).not.toContain( "'" );
		expect( request.data ).toBe( SHIPPING_FORM_ENCODED );

		// Every value decodes back to what the shopper typed.
		const body = new URLSearchParams( request.data );
		expect( body.get( 'calc_shipping_city' ) ).toBe( "O'Fallon" );
		expect( body.get( 'calc_shipping_state' ) ).toBe( "O'State" );
		expect( body.get( 'calc_shipping' ) ).toBe( 'x' );
	} );

	test( 'should encode apostrophes in update cart data', () => {
		// update_cart() is reached through the wc_update_cart document event.
		const updateCart = findDocumentHandler( 'wc_update_cart' );

		updateCart( {} );

		expect( capturedAjaxRequests ).toHaveLength( 1 );

		const request = capturedAjaxRequests[ 0 ];
		expect( request.url ).toBe( CART_URL );
		expect( request.data ).not.toContain( "'" );
		expect( request.data ).toBe( CART_FORM_ENCODED );

		const body = new URLSearchParams( request.data );
		expect( body.get( 'coupon_code' ) ).toBe( "SAVE'10" );
		expect( body.get( 'cart[abc123][qty]' ) ).toBe( '2' );
		expect( body.get( "order'note" ) ).toBe( "Leave at O'Brien's door" );
		expect( body.get( 'reference' ) ).toBe( "already'encoded" );
	} );

	test( 'should encode apostrophes in quantity update data', () => {
		// quantity_update() is reached through cart_submit() when the clicked
		// submit button is the Update cart button.
		clickedSubmitName = 'update_cart';
		const submit = findDocumentHandler( 'submit', '.woocommerce-cart-form' );
		const evt = { preventDefault: jest.fn(), currentTarget: cartFormElement };

		submit( evt );

		// preventDefault() proves cart_submit() took the quantity_update() branch.
		expect( evt.preventDefault ).toHaveBeenCalled();
		expect( capturedAjaxRequests ).toHaveLength( 1 );

		const request = capturedAjaxRequests[ 0 ];
		expect( request.url ).toBe( CART_URL );
		expect( request.data ).not.toContain( "'" );
		expect( request.data ).toBe( CART_FORM_ENCODED );
		expect( new URLSearchParams( request.data ).get( 'coupon_code' ) ).toBe(
			"SAVE'10"
		);
	} );
} );
