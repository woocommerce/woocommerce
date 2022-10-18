const { ordersApi, productsApi } = require( '../../endpoints' );
const { order } = require( '../../data' );

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

/**
 * Tests for the WooCommerce Orders API.
 *
 * @group api
 * @group orders
 *
 */
describe( 'Orders API tests: CRUD', () => {
	let orderId;

	describe( 'Create an order', () => {
		it( 'can create a pending order by default', async () => {
			// Create an order that has a null status
			const requestPayload = {
				...order,
				status: null,
			};
			const { body, status } = await ordersApi.create.order(
				requestPayload
			);
			// Save the order ID. It will be used by the retrieve, update, and delete tests.
			orderId = body.id;

			// Verify that the order status is 'pending'
			expect( status ).toEqual( ordersApi.create.responseCode );
			expect( typeof body.id ).toEqual( 'number' );
			expect( body.status ).toEqual( 'pending' );
		} );

		it.each( statusesDataTable )(
			"can create an order with status '%s'",
			async ( expectedStatus ) => {
				const requestPayload = {
					...order,
					status: expectedStatus,
				};
				const { status, body } = await ordersApi.create.order(
					requestPayload
				);

				expect( status ).toEqual( ordersApi.create.responseCode );
				expect( typeof body.id ).toEqual( 'number' );
				expect( body.status ).toEqual( expectedStatus );

				// Cleanup: Delete this order
				await ordersApi.delete.order( body.id, true );
			}
		);
	} );

	describe( 'Retrieve an order', () => {
		it( 'can retrieve an order', async () => {
			const response = await ordersApi.retrieve.order( orderId );
			expect( response.status ).toEqual(
				ordersApi.retrieve.responseCode
			);
			expect( response.body.id ).toEqual( orderId );
		} );
	} );

	describe( 'Update an order', () => {
		beforeAll( async () => {
			// Create the product and save its id
			const { body } = await productsApi.create.product( simpleProduct );
			simpleProduct.id = body.id;
		} );

		afterAll( async () => {
			// Delete the created product
			await productsApi.delete.product( simpleProduct.id, true );
		} );

		it.each( statusesDataTable )(
			"can update status of an order to '%s'",
			async ( expectedOrderStatus ) => {
				const requestPayload = {
					status: expectedOrderStatus,
				};
				const { status, body } = await ordersApi.update.order(
					orderId,
					requestPayload
				);

				expect( status ).toEqual( ordersApi.update.responseCode );
				expect( body.id ).toEqual( orderId );
				expect( body.status ).toEqual( expectedOrderStatus );
			}
		);

		it( 'can add shipping and billing contacts to an order', async () => {
			// Update the billing and shipping fields on the order
			order.billing = updatedCustomerBilling;
			order.shipping = updatedCustomerShipping;

			const response = await ordersApi.update.order( orderId, order );
			expect( response.status ).toEqual( ordersApi.update.responseCode );

			expect( response.body.billing ).toEqual( updatedCustomerBilling );
			expect( response.body.shipping ).toEqual( updatedCustomerShipping );
		} );

		it( 'can add a product to an order', async () => {
			// Add the product to the order
			const requestPayload = {
				line_items: [ { product_id: simpleProduct.id } ],
			};
			const { body, status } = await ordersApi.update.order(
				orderId,
				requestPayload
			);

			// Verify that the added product has the correct values
			expect( status ).toEqual( ordersApi.update.responseCode );
			expect( body.line_items ).toHaveLength( 1 );
			expect( body.line_items[ 0 ].product_id ).toEqual(
				simpleProduct.id
			);
			expect( body.line_items[ 0 ].name ).toEqual( simpleProduct.name );
		} );

		it( 'can pay for an order', async () => {
			// Setup: Set order status to 'pending'
			await ordersApi.update.order( orderId, {
				status: 'pending',
			} );

			// Pay for the order by setting `set_paid` to true
			const updateRequestPayload = {
				set_paid: true,
			};
			const { status, body } = await ordersApi.update.order(
				orderId,
				updateRequestPayload
			);
			expect( status ).toEqual( ordersApi.update.responseCode );
			expect( body.id ).toEqual( orderId );

			// Validate that the status of the order was automatically set to 'processing'
			expect( body.status ).toEqual( 'processing' );

			// Validate that the date_paid and date_paid_gmt properties are no longer null
			expect( body.date_paid ).not.toBeNull();
			expect( body.date_paid_gmt ).not.toBeNull();
		} );
	} );

	describe( 'Delete an order', () => {
		it( 'can permanently delete an order', async () => {
			// Delete the order.
			const response = await ordersApi.delete.order( orderId, true );
			expect( response.status ).toEqual( ordersApi.delete.responseCode );

			// Verify that the order can no longer be retrieved.
			const getOrderResponse = await ordersApi.retrieve.order( orderId );
			expect( getOrderResponse.status ).toEqual( 404 );
		} );
	} );
} );
