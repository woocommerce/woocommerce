const { test, expect } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const simpleProductName = 'A Simple Product';
const singleProductFullPrice = '110.00';
const singleProductSalePrice = '55.00';
const coupons = [
	{
		code: '5fixedcart',
		discount_type: 'fixed_cart',
		amount: '5.00',
	},
	{
		code: '50percoff',
		discount_type: 'percent',
		amount: '50',
	},
	{
		code: '10fixedproduct',
		discount_type: 'fixed_product',
		amount: '10.00',
	},
];

const pageTitle = 'Cart Block';
const pageSlug = pageTitle.replace( / /gi, '-' ).toLowerCase();

let product1Id;

test.describe( 'Cart Block Applying Coupons', () => {
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
		// add a product
		await api
			.post( 'products', {
				name: simpleProductName,
				type: 'simple',
				regular_price: singleProductFullPrice,
				sale_price: singleProductSalePrice,
			} )
			.then( ( response ) => {
				product1Id = response.data.id;
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

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.post( 'products/batch', {
			delete: [ product1Id ],
		} );
		await api.post( 'coupons/batch', { delete: [ ...couponBatchId ] } );
	} );

	test.beforeEach( async ( { context } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();
	} );

	test( 'can create Cart Block page', async ( { page } ) => {
		// create a new page with cart block
		await page.goto( 'wp-admin/post-new.php?post_type=page' );
		await page.waitForLoadState( 'networkidle' );
		await page.locator( 'input[name="log"]' ).fill( admin.username );
		await page.locator( 'input[name="pwd"]' ).fill( admin.password );
		await page.locator( 'text=Log In' ).click();

		// Close welcome popup if prompted
		try {
			await page
				.getByLabel( 'Close', { exact: true } )
				.click( { timeout: 5000 } );
		} catch ( error ) {
			console.log( "Welcome modal wasn't present, skipping action." );
		}

		await page
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( pageTitle );
		await page.getByRole( 'button', { name: 'Add default block' } ).click();
		await page
			.getByRole( 'document', {
				name: 'Empty block; start writing or type forward slash to choose a block',
			} )
			.fill( '/cart' );
		await page.keyboard.press( 'Enter' );
		await page
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await page
			.getByRole( 'region', { name: 'Editor publish' } )
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await expect(
			page.getByText( `${ pageTitle } is now live.` )
		).toBeVisible();
	} );

	test( 'allows cart block to apply coupon of any type', async ( {
		page,
	} ) => {
		const totals = [ '$50.00', '$27.50', '$45.00' ];
		// add product to cart block
		await page.goto( `/shop/?add-to-cart=${ product1Id }` );
		await page.waitForLoadState( 'networkidle' );
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();

		// apply all coupon types
		for ( let i = 0; i < coupons.length; i++ ) {
			await page.getByRole( 'button', { name: 'Add a coupon' } ).click();
			await page
				.locator( '#wc-block-components-totals-coupon__input-0' )
				.fill( coupons[ i ].code );
			await page.getByText( 'Apply', { exact: true } ).click();
			await expect(
				page
					.locator( '.wc-block-components-notice-banner__content' )
					.getByText(
						`Coupon code "${ coupons[ i ].code }" has been applied to your cart.`
					)
			).toBeVisible();
			await expect(
				page.locator(
					'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
				)
			).toHaveText( totals[ i ] );
			await page
				.getByLabel( `Remove coupon "${ coupons[ i ].code }"` )
				.click();
			await expect(
				page
					.locator( '.wc-block-components-notice-banner__content' )
					.getByText(
						`Coupon code "${ coupons[ i ].code }" has been removed from your cart.`
					)
			).toBeVisible();
		}
	} );

	test( 'allows cart block to apply multiple coupons', async ( { page } ) => {
		const totals = [ '$50.00', '$22.50', '$12.50' ];
		const totalsReverse = [ '$17.50', '$45.00', '$55.00' ];
		const discounts = [ '-$5.00', '-$32.50', '-$42.50' ];
		// add product to cart block
		await page.goto( `/shop/?add-to-cart=${ product1Id }` );
		await page.waitForLoadState( 'networkidle' );
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();

		// add all coupons and verify prices
		for ( let i = 0; i < coupons.length; i++ ) {
			await page.getByRole( 'button', { name: 'Add a coupon' } ).click();
			await page
				.locator( '#wc-block-components-totals-coupon__input-0' )
				.fill( coupons[ i ].code );
			await page.getByText( 'Apply', { exact: true } ).click();
			await expect(
				page
					.locator( '.wc-block-components-notice-banner__content' )
					.getByText(
						`Coupon code "${ coupons[ i ].code }" has been applied to your cart.`
					)
			).toBeVisible();
			await expect(
				page.locator(
					'.wc-block-components-totals-discount > .wc-block-components-totals-item__value'
				)
			).toHaveText( discounts[ i ] );
			await expect(
				page.locator(
					'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
				)
			).toHaveText( totals[ i ] );
		}

		for ( let i = 0; i < coupons.length; i++ ) {
			await page
				.getByLabel( `Remove coupon "${ coupons[ i ].code }"` )
				.click();
			await expect(
				page.locator(
					'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
				)
			).toHaveText( totalsReverse[ i ] );
		}
	} );

	test( 'prevents cart block applying same coupon twice', async ( {
		page,
	} ) => {
		// add product to cart block
		await page.goto( `/shop/?add-to-cart=${ product1Id }` );
		await page.waitForLoadState( 'networkidle' );
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();

		// try to add two same coupons and verify the error message
		await page.getByRole( 'button', { name: 'Add a coupon' } ).click();
		await page
			.locator( '#wc-block-components-totals-coupon__input-0' )
			.fill( coupons[ 0 ].code );
		await page.getByText( 'Apply', { exact: true } ).click();
		await expect(
			page
				.locator( '.wc-block-components-notice-banner__content' )
				.getByText(
					`Coupon code "${ coupons[ 0 ].code }" has been applied to your cart.`
				)
		).toBeVisible();
		await page.getByRole( 'button', { name: 'Add a coupon' } ).click();
		await page
			.locator( '#wc-block-components-totals-coupon__input-0' )
			.fill( coupons[ 0 ].code );
		await page.getByText( 'Apply', { exact: true } ).click();
		await expect(
			page
				.getByRole( 'alert' )
				.getByText(
					`Coupon code "${ coupons[ 0 ].code }" has already been applied.`
				)
		).toBeVisible();
	} );
} );
