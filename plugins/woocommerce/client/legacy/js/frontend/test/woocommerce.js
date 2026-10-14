/**
 * @jest-environment jest-fixed-jsdom
 */

// woocommerce.js registers a jQuery ajax prefilter that encodes the body of
// requests that opt in with the wc_form_encoding option the way a browser form
// submission would. jQuery serializes with encodeURIComponent(), which leaves
// !'()~ literal; the WHATWG form serializer percent-encodes them, and some WAF
// rules reject bodies with the literal characters (see #40791).
describe( 'woocommerce.js form encoding prefilter', () => {
	let prefilter;
	const FORM = 'application/x-www-form-urlencoded; charset=UTF-8';

	const loadScript = ( withAjaxPrefilter = true ) => {
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
		if ( withAjaxPrefilter ) {
			jQueryMock.ajaxPrefilter = jest.fn( ( handler ) => {
				prefilter = handler;
			} );
		}
		jQueryMock.isPlainObject = ( obj ) =>
			!! obj && Object.getPrototypeOf( obj ) === Object.prototype;
		jQueryMock.param = ( obj ) =>
			Object.keys( obj )
				.map(
					( key ) =>
						encodeURIComponent( key ) +
						'=' +
						encodeURIComponent( obj[ key ] )
				)
				.join( '&' );

		global.window.jQuery = jQueryMock;
		global.window.$ = jQueryMock;
		global.jQuery = jQueryMock;
		global.$ = jQueryMock;

		jest.resetModules();
		require( '../woocommerce' );
		return jQueryMock;
	};

	afterEach( () => {
		prefilter = undefined;
		jest.clearAllMocks();
	} );

	test( 'should register one ajax prefilter when the script loads', () => {
		const jQueryMock = loadScript();

		expect( jQueryMock.ajaxPrefilter ).toHaveBeenCalledTimes( 1 );
		expect( typeof prefilter ).toBe( 'function' );
	} );

	test( 'should load without throwing when jQuery has no ajax module', () => {
		// A slim jQuery build has no ajaxPrefilter; the rest of woocommerce.js
		// must still run.
		expect( () => loadScript( false ) ).not.toThrow();
		expect( prefilter ).toBeUndefined();
	} );

	test( 'should encode the characters a browser form post encodes', () => {
		loadScript();
		// What jQuery hands a prefilter: data already run through $.param(),
		// where encodeURIComponent() keeps !'()~ literal and encodes `%` as %25.
		const options = {
			processData: true,
			contentType: FORM,
			data: "company=Foo+(Pty)+Ltd!~&city=O'Fallon&reference=already%2527encoded&keep=*-._",
			wc_form_encoding: true,
		};

		prefilter( options );

		expect( options.data ).toBe(
			'company=Foo+%28Pty%29+Ltd%21%7E&city=O%27Fallon&reference=already%2527encoded&keep=*-._'
		);
		// Every value still decodes to what the shopper typed.
		const body = new URLSearchParams( options.data );
		expect( body.get( 'company' ) ).toBe( 'Foo (Pty) Ltd!~' );
		expect( body.get( 'city' ) ).toBe( "O'Fallon" );
		expect( body.get( 'reference' ) ).toBe( 'already%27encoded' );
		expect( body.get( 'keep' ) ).toBe( '*-._' );
	} );

	test( 'should produce the same body URLSearchParams would', () => {
		loadScript();
		const fields = {
			phone: '(212) 555-1234',
			name: "O'Brien!~",
			note: 'a*b-c._d',
		};
		const options = {
			processData: true,
			contentType: FORM,
			// jQuery turns %20 into + after serializing.
			data: global.jQuery.param( fields ).replace( /%20/g, '+' ),
			wc_form_encoding: true,
		};

		prefilter( options );

		expect( options.data ).toBe( new URLSearchParams( fields ).toString() );
	} );

	test( 'should serialize object data itself when jQuery has not yet done so', () => {
		// jQuery 4 runs prefilters before jQuery.param(), so object data
		// reaches the prefilter.
		loadScript();
		const options = {
			processData: true,
			contentType: FORM,
			data: { city: "O'Fallon", state: 'NY' },
			wc_form_encoding: true,
		};

		prefilter( options );

		expect( options.data ).toBe( 'city=O%27Fallon&state=NY' );
	} );

	test( 'should leave requests without the opt-in alone', () => {
		loadScript();
		const options = {
			processData: true,
			contentType: FORM,
			data: "city=O'Fallon",
		};

		prefilter( options );

		expect( options.data ).toBe( "city=O'Fallon" );
	} );

	test( 'should leave non-form bodies alone even when opted in', () => {
		loadScript();
		const json = {
			processData: false,
			contentType: 'application/json',
			data: '{"city":"O\'Fallon"}',
			wc_form_encoding: true,
		};
		const formData = { append: jest.fn() };
		const raw = {
			processData: false,
			contentType: false,
			data: formData,
			wc_form_encoding: true,
		};

		prefilter( json );
		prefilter( raw );

		expect( json.data ).toBe( '{"city":"O\'Fallon"}' );
		expect( raw.data ).toBe( formData );
	} );

	test( 'should not touch the original options the caller passed', () => {
		loadScript();
		// jQuery passes its own copy as `options` and the caller's object as
		// `originalOptions`; third-party prefilters read the latter as an object.
		const originalOptions = {
			data: { city: "O'Fallon", state: undefined },
			wc_form_encoding: true,
		};
		const options = {
			processData: true,
			contentType: FORM,
			data: "city=O'Fallon",
			wc_form_encoding: true,
		};

		prefilter( options, originalOptions );

		expect( options.data ).toBe( 'city=O%27Fallon' );
		expect( originalOptions.data ).toEqual( {
			city: "O'Fallon",
			state: undefined,
		} );
	} );
} );
