const {
	goToPageEditor,
	fillPageTitle,
	insertBlockByShortcut,
	publishPage,
} = require( '../../utils/editor' );
const { addAProductToCart } = require( '../../utils/cart' );
const { test: baseTest, expect } = require( '../../fixtures/fixtures' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { random } = require( '../../utils/helpers' );

const simpleProductName = `Checkout Coupons Product ${ random() }`;
const singleProductFullPrice = '110.00';
const singleProductSalePrice = '55.00';
const coupons = [
	{
		code: '5fixedcheckout',
		discount_type: 'fixed_cart',
		amount: '5.00',
	},
	{
		code: '50percoffcheckout',
		discount_type: 'percent',
		amount: '50',
	},
	{
		code: '10fixedproductcheckout',
		discount_type: 'fixed_product',
		amount: '10.00',
	},
];
const couponLimitedCode = '10fixedcheckoutlimited';
const customerBilling = {
	email: 'john.doe.merchant.test@example.com',
};

let productId, orderId, limitedCouponId;

baseTest.describe( 'Checkout Block Applying Coupons', () => {
	const test = baseTest.extend( {
		storageState: process.env.ADMINSTATE,
		testPageTitle: `Checkout Block ${ random() }`,
		page: async ( { context, page, testPage }, use ) => {
			await goToPageEditor( { page } );
			await fillPageTitle( page, testPage.title );
			await insertBlockByShortcut( page, '/checkout' );
			await publishPage( page, testPage.title );

			await context.clearCookies();

			await addAProductToCart( page, productId );
			await page.goto( testPage.slug );
			await expect(
				page.getByRole( 'heading', { name: testPage.title } )
			).toBeVisible();

			await use( page );
		},
	} );

	const couponBatchId = [];

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
				productId = response.data.id;
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
		// add limited coupon
		await api
			.post( 'coupons', {
				code: couponLimitedCode,
				discount_type: 'fixed_cart',
				amount: '10.00',
				usage_limit: 1,
				usage_count: 1,
			} )
			.then( ( response ) => {
				limitedCouponId = response.data.id;
			} );
		// add order with applied limited coupon
		await api
			.post( 'orders', {
				status: 'processing',
				billing: customerBilling,
				coupon_lines: [
					{
						code: couponLimitedCode,
					},
				],
			} )
			.then( ( response ) => {
				orderId = response.data.id;
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
			delete: [ productId ],
		} );
		await api.post( 'coupons/batch', {
			delete: [ ...couponBatchId, limitedCouponId ],
		} );
		await api.post( 'orders/batch', {
			delete: [ orderId ],
		} );
	} );

	test( 'allows checkout block to apply coupon of any type', async ( {
		page,
	} ) => {
		const totals = [ '$50.00', '$27.50', '$45.00' ];

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

	test( 'allows checkout block to apply multiple coupons', async ( {
		page,
	} ) => {
		const totals = [ '$50.00', '$22.50', '$12.50' ];
		const totalsReverse = [ '$17.50', '$45.00', '$55.00' ];
		const discounts = [ '-$5.00', '-$32.50', '-$42.50' ];

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

	test( 'prevents checkout block applying same coupon twice', async ( {
		page,
	} ) => {
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

	test( 'prevents checkout block applying coupon with usage limit', async ( {
		page,
	} ) => {
		// add coupon with usage limit
		await page.getByRole( 'button', { name: 'Add a coupon' } ).click();
		await page
			.locator( '#wc-block-components-totals-coupon__input-0' )
			.fill( couponLimitedCode );
		await page.getByText( 'Apply', { exact: true } ).click();
		await expect(
			page
				.getByRole( 'alert' )
				.getByText( 'Coupon usage limit has been reached.' )
		).toBeVisible();
	} );
} );
