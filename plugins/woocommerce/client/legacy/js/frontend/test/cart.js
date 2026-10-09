/**
 * @jest-environment jest-fixed-jsdom
 */

// Fixtures match jQuery 3 serialize(): encodeURIComponent per field, spaces as
// %20, apostrophes literal. cart.js hands this to $.ajax() untouched and opts in
// to the apostrophe encoding that the woocommerce.js prefilter applies.
const CART_URL = 'https://example.test/cart/';
const COUPON_CODE = "SAVE'10";

const SHIPPING_FORM_SERIALIZED =
	'calc_shipping_country=US' +
	"&calc_shipping_state=O'State" +
	'&calc_shipping_postcode=63366' +
	"&calc_shipping_city=O'Fallon" +
	'&woocommerce-shipping-calculator-nonce=abc123' +
	'&_wp_http_referer=%2Fcart%2F' +
	'&calc_shipping=x';

const CART_FORM_SERIALIZED =
	'cart%5Babc123%5D%5Bqty%5D=2' +
	"&coupon_code=SAVE'10" +
	"&order'note=Leave%20at%20O'Brien's%20door" +
	'&reference=already%2527encoded' +
	'&woocommerce-cart-nonce=abc123' +
	'&_wp_http_referer=%2Fcart%2F';

// quantity_update() appends a hidden update_cart input before serialize().
const QUANTITY_FORM_SERIALIZED =
	CART_FORM_SERIALIZED + '&update_cart=Update%20Cart';

describe( 'cart.js request data', () => {
	let capturedAjaxRequests;
	let documentHandlers;
	let findDocumentHandler;
	let $cartForm;
	let $shippingForm;
	let $couponInput;
	let $removeCouponLink;
	let clickedSubmitName;

	// Sentinels standing in for the DOM elements jQuery would hand to a
	// delegated handler as `evt.currentTarget`.
	const cartFormElement = { id: 'cart-form' };
	const shippingFormElement = { id: 'shipping-form' };
	const removeCouponElement = { id: 'remove-coupon' };

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

		$couponInput = createDefaultMock();
		$couponInput.length = 1;
		$couponInput.val = jest.fn( () => COUPON_CODE );

		// remove_coupon_clicked() reads the code off data-coupon and blocks
		// the closest .cart_totals wrapper.
		$removeCouponLink = createDefaultMock();
		$removeCouponLink.length = 1;
		$removeCouponLink.attr = jest.fn( ( name ) =>
			name === 'data-coupon' ? COUPON_CODE : undefined
		);
		$removeCouponLink.closest = jest.fn( () => createDefaultMock() );

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
			if ( arg === '#coupon_code' ) {
				return $couponInput;
			}
			if ( arg === removeCouponElement ) {
				return $removeCouponLink;
			}
			return createDefaultMock();
		} );
		jQueryMock.ajax = jest.fn( ( options ) => {
			capturedAjaxRequests.push( options );
			return { abort: jest.fn() };
		} );
		jQueryMock.param = jest.fn();

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

	// Every request site passes the data to $.ajax() as jQuery would get it from
	// the shopper (the serialized form, or the data object) and sets the
	// wc_encode_apostrophes option, so the woocommerce.js prefilter encodes the
	// apostrophes after jQuery has serialized the request. Passing objects keeps
	// undefined fields out of the body and lets other prefilters read
	// originalOptions.data.
	test( 'should send the serialized shipping calculator form with the encoding opt-in', () => {
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
		expect( request.data ).toBe( SHIPPING_FORM_SERIALIZED );
		expect( request.wc_encode_apostrophes ).toBe( true );
		expect( global.jQuery.param ).not.toHaveBeenCalled();
	} );

	test( 'should send the serialized cart form with the encoding opt-in', () => {
		// update_cart() is reached through the wc_update_cart document event.
		const updateCart = findDocumentHandler( 'wc_update_cart' );

		updateCart( {} );

		expect( capturedAjaxRequests ).toHaveLength( 1 );

		const request = capturedAjaxRequests[ 0 ];
		expect( request.url ).toBe( CART_URL );
		expect( request.data ).toBe( CART_FORM_SERIALIZED );
		expect( request.wc_encode_apostrophes ).toBe( true );
	} );

	test( 'should send the serialized quantity update form with the encoding opt-in', () => {
		// quantity_update() is reached through cart_submit() when the clicked
		// submit button is the Update cart button.
		clickedSubmitName = 'update_cart';
		$cartForm.serialize.mockReturnValue( QUANTITY_FORM_SERIALIZED );
		const submit = findDocumentHandler( 'submit', '.woocommerce-cart-form' );
		const evt = { preventDefault: jest.fn(), currentTarget: cartFormElement };

		submit( evt );

		// preventDefault() proves cart_submit() took the quantity_update() branch.
		expect( evt.preventDefault ).toHaveBeenCalled();
		expect( capturedAjaxRequests ).toHaveLength( 1 );

		const request = capturedAjaxRequests[ 0 ];
		expect( request.url ).toBe( CART_URL );
		expect( request.data ).toBe( QUANTITY_FORM_SERIALIZED );
		expect( request.wc_encode_apostrophes ).toBe( true );
	} );

	test( 'should send apply coupon data as an object with the encoding opt-in', () => {
		clickedSubmitName = 'apply_coupon';
		const submit = findDocumentHandler( 'submit', '.woocommerce-cart-form' );
		const evt = { preventDefault: jest.fn(), currentTarget: cartFormElement };

		submit( evt );

		expect( evt.preventDefault ).toHaveBeenCalled();

		const request = capturedAjaxRequests.find( ( options ) =>
			options.url.includes( 'apply_coupon' )
		);
		expect( request ).toBeDefined();
		expect( typeof request.data ).toBe( 'object' );
		expect( request.data ).toEqual( {
			security: 'nonce',
			coupon_code: COUPON_CODE,
		} );
		expect( request.wc_encode_apostrophes ).toBe( true );
		expect( global.jQuery.param ).not.toHaveBeenCalled();
	} );

	test( 'should send remove coupon data as an object with the encoding opt-in', () => {
		const click = findDocumentHandler( 'click', 'a.woocommerce-remove-coupon' );
		const evt = {
			preventDefault: jest.fn(),
			currentTarget: removeCouponElement,
		};

		click( evt );

		expect( evt.preventDefault ).toHaveBeenCalled();

		const request = capturedAjaxRequests.find( ( options ) =>
			options.url.includes( 'remove_coupon' )
		);
		expect( request ).toBeDefined();
		expect( typeof request.data ).toBe( 'object' );
		expect( request.data ).toEqual( {
			security: 'nonce',
			coupon: COUPON_CODE,
		} );
		expect( request.wc_encode_apostrophes ).toBe( true );
	} );
} );
