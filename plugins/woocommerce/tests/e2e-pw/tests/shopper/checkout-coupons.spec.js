const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const firstProductName = 'Coupon checkout test product';
const coupons = [
	{
		code: 'fixed-cart-off-checkout',
		discount_type: 'fixed_cart',
		amount: '5.00',
	},
	{
		code: 'percent-off-checkout',
		discount_type: 'percent',
		amount: '50',
	},
	{
		code: 'fixed-product-off-checkout',
		discount_type: 'fixed_product',
		amount: '7.00',
	},
];
const discounts = [ '$5.00', '$10.00', '$7.00' ];
const totals = [ '$15.00', '$10.00', '$13.00' ];

test.describe( 'Checkout coupons', () => {
	let firstProductId;
	const couponBatchId = new Array();

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// make sure the currency is USD
		await api.put( 'settings/general/woocommerce_currency', {
			value: 'USD',
		} );
		// add product
		await api
			.post( 'products', {
				name: firstProductName,
				type: 'simple',
				regular_price: '20.00',
			} )
			.then( ( response ) => {
				firstProductId = response.data.id;
			} );
		// add coupons
		await api
			.post( 'coupons/batch', {
				create: coupons,
			} )
			.then( ( response ) => {
				for ( let i = 0; i < response.data.create.length; i++ ) {
					couponBatchId.push( response.data.create[ i ].id );
				}
			} );
	} );

	test.beforeEach( async ( { page, context } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();

		// all tests use the first product
		await page.goto( `/shop/?add-to-cart=${ firstProductId }` );
		await page.waitForLoadState( 'networkidle' );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `products/${ firstProductId }`, {
			force: true,
		} );
		await api.post( 'coupons/batch', { delete: [ ...couponBatchId ] } );
	} );

	for ( let i = 0; i < coupons.length; i++ ) {
		test( `allows checkout to apply coupon of type ${ coupons[ i ].discount_type }`, async ( {
			page,
		} ) => {
			await page.goto( '/checkout/', { waitUntil: 'networkidle' } );
			await page.locator( 'text=Click here to enter your code' ).click();
			await page.locator( '#coupon_code' ).fill( coupons[ i ].code );
			await page.locator( 'text=Apply coupon' ).click();

			await expect( page.getByText( 'Coupon code applied successfully.' ) ).toBeVisible();
			await expect(
				page.locator( '.cart-discount .amount' )
				).toContainText( discounts[ i ] );
			await expect(
				page.locator( '.order-total .amount' )
				).toContainText( totals[ i ] );
		} );
	}

	test( 'prevents checkout applying same coupon twice', async ( {
		page,
	} ) => {
		await page.goto( '/checkout/' );
		await page.locator( 'text=Click here to enter your code' ).click();
		await page.locator( '#coupon_code' ).fill( coupons[ 0 ].code );
		await page.locator( 'text=Apply coupon' ).click();
		// successful first time
		await expect( page.getByText( 'Coupon code applied successfully.' ) ).toBeVisible();
		// try to apply the same coupon
		await page.locator( 'text=Click here to enter your code' ).click();
		await page.locator( '#coupon_code' ).fill( coupons[ 0 ].code );
		await page.locator( 'text=Apply coupon' ).click();
		// error received
		await expect( page.getByText( 'Coupon code already applied!' ) ).toBeVisible();
		// check cart total
		await expect( page.locator( '.cart-discount .amount' ) ).toContainText(
			discounts[ 0 ]
		);
		await expect( page.locator( '.order-total .amount' ) ).toContainText(
			totals[ 0 ]
		);
	} );

	test( 'allows checkout to apply multiple coupons', async ( { page } ) => {
		await page.goto( '/checkout/' );
		await page.locator( 'text=Click here to enter your code' ).click();
		await page.locator( '#coupon_code' ).fill( coupons[ 0 ].code );
		await page.locator( 'text=Apply coupon' ).click();
		// successful
		await expect( page.getByText( 'Coupon code applied successfully.' ) ).toBeVisible();
		await page.locator( 'text=Click here to enter your code' ).click();
		await page.locator( '#coupon_code' ).fill( coupons[ 2 ].code );
		await page.locator( 'text=Apply coupon' ).click();
		// successful
		await expect( page.getByText( 'Coupon code applied successfully.' ) ).toBeVisible();
		// check cart total
		await expect(
			page.locator( '.cart-discount .amount >> nth=0' )
		).toContainText( discounts[ 0 ] );
		await expect(
			page.locator( '.cart-discount .amount >> nth=1' )
		).toContainText( discounts[ 2 ] );
		await expect( page.locator( '.order-total .amount' ) ).toContainText(
			'$8.00'
		);
	} );

	test( 'restores checkout total when coupons are removed', async ( {
		page,
	} ) => {
		await page.goto( '/checkout/' );
		await page.locator( 'text=Click here to enter your code' ).click();
		await page.locator( '#coupon_code' ).fill( coupons[ 0 ].code );
		await page.locator( 'text=Apply coupon' ).click();

		// confirm numbers
		await expect( page.locator( '.cart-discount .amount' ) ).toContainText(
			discounts[ 0 ]
		);
		await expect( page.locator( '.order-total .amount' ) ).toContainText(
			totals[ 0 ]
		);

		await page.locator( 'a.woocommerce-remove-coupon' ).click();

		await expect( page.locator( '.order-total .amount' ) ).toContainText(
			'$20.00'
		);
	} );
} );
