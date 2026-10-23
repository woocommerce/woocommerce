/**
 * @jest-environment jest-fixed-jsdom
 */

// Runs the woocommerce.js form encoding prefilter through the real jQuery
// request pipeline on the version WordPress bundles (3.7.1) and on jQuery 4,
// which runs prefilters before it serializes the data. The transport is a fake
// XMLHttpRequest that records what reaches the wire, so each test pins the
// body a browser would see.
describe.each( [
	[ 'jquery', '3.7.1' ],
	[ 'jquery4', '4.0.0' ],
] )(
	'woocommerce.js form encoding prefilter with real jQuery (%s %s)',
	( moduleName, expectedVersion ) => {
		let $;
		let sent;

		beforeEach( () => {
			sent = [];

			class FakeXMLHttpRequest {
				constructor() {
					this.readyState = 0;
					this.status = 0;
					this.headers = {};
				}
				open( method, url ) {
					this.method = method;
					this.url = url;
				}
				setRequestHeader( name, value ) {
					this.headers[ name ] = value;
				}
				getAllResponseHeaders() {
					return '';
				}
				abort() {
					this.aborted = true;
				}
				send( body ) {
					sent.push( { method: this.method, url: this.url, body } );
				}
			}
			// jQuery picks its transport when it loads, so the fake has to be in
			// place before jQuery is required.
			global.window.XMLHttpRequest = FakeXMLHttpRequest;
			global.window.Cookies = { get: jest.fn(), set: jest.fn() };
			global.window.woocommerce_params = {
				i18n_password_show: 'Show',
				i18n_password_hide: 'Hide',
			};

			jest.resetModules();
			$ = require( moduleName );
			expect( $.fn.jquery ).toBe( expectedVersion );
			global.window.jQuery = $;
			global.window.$ = $;
			global.jQuery = $;
			global.$ = $;
			require( '../woocommerce' );
		} );

		afterEach( () => {
			jest.clearAllMocks();
		} );

		const checkoutData = () => ( {
			security: 'nonce',
			country: 'US',
			city: "O'Fallon",
			address: "1 O'Fallon St (rear)",
			company: 'Foo (Pty) Ltd!~',
			// Fields removed from the form by a filter read as undefined.
			state: undefined,
			postcode: undefined,
			has_full_address: true,
			shipping_method: { 0: 'flat_rate:1' },
		} );

		test( 'should drop undefined fields, keep nested data and send what a browser form post would', () => {
			$.ajax( {
				type: 'POST',
				url: '/?wc-ajax=update_order_review',
				data: checkoutData(),
				wc_form_encoding: true,
			} );

			const expected = new URLSearchParams( {
				security: 'nonce',
				country: 'US',
				city: "O'Fallon",
				address: "1 O'Fallon St (rear)",
				company: 'Foo (Pty) Ltd!~',
				has_full_address: 'true',
				'shipping_method[0]': 'flat_rate:1',
			} ).toString();

			expect( sent ).toHaveLength( 1 );
			expect( sent[ 0 ].body ).toBe( expected );
			expect( sent[ 0 ].body ).not.toMatch( /[!'()~]/ );
			expect( sent[ 0 ].body ).not.toContain( 'state=' );
			expect( sent[ 0 ].body ).not.toContain( 'postcode=' );
		} );

		test( 'should leave requests without the opt-in as jQuery serializes them', () => {
			$.ajax( {
				type: 'POST',
				url: '/?wc-ajax=update_order_review',
				data: checkoutData(),
			} );

			expect( sent[ 0 ].body ).toBe(
				"security=nonce&country=US&city=O'Fallon&address=1+O'Fallon+St+(rear)" +
					'&company=Foo+(Pty)+Ltd!~&has_full_address=true&shipping_method%5B0%5D=flat_rate%3A1'
			);
		} );

		test( 'should encode an already serialized form string', () => {
			$.ajax( {
				type: 'POST',
				url: '/?wc-ajax=checkout',
				data: "billing_email=jo.o'brien%40example.com&billing_phone=(212)+555-1234&reference=already%2527encoded",
				wc_form_encoding: true,
			} );

			expect( sent[ 0 ].body ).toBe(
				'billing_email=jo.o%27brien%40example.com&billing_phone=%28212%29+555-1234&reference=already%2527encoded'
			);
		} );

		test( 'should let a prefilter registered later read the original data as an object', () => {
			// The shape HurryTimer and Global Seller Services use, added after ours.
			const seen = {};
			$.ajaxPrefilter( ( options, originalOptions ) => {
				seen.originalType = typeof originalOptions.data;
				seen.couponCode = originalOptions.data.coupon_code;
				seen.optionsData = options.data;
			} );

			$.ajax( {
				type: 'POST',
				url: '/?wc-ajax=apply_coupon',
				data: { security: 'nonce', coupon_code: "SAVE'10" },
				wc_form_encoding: true,
			} );

			expect( seen.originalType ).toBe( 'object' );
			expect( seen.couponCode ).toBe( "SAVE'10" );
			// Registered after ours, so it sees the encoded body in options.data
			// on every jQuery version.
			expect( seen.optionsData ).toBe( 'security=nonce&coupon_code=SAVE%2710' );
			expect( sent[ 0 ].body ).toBe( 'security=nonce&coupon_code=SAVE%2710' );
		} );

		test( 'should keep a global beforeSend and an ajaxSend handler working', () => {
			// The shapes FOX Currency Switcher (ajaxSend) and global ajaxSetup users rely on.
			const beforeSend = jest.fn( ( jqXHR, settings ) => {
				beforeSend.sawData = settings.data;
			} );
			$.ajaxSetup( { beforeSend } );
			$( document ).ajaxSend( ( event, jqXHR, settings ) => {
				settings.data += '&woocs_sk=key';
			} );

			$.ajax( {
				type: 'POST',
				url: '/?wc-ajax=apply_coupon',
				data: { security: 'nonce', coupon_code: "SAVE'10" },
				wc_form_encoding: true,
			} );

			expect( beforeSend ).toHaveBeenCalledTimes( 1 );
			expect( beforeSend.sawData ).toBe( 'security=nonce&coupon_code=SAVE%2710' );
			expect( sent[ 0 ].body ).toBe(
				'security=nonce&coupon_code=SAVE%2710&woocs_sk=key'
			);
		} );

		test( 'should honour the traditional option when it serializes array values', () => {
			// Only jQuery 4 hands the prefilter unserialized data, so this pins
			// the `options.traditional` pass-through on that path.
			$.ajax( {
				type: 'POST',
				url: '/?wc-ajax=update_order_review',
				data: { ids: [ 1, 2 ], city: "O'Fallon" },
				traditional: true,
				wc_form_encoding: true,
			} );

			expect( sent[ 0 ].body ).toBe( 'ids=1&ids=2&city=O%27Fallon' );
		} );

		test( 'should serialize an array of name/value pairs like serializeArray()', () => {
			$.ajax( {
				type: 'POST',
				url: '/?wc-ajax=update_order_review',
				data: [ { name: 'city', value: "O'Fallon" }, { name: 'state', value: 'NY' } ],
				wc_form_encoding: true,
			} );

			expect( sent[ 0 ].body ).toBe( 'city=O%27Fallon&state=NY' );
		} );

		test( 'should leave a JSON body from an earlier json prefilter alone', () => {
			// A dataType-specific prefilter runs before the '*' ones whatever the
			// load order; a body it turned into JSON must not be rewritten.
			$.ajaxPrefilter( 'json', ( options, originalOptions ) => {
				options.contentType = 'application/json';
				options.processData = false;
				options.data = JSON.stringify( originalOptions.data );
			} );

			$.ajax( {
				type: 'POST',
				url: '/?wc-ajax=checkout',
				dataType: 'json',
				data: { billing_last_name: "O'Brien" },
				wc_form_encoding: true,
			} );

			expect( sent[ 0 ].body ).toBe( '{"billing_last_name":"O\'Brien"}' );
		} );
	}
);
