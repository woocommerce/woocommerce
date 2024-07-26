const { test, expect } = require( '../../../fixtures/api-tests-fixtures' );
const { order } = require( '../../../data' );
const { API_BASE_URL } = process.env;
const shouldSkip = API_BASE_URL !== undefined;

/**
 * Billing properties to update.
 */
const updatedCustomerBilling = {
	first_name: 'Jane',
	last_name: 'Doe',
	company: 'Automattic',
	country: 'US',
	address_1: '123 Market Street',
	address_2: 'Suite 500',
	city: 'Austin',
	state: 'TX',
	postcode: '73301',
	phone: '123456789',
	email: 'jane.doe@example.com',
};

/**
 * Shipping properties to update.
 */
const updatedCustomerShipping = {
	first_name: 'Mike',
	last_name: 'Anderson',
	company: 'Automattic',
	country: 'US',
	address_1: '123 Ocean Ave',
	address_2: '',
	city: 'New York',
	state: 'NY',
	postcode: '10013',
	phone: '123456789',
};

/**
 * Data tables to be used for testing the 'Create an order' API.
 */
const statusesDataTable = [
	'pending',
	'processing',
	'on-hold',
	'completed',
	'cancelled',
	'refunded',
	'failed',
];

/**
 * A simple product that will be added to an order.
 */
const simpleProduct = {
	name: 'Incredible Plastic Table',
	regular_price: '48',
};

