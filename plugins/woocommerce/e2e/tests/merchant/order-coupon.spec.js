const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

let productId, couponId, orderId;

const productPrice = '9.99';
const couponAmount = '5';
const discountedPrice = ( productPrice - couponAmount ).toString();

test.describe( 'WooCommerce Orders > Apply Coupon', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// create a simple product
		await api
			.post( 'products', {
				name: 'Simple Product',
				type: 'simple',
				regular_price: productPrice,
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		// create a $5 off coupon
		await api
			.post( 'coupons', {
				code: '5off',
				discount_type: 'fixed_product',
				amount: couponAmount,
			} )
			.then( ( response ) => {
				couponId = response.data.id;
			} );
		// create order
		await api
			.post( 'orders', {
				line_items: [
					{
						product_id: productId,
						quantity: 1,
					},
				],
				coupon_lines: [
					{
						code: '5off',
					},
				],
			} )
			.then( ( response ) => {
				orderId = response.data.id;
			} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		// cleans up product, coupon and order after run
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `products/${ productId }`, { force: true } );
		await api.delete( `coupons/${ couponId }`, { force: true } );
		await api.delete( `orders/${ orderId }`, { force: true } );
	} );

	test( 'can apply a coupon', async ( { page } ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=shop_order' );

		// open modal for adding line items
		await page.click( 'button.add-line-item' );
		await page.click( 'button.add-order-item' );

		// search for product to add
		await page.click( 'text=Search for a product…' );
		await page.type(
			'input:below(:text("Search for a product…"))',
			'Simple Product'
		);
		await page.click(
			'li.select2-results__option.select2-results__option--highlighted'
		);

		await page.click( 'button#btn-ok' );

		// apply coupon
		page.on( 'dialog', ( dialog ) => dialog.accept( '5off' ) );
		await page.click( 'button.add-coupon' );

		await expect( page.locator( 'text=5off' ) ).toBeVisible();
		await expect(
			page.locator( '.wc-order-totals td.label >> nth=1' )
		).toContainText( 'Coupon(s)' );
		await expect(
			page.locator( '.wc-order-totals td.label >> nth=2' )
		).toContainText( 'Order Total' );
		await expect(
			page.locator( '.wc-order-totals td.total >> nth=1' )
		).toContainText( couponAmount );
		await expect(
			page.locator( '.wc-order-totals td.total >> nth=2' )
		).toContainText( discountedPrice );
	} );

	test( 'can remove a coupon', async ( { page } ) => {
		await page.goto( `/wp-admin/post.php?post=${ orderId }&action=edit` );
		// assert that there is a coupon on the order
		await expect( page.locator( 'text=5off' ) ).toBeVisible();
		await expect(
			page.locator( '.wc-order-totals td.label >> nth=1' )
		).toContainText( 'Coupon(s)' );
		await expect(
			page.locator( '.wc-order-totals td.label >> nth=2' )
		).toContainText( 'Order Total' );
		await expect(
			page.locator( '.wc-order-totals td.total >> nth=1' )
		).toContainText( couponAmount );
		await expect(
			page.locator( '.wc-order-totals td.total >> nth=2' )
		).toContainText( discountedPrice );
		// remove the coupon
		await page.dispatchEvent( 'a.remove-coupon', 'click' ); // have to use dispatchEvent because nothing visible to click on

		// make sure the coupon was removed
		await expect( page.locator( 'text=5off' ) ).not.toBeVisible();
		await expect(
			page.locator( '.wc-order-totals td.label >> nth=1' )
		).toContainText( 'Order Total' );
		await expect(
			page.locator( '.wc-order-totals td.total >> nth=1' )
		).toContainText( '$9.99' );
	} );
} );
