/**
 * @jest-environment jest-fixed-jsdom
 */

describe( 'Order attribution input deduplication', () => {
	beforeAll( () => {
		window.wc_order_attribution = {
			fields: {
				source_type: 'current.typ',
			},
			params: {
				allowTracking: false,
				prefix: 'wc_order_attribution_',
			},
		};

		require( '../order-attribution' );
	} );

	beforeEach( () => {
		document.body.innerHTML = '';
	} );

	afterAll( () => {
		delete window.wc_order_attribution;
	} );

	test( 'keeps checkout attribution when an out-of-form group appears first', () => {
		document.body.innerHTML = `
			<wc-order-attribution-inputs id="outside-form"></wc-order-attribution-inputs>
			<form name="checkout">
				<wc-order-attribution-inputs id="checkout-first"></wc-order-attribution-inputs>
				<wc-order-attribution-inputs id="checkout-second"></wc-order-attribution-inputs>
			</form>
		`;

		window.wc_order_attribution.setOrderTracking( false );

		expect(
			document.querySelectorAll( 'wc-order-attribution-inputs' )
		).toHaveLength( 2 );
		expect( document.getElementById( 'checkout-first' ) ).not.toBeNull();
		expect( document.getElementById( 'outside-form' ) ).not.toBeNull();
		expect( document.getElementById( 'checkout-second' ) ).toBeNull();
		expect(
			document.querySelector( 'form[name="checkout"]' ).elements.namedItem(
				'wc_order_attribution_source_type'
			)
		).not.toBeNull();
	} );

	test( 'keeps one group in each form', () => {
		document.body.innerHTML = `
			<form name="register">
				<wc-order-attribution-inputs id="register-first"></wc-order-attribution-inputs>
				<wc-order-attribution-inputs id="register-second"></wc-order-attribution-inputs>
			</form>
			<form name="checkout">
				<wc-order-attribution-inputs id="checkout-first"></wc-order-attribution-inputs>
				<wc-order-attribution-inputs id="checkout-second"></wc-order-attribution-inputs>
			</form>
		`;

		window.wc_order_attribution.setOrderTracking( false );

		expect(
			document.querySelectorAll( 'wc-order-attribution-inputs' )
		).toHaveLength( 2 );
		expect( document.getElementById( 'register-first' ) ).not.toBeNull();
		expect( document.getElementById( 'register-second' ) ).toBeNull();
		expect( document.getElementById( 'checkout-first' ) ).not.toBeNull();
		expect( document.getElementById( 'checkout-second' ) ).toBeNull();
		expect(
			document.querySelector( 'form[name="register"]' ).elements.namedItem(
				'wc_order_attribution_source_type'
			)
		).not.toBeNull();
		expect(
			document.querySelector( 'form[name="checkout"]' ).elements.namedItem(
				'wc_order_attribution_source_type'
			)
		).not.toBeNull();
	} );

	test( 'keeps the first document-owned group', () => {
		document.body.innerHTML = `
			<wc-order-attribution-inputs id="first"></wc-order-attribution-inputs>
			<wc-order-attribution-inputs id="second"></wc-order-attribution-inputs>
		`;

		window.wc_order_attribution.setOrderTracking( false );

		expect( document.getElementById( 'first' ) ).not.toBeNull();
		expect( document.getElementById( 'second' ) ).toBeNull();
	} );
} );

describe( 'Order attribution sourcebuster.js init', () => {
	afterEach( () => {
		delete window.wc_order_attribution;
		delete window.sbjs;
		jest.restoreAllMocks();
	} );

	test( 'passes the cookie fields to sbjs.init', () => {
		window.sbjs = { init: jest.fn(), get: {} };
		window.wc_order_attribution = {
			fields: {
				source_type: 'current.typ',
			},
			params: {
				allowTracking: false,
				prefix: 'wc_order_attribution_',
				cookieFields: [ 'current.typ', 'first.src' ],
			},
		};
		// The first suite already defined the custom element.
		jest.spyOn( window.customElements, 'define' ).mockImplementation( () => {} );
		jest.isolateModules( () => require( '../order-attribution' ) );

		window.wc_order_attribution.setOrderTracking( true );

		expect( window.sbjs.init ).toHaveBeenCalledWith(
			expect.objectContaining( {
				fields: [ 'current.typ', 'first.src' ],
			} )
		);
	} );
} );