test.describe.serial( 'Orders API tests: CRUD', () => {
	let orderId;

	test.describe( 'Create an order', () => {
		test( 'can create a pending order by default', async ( {
			request,
		} ) => {
			// Create an order that has a null status
			const requestPayload = {
				...order,
				status: null,
			};

			// call API to create an order
			const response = await request.post( '/wp-json/wc/v3/orders', {
				data: requestPayload,
			} );
			const responseJSON = await response.json();

			// Save the order ID. It will be used by the retrieve, update, and delete tests.
			orderId = responseJSON.id;

			// Verify that the order status is 'pending'
			expect( response.status() ).toEqual( 201 );
			expect( typeof responseJSON.id ).toEqual( 'number' );
			expect( responseJSON.status ).toEqual( 'pending' );
		} );

		for ( const expectedStatus of statusesDataTable ) {
			test( `can create an order with status ${ expectedStatus }`, async ( {
				request,
			} ) => {
				const requestPayload = {
					...order,
					status: expectedStatus,
				};
				//create order with status
				const response = await request.post( '/wp-json/wc/v3/orders', {
					data: requestPayload,
				} );
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 201 );
				expect( typeof responseJSON.id ).toEqual( 'number' );
				expect( responseJSON.status ).toEqual( expectedStatus );

				// Cleanup: Delete this order
				await request.delete(
					`/wp-json/wc/v3/orders/${ responseJSON.id }`,
					{
						data: {
							force: true,
						},
					}
				);
			} );
		}

		test.describe( 'Order Notes tests', () => {
			let orderNoteId;
			test( 'can create a order note', async ( { request } ) => {
				// call API to create an order note on the previously created order
				const response = await request.post(
					`/wp-json/wc/v3/orders/${ orderId }/notes`,
					{
						data: {
							note: 'Order ok!!!',
						},
					}
				);
				const responseJSON = await response.json();

				// Save the order note ID. It will be used by the retrieve, update and delete tests.
				orderNoteId = responseJSON.id;

				// Verify that the order status is 'pending'
				expect( response.status() ).toEqual( 201 );
				expect( typeof responseJSON.id ).toEqual( 'number' );
				expect( responseJSON.note ).toEqual( 'Order ok!!!' );
				expect( responseJSON.customer_note ).toEqual( false );
			} );

			test( 'can retrieve an order note', async ( { request } ) => {
				// call API to retrieve the previously saved order note
				const response = await request.get(
					`/wp-json/wc/v3/orders/${ orderId }/notes/${ orderNoteId }`
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 200 );
				expect( typeof responseJSON.id ).toEqual( 'number' );
				expect( responseJSON.note ).toEqual( 'Order ok!!!' );
				expect( responseJSON.customer_note ).toEqual( false );
				expect( responseJSON._links ).toEqual(
					expect.objectContaining( {
						up: expect.arrayContaining( [
							expect.objectContaining( {
								href: expect.stringContaining(
									`/wp-json/wc/v3/orders/${ orderId }`
								),
							} ),
						] ),
					} )
				);
			} );

			test( 'can retrieve all order notes', async ( { request } ) => {
				// call API to retrieve all order notes
				const response = await request.get(
					`/wp-json/wc/v3/orders/${ orderId }/notes`
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 200 );
				expect( Array.isArray( responseJSON ) ).toBe( true );
				expect( responseJSON.length ).toBeGreaterThan( 0 );
			} );

			test( `cannot update an order note`, async ( { request } ) => {
				// attempt to update order note should fail
				const response = await request.put(
					`/wp-json/wc/v3/orders/${ orderId }/notes/${ orderNoteId }`,
					{
						data: {
							name: 'Not able to update order note',
						},
					}
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 404 );
				expect( responseJSON.code ).toEqual( 'rest_no_route' );
				expect( responseJSON.message ).toEqual(
					'No route was found matching the URL and request method.'
				);
			} );

			test( 'can permanently delete an order note', async ( {
				request,
			} ) => {
				// Delete the order note.
				const response = await request.delete(
					`/wp-json/wc/v3/orders/${ orderId }/notes/${ orderNoteId }`,
					{
						data: {
							force: true,
						},
					}
				);
				expect( response.status() ).toEqual( 200 );

				// Verify that the order note can no longer be retrieved
				const getDeletedOrderNoteResponse = await request.get(
					`/wp-json/wc/v3/orders/${ orderId }/notes/${ orderNoteId }`
				);
				expect( getDeletedOrderNoteResponse.status() ).toEqual( 404 );
			} );
		} );
	} );

	test.describe( 'Retrieve an order', () => {
		test( 'can retrieve an order', async ( { request } ) => {
			// call API to retrieve the previously saved order
			const response = await request.get(
				`/wp-json/wc/v3/orders/${ orderId }`
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( orderId );
		} );
	} );

	test.describe( 'Update an order', () => {
		test.beforeAll( async ( { request } ) => {
			// Create the product and save its id
			const response = await request.post( '/wp-json/wc/v3/products', {
				data: simpleProduct,
			} );
			const responseJSON = await response.json();
			simpleProduct.id = responseJSON.id;
		} );

		test.afterAll( async ( { request } ) => {
			// Delete the created product
			await request.delete(
				`/wp-json/wc/v3/products/${ simpleProduct.id }`,
				{
					data: {
						force: true,
					},
				}
			);
		} );

		const delay = ( delayInms ) => {
			return new Promise( ( resolve ) =>
				setTimeout( resolve, delayInms )
			);
		};

		for ( const expectedOrderStatus of statusesDataTable ) {
			test( `can update status of an order to ${ expectedOrderStatus }`, async ( {
				request,
			} ) => {
				// eslint-disable-next-line playwright/no-conditional-in-test
				if ( shouldSkip ) {
					await delay( 1000 ); // if this runs too fast on an external host, it fails
				}
				const requestPayload = {
					status: expectedOrderStatus,
				};

				const response = await request.put(
					`/wp-json/wc/v3/orders/${ orderId }`,
					{
						data: requestPayload,
					}
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 200 );
				expect( responseJSON.id ).toEqual( orderId );
				expect( responseJSON.status ).toEqual( expectedOrderStatus );
			} );
		}

		test( 'can add shipping and billing contacts to an order', async ( {
			request,
		} ) => {
			// Update the billing and shipping fields on the order
			order.billing = updatedCustomerBilling;
			order.shipping = updatedCustomerShipping;

			const response = await request.put(
				`/wp-json/wc/v3/orders/${ orderId }`,
				{
					data: order,
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.billing ).toEqual( updatedCustomerBilling );
			expect( responseJSON.shipping ).toEqual( updatedCustomerShipping );
		} );

		test( 'can add a product to an order', async ( { request } ) => {
			// Add the product to the order
			const requestPayload = {
				line_items: [
					{
						product_id: simpleProduct.id,
					},
				],
			};
			const response = await request.put(
				`/wp-json/wc/v3/orders/${ orderId }`,
				{
					data: requestPayload,
				}
			);
			const responseJSON = await response.json();

			// Verify that the added product has the correct values
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.line_items ).toHaveLength( 1 );
			expect( responseJSON.line_items[ 0 ].product_id ).toEqual(
				simpleProduct.id
			);
			expect( responseJSON.line_items[ 0 ].name ).toEqual(
				simpleProduct.name
			);
		} );

		test( 'can pay for an order', async ( { request } ) => {
			// Setup: Set order status to 'pending'
			await request.put( `/wp-json/wc/v3/orders/${ orderId }`, {
				data: {
					status: 'pending',
				},
			} );

			// Pay for the order by setting `set_paid` to true
			const updateRequestPayload = {
				set_paid: true,
			};
			const response = await request.put(
				`/wp-json/wc/v3/orders/${ orderId }`,
				{
					data: updateRequestPayload,
				}
			);
			const responseJSON = await response.json();

			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( orderId );

			// Validate that the status of the order was automatically set to 'processing'
			expect( responseJSON.status ).toEqual( 'processing' );

			// Validate that the date_paid and date_paid_gmt properties are no longer null
			expect( responseJSON.date_paid ).not.toBeNull();
			expect( responseJSON.date_paid_gmt ).not.toBeNull();
		} );
	} );

	test.describe( 'Delete an order', () => {
		test( 'can permanently delete an order', async ( { request } ) => {
			// Delete the order.
			const response = await request.delete(
				`/wp-json/wc/v3/orders/${ orderId }`,
				{
					data: {
						force: true,
					},
				}
			);
			expect( response.status() ).toEqual( 200 );

			// Verify that the order can no longer be retrieved.
			const getOrderResponse = await request.get(
				`/wp-json/wc/v3/orders/${ orderId }`
			);
			expect( getOrderResponse.status() ).toEqual( 404 );
		} );
	} );
} );
