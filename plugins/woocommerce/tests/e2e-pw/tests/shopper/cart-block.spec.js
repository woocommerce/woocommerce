const { test, expect, request } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const simpleProductName = 'Single Simple Product';
const simpleProductDesc = 'Lorem ipsum dolor sit amet.';
const singleProductPrice = '110.00';
const singleProductSalePrice = '55.00';
const firstCrossSellProductPrice = '25.00';
const secondCrossSellProductPrice = '15.00';
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
const productSlug = simpleProductName.replace( / /gi, '-' ).toLowerCase();

let product1Id, product2Id, product3Id;

test.describe( 'Cart Block page', () => {
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
		// add 3 products
		await api
			.post( 'products', {
				name: `${ simpleProductName } Cross-Sell 1`,
				type: 'simple',
				regular_price: firstCrossSellProductPrice,
			} )
			.then( ( response ) => {
				product2Id = response.data.id;
			} );
		await api
			.post( 'products', {
				name: `${ simpleProductName } Cross-Sell 2`,
				type: 'simple',
				regular_price: secondCrossSellProductPrice,
			} )
			.then( ( response ) => {
				product3Id = response.data.id;
			} );
		await api
			.post( 'products', {
				name: simpleProductName,
				description: simpleProductDesc,
				type: 'simple',
				regular_price: singleProductPrice,
				sale_price: singleProductSalePrice,
				cross_sell_ids: [ product2Id, product3Id ],
				manage_stock: true,
				stock_quantity: 2,
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
			delete: [ product1Id, product2Id, product3Id ],
		} );
		await api.post( 'coupons/batch', { delete: [ ...couponBatchId ] } );
		const base64auth = Buffer.from(
			`${ admin.username }:${ admin.password }`
		).toString( 'base64' );
		const wpApi = await request.newContext( {
			baseURL: `${ baseURL }/wp-json/wp/v2/`,
			extraHTTPHeaders: {
				Authorization: `Basic ${ base64auth }`,
			},
		} );
		let response = await wpApi.get( `pages` );
		const allPages = await response.json();
		await allPages.forEach( async ( page ) => {
			if ( page.title.rendered === pageTitle ) {
				response = await wpApi.delete( `pages/${ page.id }`, {
					data: {
						force: true,
					},
				} );
			}
		} );
	} );

	test.beforeEach( async ( { context } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();
	} );

	test( 'can see empty cart block', async ( { page } ) => {
		// create a new page with cart block
		await page.goto( 'wp-admin/post-new.php?post_type=page' );
		await page.locator( 'input[name="log"]' ).fill( admin.username );
		await page.locator( 'input[name="pwd"]' ).fill( admin.password );
		await page.locator( 'text=Log In' ).click();
		await page.waitForLoadState( 'networkidle' );
		const welcomeModalVisible = await page
			.getByRole( 'heading', {
				name: 'Welcome to the block editor',
			} )
			.isVisible();
		if ( welcomeModalVisible ) {
			await page.getByRole( 'button', { name: 'Close' } ).click();
		}
		await page
			.getByRole( 'textbox', { name: 'Add Title' } )
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

		// go to the page to test empty cart block
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();
		await expect(
			page.getByText( 'Your cart is currently empty!' )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: 'Browse store' } )
		).toBeVisible();
		await page.getByRole( 'link', { name: 'Browse store' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Shop' } )
		).toBeVisible();
	} );

	test( 'can add product to cart block, increase quantity, manage cross-sell products and proceed to checkout', async ( {
		page,
	} ) => {
		// add product to cart block
		await page.goto( `product/${ productSlug }` );
		await page.getByRole( 'button', { name: 'Add to cart' } ).click();
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: simpleProductName, exact: true } )
		).toBeVisible();
		await expect( page.getByText( simpleProductDesc ) ).toBeVisible();
		await expect(
			page.getByText( `Save $${ singleProductSalePrice }` )
		).toBeVisible();

		// increase product quantity to its maximum
		await expect( page.getByText( '2 left in stock' ) ).toBeVisible();
		await page.getByRole( 'button' ).filter( { hasText: '＋' } ).click();
		await expect(
			page.getByRole( 'button' ).filter( { hasText: '＋' } )
		).toBeDisabled();

		// add cross-sell products to cart
		await expect(
			page.getByRole( 'heading', { name: 'You may be interested in…' } )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', {
				name: `${ simpleProductName } Cross-Sell 1`,
				exact: true,
			} )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', {
				name: `${ simpleProductName } Cross-Sell 2`,
				exact: true,
			} )
		).toBeVisible();
		await page
			.getByRole( 'link', {
				name: `${ simpleProductName } Cross-Sell 1`,
				exact: true,
			} )
			.click();
		await page.getByRole( 'button', { name: 'Add to cart' } ).click();
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();
		await page.locator( '.add_to_cart_button' ).click();
		await expect(
			page.getByRole( 'heading', { name: 'You may be interested in…' } )
		).toBeHidden();
		await expect(
			page.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
		).toHaveText( '$95.00' );

		// remove cross-sell products from cart
		await page.locator( ':nth-match(:text("Remove item"), 3)' ).click();
		await page.locator( ':nth-match(:text("Remove item"), 2)' ).click();
		await expect(
			page.getByRole( 'heading', { name: 'You may be interested in…' } )
		).toBeVisible();

		// check if the link to proceed to the checkout exists
		await expect(
			page.getByRole( 'link', {
				name: 'Proceed to Checkout',
			} )
		).toBeVisible();

		// remove product from cart
		await page.locator( ':text("Remove item")' ).click();
		await expect(
			page.getByText( 'Your cart is currently empty!' )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: 'Browse store' } )
		).toBeVisible();
	} );

	test( 'allows cart block to apply coupon of any type', async ( {
		page,
	} ) => {
		const totals = [ '$50.00', '$27.50', '$45.00' ];
		// add product to cart block
		await page.goto( `product/${ productSlug }` );
		await page.getByRole( 'button', { name: 'Add to cart' } ).click();
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
			await page.locator( '.wc-block-components-chip__remove' ).click();
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
		// add product to cart block
		await page.goto( `product/${ productSlug }` );
		await page.getByRole( 'button', { name: 'Add to cart' } ).click();
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();

		// add all coupon types
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
		}
	} );

	test( 'prevents cart block applying same coupon twice', async ( {
		page,
	} ) => {
		// add product to cart block
		await page.goto( `product/${ productSlug }` );
		await page.getByRole( 'button', { name: 'Add to cart' } ).click();
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
