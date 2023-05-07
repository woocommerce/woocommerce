const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const randomNum = new Date().getTime().toString();
const customer = {
	username: `customer${ randomNum }`,
	password: 'password',
	email: `customer${ randomNum }@woocommercecoree2etestsuite.com`,
};

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
		// create customer
		await api
			.post( 'customers', customer )
			.then( ( response ) => ( customer.id = response.data.id ) );
		// create an order
		await api
			.post( 'orders', {
				set_paid: false,
				billing: {
					first_name: 'Jane',
					last_name: 'Smith',
					email: customer.email,
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
			customer_id: customer.id,
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
		await api.delete( `customers/${ customer.id }`, { force: true } );
		await api.put( 'payment_gateways/cod', {
			enabled: false,
		} );
	} );

	test( 'allows customer to pay for their order in My Account', async ( {
		page,
	} ) => {
		await page.goto( 'my-account/orders/' );
		// sign in as the "customer" user
		await page.fill( '#username', customer.username );
		await page.fill( '#password', customer.password );
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
