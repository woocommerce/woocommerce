const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const searchString = 'John Doe';
const itemName = 'Wanted Product';

const customerBilling = {
	first_name: 'John',
	last_name: 'Doe',
	company: 'Automattic',
	country: 'US',
	address_1: 'address1',
	address_2: 'address2',
	city: 'San Francisco',
	state: 'CA',
	postcode: '94107',
	phone: '123456789',
	email: 'john.doe@example.com',
};
const customerShipping = {
	first_name: 'Tim',
	last_name: 'Clark',
	company: 'Automattic',
	country: 'US',
	address_1: 'Oxford Ave',
	address_2: 'Linwood Ave',
	city: 'Buffalo',
	state: 'NY',
	postcode: '14201',
	phone: '123456789',
	email: 'john.doe@example.com',
};

const queries = [
	[ customerBilling.first_name, 'billing first name' ],
	[ customerBilling.last_name, 'billing last name' ],
	[ customerBilling.company, 'billing company name' ],
	[ customerBilling.address_1, 'billing first address' ],
	[ customerBilling.address_2, 'billing second address' ],
	[ customerBilling.city, 'billing city name' ],
	[ customerBilling.postcode, 'billing post code' ],
	[ customerBilling.email, 'billing email' ],
	[ customerBilling.phone, 'billing phone' ],
	[ customerBilling.state, 'billing state' ],
	[ customerShipping.first_name, 'shipping first name' ],
	[ customerShipping.last_name, 'shipping last name' ],
	[ customerShipping.address_1, 'shipping first address' ],
	[ customerShipping.address_2, 'shipping second address' ],
	[ customerShipping.city, 'shipping city name' ],
	[ customerShipping.postcode, 'shipping post code' ],
	[ itemName, 'shipping item name' ],
];

test.describe( 'WooCommerce Orders > Search orders', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	let productId, customerId, orderId;

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
				name: 'Wanted Product',
				type: 'simple',
				regular_price: '7.99',
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		// update customer info
		await api
			.post( 'customers', {
				email: 'john.doe@example.com',
				first_name: 'John',
				last_name: 'Doe',
				username: 'john.doe',
				billing: customerBilling,
				shipping: customerShipping,
			} )
			.then( ( response ) => {
				customerId = response.data.id;
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
				customer_id: customerId,
				billing: customerBilling,
				shipping: customerShipping,
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
		await api.delete( `products/${ productId }`, { force: true } );
		await api.delete( `orders/${ orderId }`, { force: true } );
		await api.delete( `customers/${ customerId }`, { force: true } );
	} );

	test( 'can search for order by order id', async ( { page } ) => {
		await page.goto( 'wp-admin/edit.php?post_type=shop_order' );
		await page.fill( '#post-search-input', orderId.toString() );
		await page.click( '#search-submit' );

		await expect(
			page.locator( '.order_number > a.order-view' )
		).toContainText( `#${ orderId } ${ searchString }` );
	} );

	for ( let i = 0; i < queries.length; i++ ) {
		test( `can search for order containing "${ queries[ i ][ 0 ] }" as the ${ queries[ i ][ 1 ] }`, async ( {
			page,
		} ) => {
			await page.goto( 'wp-admin/edit.php?post_type=shop_order' );
			await page.fill( '#post-search-input', queries[ i ][ 0 ] );
			await page.click( '#search-submit' );

			await expect(
				page.locator( '.order_number > a.order-view' )
			).toContainText( `#${ orderId } ${ searchString }` );
		} );
	}
} );
