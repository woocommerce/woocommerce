const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'Customer can pay for their order through My Account', () => {
	let productId, orderId;

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// add product
		await api
			.post( 'products', {
				name: 'Pay Order My Account',
				type: 'simple',
				regular_price: '15.77',
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		// create an order
		await api
			.post( 'orders', {
				set_paid: false,
				billing: {
					first_name: 'Jane',
					last_name: 'Smith',
					email: 'customer@woocommercecoree2etestsuite.com',
				},
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
		// once the order is created, assign it to our existing customer user
		await api.put( `orders/${ orderId }`, {
			customer_id: 2, // should be safe to use this ID. Saves an API call to retrieve.
		} );
		// enable COD payment
		await api.put( 'payment_gateways/cod', {
			enabled: true,
		} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `products/${ productId }`, {
			force: true,
		} );
		await api.delete( `orders/${ orderId }`, { force: true } );
		await api.put( 'payment_gateways/cod', {
			enabled: false,
		} );
	} );

	test( 'allows customer to pay for their order in My Account', async ( {
		page,
	} ) => {
		await page.goto( 'my-account/orders/' );
		// sign in as the "customer" user
		await page.fill( '#username', 'customer' );
		await page.fill( '#password', 'password' );
		await page.click( 'text=Log in' );

		await page.click( 'a.pay' );

		await expect( page.locator( 'h1.entry-title' ) ).toContainText(
			'Pay for order'
		);
		await page.click( '#place_order' );

		await expect( page.locator( 'h1.entry-title' ) ).toContainText(
			'Order received'
		);
	} );
} );
