/**
 * Internal dependencies
 */
import { tags, expect, test } from '../../fixtures/fixtures';
import { WC_API_PATH } from '../../utils/api-client';
import { ADMIN_STATE_PATH } from '../../playwright.config';

const searchString = 'James Doe';
const itemName = 'Wanted Product';

const customerBilling = {
	first_name: 'James',
	last_name: 'Doe',
	company: 'Automattic',
	country: 'US',
	address_1: 'address1',
	address_2: 'address2',
	city: 'San Francisco',
	state: 'CA',
	postcode: '94107',
	phone: '123456789',
	email: 'john.doe.ordersearch@example.com',
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

/**
 * Check first if customer already exists. Delete if it does.
 */
const deleteCustomer = async ( restApi ) => {
	const { data: customersList } = await restApi.get(
		`${ WC_API_PATH }/customers`,
		{
			email: customerBilling.email,
		}
	);

	if ( customersList && customersList.length ) {
		const customerId = customersList[ 0 ].id;

		console.log(
			`Customer with email ${ customerBilling.email } exists! Deleting it before starting test...`
		);

		await restApi.delete( `${ WC_API_PATH }/customers/${ customerId }`, {
			force: true,
		} );
	}
};

test.describe(
	'WooCommerce Orders > Search orders',
	{ tag: [ tags.HPOS ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		let productId, customerId, orderId;

		test.beforeAll( async ( { restApi } ) => {
			// create a simple product
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: 'Wanted Product',
					type: 'simple',
					regular_price: '7.99',
				} )
				.then( ( response ) => {
					productId = response.data.id;
				} );

			await deleteCustomer( restApi );

			// Create test customer.
			await restApi
				.post( `${ WC_API_PATH }/customers`, {
					email: customerBilling.email,
					first_name: customerBilling.first_name,
					last_name: customerBilling.last_name,
					username: 'john.doe.ordersearch',
					billing: customerBilling,
					shipping: customerShipping,
				} )
				.then( ( response ) => {
					customerId = response.data.id;
				} );

			// create order
			await restApi
				.post( `${ WC_API_PATH }/orders`, {
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

		test.afterAll( async ( { restApi } ) => {
			await restApi.delete( `${ WC_API_PATH }/products/${ productId }`, {
				force: true,
			} );
			await restApi.delete( `${ WC_API_PATH }/orders/${ orderId }`, {
				force: true,
			} );
			await restApi.delete(
				`${ WC_API_PATH }/customers/${ customerId }`,
				{ force: true }
			);
		} );

		test( 'can search for order by order id', async ( { page } ) => {
			await page.goto( 'wp-admin/admin.php?page=wc-orders' );
			await page
				.locator( '[type=search][name=s]' )
				.fill( orderId.toString() );
			await page.locator( '#search-submit' ).click();

			await expect(
				page.locator( '.order_number > a.order-view' )
			).toContainText( `#${ orderId } ${ searchString }` );
		} );

		for ( let i = 0; i < queries.length; i++ ) {
			test( `can search for order containing "${ queries[ i ][ 0 ] }" as the ${ queries[ i ][ 1 ] }`, async ( {
				page,
			} ) => {
				await page.goto( 'wp-admin/admin.php?page=wc-orders' );
				await page
					.locator( '[type=search][name=s]' )
					.fill( queries[ i ][ 0 ] );
				await page.locator( '#search-submit' ).click();

				await expect(
					page.locator( '.order_number > a.order-view', {
						hasText: `#${ orderId } ${ searchString }`,
					} )
				).toBeVisible();
			} );
		}
	}
);
