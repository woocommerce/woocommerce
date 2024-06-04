const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

let productId, orderId;
const productName = 'Simple Product Name';
const productPrice = '15.99';

test.describe( 'WooCommerce Merchant Flow: Orders > Customer Payment Page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

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
				name: productName,
				type: 'simple',
				regular_price: productPrice,
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		// create an order
		await api
			.post( 'orders', {
				line_items: [
					{
						product_id: productId,
						quantity: 1,
					},
				],
			} )
			.then( ( response ) => {
				orderId = response.data.id;
			} );
		// enable bank transfer as a payment option
		await api.put( 'payment_gateways/bacs', {
			enabled: 'true',
		} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `products/${ productId }`, { force: true } );
		await api.delete( `orders/${ orderId }`, { force: true } );
		await api.put( 'payment_gateways/bacs', { enabled: 'false' } );
	} );

	test( 'should show the customer payment page link on a pending order', async ( {
		page,
	} ) => {
		await page.goto( `wp-admin/post.php?post=${ orderId }&action=edit` );

		// verify that the order is pending payment
		await expect(
			page.locator( '#select2-order_status-container' )
		).toContainText( 'Pending payment' );

		//verify that the customer payment page link is displayed
		await expect(
			page.locator( 'label[for=order_status] > a' )
		).toContainText( 'Customer payment page →' );
	} );

	test( 'should load the customer payment page', async ( { page } ) => {
		await page.goto( `wp-admin/post.php?post=${ orderId }&action=edit` );

		// visit the page
		await page.locator( 'label[for=order_status] > a' ).click();

		// verify we landed on the customer payment page
		await expect(
			page.getByRole( 'button', { name: 'Pay for order' } )
		).toBeVisible();
		await expect( page.locator( 'td.product-name' ) ).toContainText(
			productName
		);
		await expect(
			page.locator( 'span.woocommerce-Price-amount.amount >> nth=0' )
		).toContainText( productPrice );
	} );

	test( 'can pay for the order through the customer payment page', async ( {
		page,
	} ) => {
		// key required, so can't go directly to the customer payment page
		await page.goto( `wp-admin/post.php?post=${ orderId }&action=edit` );
		await page.locator( 'label[for=order_status] > a' ).click();

		// pay for the order
		await page.locator( 'button#place_order' ).click();

		// Verify we landed on the order received page
		await expect(
			page.getByText( 'Your order has been received' )
		).toBeVisible();
		await expect(
			page.getByText( `Order number: ${ orderId }` )
		).toBeVisible();
		await expect(
			await page.getByText( `Total: $${ productPrice }` ).count()
		).toBeGreaterThan( 0 );
	} );
} );
