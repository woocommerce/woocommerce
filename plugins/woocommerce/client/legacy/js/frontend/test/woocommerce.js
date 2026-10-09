/**
 * @jest-environment jest-fixed-jsdom
 */

// woocommerce.js registers a jQuery ajax prefilter that percent-encodes
// apostrophes in the serialized body of requests that opt in with the
// wc_encode_apostrophes option. jQuery leaves `'` alone when serializing, and
// some WAF rules reject bodies with literal apostrophes (see #40791).
describe( 'woocommerce.js apostrophe prefilter', () => {
	let prefilter;

	beforeEach( () => {
		const createDefaultMock = () => {
			const mock = {
				length: 0,
				on: jest.fn( () => mock ),
				each: jest.fn( () => mock ),
				data: jest.fn(),
				hide: jest.fn( () => mock ),
				show: jest.fn( () => mock ),
				wrap: jest.fn( () => mock ),
				filter: jest.fn( () => mock ),
				parent: jest.fn( () => mock ),
				addClass: jest.fn( () => mock ),
			};
			return mock;
		};
		const jQueryMock = jest.fn( ( arg ) => {
			// jQuery( fn ) is the DOM-ready wrapper; the prefilter is registered
			// outside it, so the wrapper never has to run for these tests.
			if ( typeof arg === 'function' ) {
				return;
			}
			return createDefaultMock();
		} );
		jQueryMock.ajaxPrefilter = jest.fn( ( handler ) => {
			prefilter = handler;
		} );

		global.window.jQuery = jQueryMock;
		global.window.$ = jQueryMock;
		global.jQuery = jQueryMock;
		global.$ = jQueryMock;

		jest.resetModules();
		require( '../woocommerce' );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'should register one ajax prefilter when the script loads', () => {
		expect( global.jQuery.ajaxPrefilter ).toHaveBeenCalledTimes( 1 );
		expect( typeof prefilter ).toBe( 'function' );
	} );

	test( 'should encode apostrophes in the serialized body of an opted-in request', () => {
		// What jQuery hands a prefilter: data already run through $.param(),
		// where encodeURIComponent() keeps `'` literal and encodes `%` as %25.
		const options = {
			url: '/?wc-ajax=update_order_review',
			data: "city=O'Fallon&address=1+O'Fallon+St&reference=already%2527encoded",
			wc_encode_apostrophes: true,
		};

		prefilter( options );

		expect( options.data ).toBe(
			'city=O%27Fallon&address=1+O%27Fallon+St&reference=already%2527encoded'
		);
		expect( options.data ).not.toContain( "'" );
		// Every value still decodes to what the shopper typed.
		const body = new URLSearchParams( options.data );
		expect( body.get( 'city' ) ).toBe( "O'Fallon" );
		expect( body.get( 'reference' ) ).toBe( 'already%27encoded' );
	} );

	test( 'should leave requests without the opt-in alone', () => {
		const options = {
			url: '/?wc-ajax=update_order_review',
			data: "city=O'Fallon",
		};

		prefilter( options );

		expect( options.data ).toBe( "city=O'Fallon" );
	} );

	test( 'should leave non-string data alone even when opted in', () => {
		// processData: false requests reach prefilters with their raw payload.
		const formData = { append: jest.fn() };
		const options = {
			url: '/?wc-ajax=update_order_review',
			data: formData,
			wc_encode_apostrophes: true,
		};

		prefilter( options );

		expect( options.data ).toBe( formData );
	} );

	test( 'should not touch the original options the caller passed', () => {
		// jQuery passes its own copy as `options` and the caller's object as
		// `originalOptions`; third-party prefilters read the latter as an object.
		const originalOptions = {
			data: { city: "O'Fallon", state: undefined },
			wc_encode_apostrophes: true,
		};
		const options = {
			data: "city=O'Fallon",
			wc_encode_apostrophes: true,
		};

		prefilter( options, originalOptions );

		expect( options.data ).toBe( 'city=O%27Fallon' );
		expect( originalOptions.data ).toEqual( { city: "O'Fallon", state: undefined } );
	} );
} );
