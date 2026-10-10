/**
 * @jest-environment jest-fixed-jsdom
 */

// Runs the woocommerce.js apostrophe prefilter through the real jQuery request
// pipeline, so the test pins the order the design relies on: jQuery copies the
// options (dropping undefined fields), serializes the data, and only then runs
// prefilters. The transport is a fake XMLHttpRequest that records what reaches
// the wire.
describe( 'woocommerce.js apostrophe prefilter with real jQuery', () => {
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
		$ = require( 'jquery' );
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
		address: "1 O'Fallon St",
		// Fields removed from the form by a filter read as undefined.
		state: undefined,
		postcode: undefined,
		has_full_address: true,
		shipping_method: { 0: 'flat_rate:1' },
	} );

	test( 'should drop undefined fields, keep nested data and encode apostrophes on the wire', () => {
		$.ajax( {
			type: 'POST',
			url: '/?wc-ajax=update_order_review',
			data: checkoutData(),
			wc_encode_apostrophes: true,
		} );

		expect( sent ).toHaveLength( 1 );
		expect( sent[ 0 ].body ).toBe(
			'security=nonce&country=US&city=O%27Fallon&address=1+O%27Fallon+St&has_full_address=true&shipping_method%5B0%5D=flat_rate%3A1'
		);
		expect( sent[ 0 ].body ).not.toContain( "'" );
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
			"security=nonce&country=US&city=O'Fallon&address=1+O'Fallon+St&has_full_address=true&shipping_method%5B0%5D=flat_rate%3A1"
		);
	} );

	test( 'should encode an already serialized form string', () => {
		$.ajax( {
			type: 'POST',
			url: '/?wc-ajax=checkout',
			data: "billing_email=jo.o'brien%40example.com&reference=already%2527encoded",
			wc_encode_apostrophes: true,
		} );

		expect( sent[ 0 ].body ).toBe(
			'billing_email=jo.o%27brien%40example.com&reference=already%2527encoded'
		);
	} );

	test( 'should let a prefilter registered later read the original data as an object', () => {
		// The shape HurryTimer and Global Seller Services use. Plugin scripts load
		// after woocommerce.js, so their prefilters run after ours.
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
			wc_encode_apostrophes: true,
		} );

		expect( seen.originalType ).toBe( 'object' );
		expect( seen.couponCode ).toBe( "SAVE'10" );
		// Registered after ours, so it sees the encoded body in options.data.
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
			wc_encode_apostrophes: true,
		} );

		expect( beforeSend ).toHaveBeenCalledTimes( 1 );
		expect( beforeSend.sawData ).toBe( 'security=nonce&coupon_code=SAVE%2710' );
		expect( sent[ 0 ].body ).toBe(
			'security=nonce&coupon_code=SAVE%2710&woocs_sk=key'
		);
	} );

	test( 'should honour a global beforeSend that aborts the request', () => {
		$.ajaxSetup( { beforeSend: () => false } );

		$.ajax( {
			type: 'POST',
			url: '/?wc-ajax=update_order_review',
			data: checkoutData(),
			wc_encode_apostrophes: true,
		} );

		expect( sent ).toHaveLength( 0 );
	} );
} );
