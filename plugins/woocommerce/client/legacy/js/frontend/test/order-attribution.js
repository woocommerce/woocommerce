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

	test( 'keeps the first group within the checkout form', () => {
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
		).toHaveLength( 1 );
		expect( document.getElementById( 'checkout-first' ) ).not.toBeNull();
		expect( document.getElementById( 'outside-form' ) ).toBeNull();
		expect( document.getElementById( 'checkout-second' ) ).toBeNull();
		expect(
			document.querySelector( 'form[name="checkout"]' ).elements.namedItem(
				'wc_order_attribution_source_type'
			)
		).not.toBeNull();
	} );

	test( 'prefers the checkout form over an earlier non-checkout form', () => {
		document.body.innerHTML = `
			<form name="register">
				<wc-order-attribution-inputs id="register-form"></wc-order-attribution-inputs>
			</form>
			<form name="checkout">
				<wc-order-attribution-inputs id="checkout-form"></wc-order-attribution-inputs>
			</form>
		`;

		window.wc_order_attribution.setOrderTracking( false );

		expect( document.getElementById( 'checkout-form' ) ).not.toBeNull();
		expect( document.getElementById( 'register-form' ) ).toBeNull();
	} );

	test( 'keeps the first group within another form when no checkout form exists', () => {
		document.body.innerHTML = `
			<wc-order-attribution-inputs id="outside-form"></wc-order-attribution-inputs>
			<form name="register">
				<wc-order-attribution-inputs id="register-form"></wc-order-attribution-inputs>
			</form>
		`;

		window.wc_order_attribution.setOrderTracking( false );

		expect( document.getElementById( 'register-form' ) ).not.toBeNull();
		expect( document.getElementById( 'outside-form' ) ).toBeNull();
	} );

	test( 'keeps the first group when no group belongs to a form', () => {
		document.body.innerHTML = `
			<wc-order-attribution-inputs id="first"></wc-order-attribution-inputs>
			<wc-order-attribution-inputs id="second"></wc-order-attribution-inputs>
		`;

		window.wc_order_attribution.setOrderTracking( false );

		expect( document.getElementById( 'first' ) ).not.toBeNull();
		expect( document.getElementById( 'second' ) ).toBeNull();
	} );
} );
