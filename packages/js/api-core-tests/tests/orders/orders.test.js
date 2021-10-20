const { ordersApi } = require( '../../endpoints/orders' );
const { order } = require( '../../data' );
const { createSampleData, deleteSampleData } = require( '../../data/orders' );

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
 * Tests for the WooCommerce Orders API.
 *
 * @group api
 * @group orders
 *
 */
describe( 'Orders API tests', () => {
	let orderId, sampleData;

	beforeAll( async () => {
		sampleData = await createSampleData();
	}, 100000 );

	afterAll( async () => {
		await deleteSampleData( sampleData );
	}, 10000 );

	it( 'can create an order', async () => {
		const response = await ordersApi.create.order( order );
		expect( response.status ).toEqual( ordersApi.create.responseCode );
		expect( response.body.id ).toBeDefined();
		orderId = response.body.id;

		// Validate the data type and verify the order is in a pending state
		expect( typeof response.body.status ).toBe( 'string' );
		expect( response.body.status ).toEqual( 'pending' );
	} );

	it( 'can retrieve an order', async () => {
		const response = await ordersApi.retrieve.order( orderId );
		expect( response.status ).toEqual( ordersApi.retrieve.responseCode );
		expect( response.body.id ).toEqual( orderId );
	} );

	it( 'can add shipping and billing contacts to an order', async () => {
		// Update the billing and shipping fields on the order
		order.billing = updatedCustomerBilling;
		order.shipping = updatedCustomerShipping;

		const response = await ordersApi.update.order( orderId, order );
		expect( response.status ).toEqual( ordersApi.update.responseCode );

		expect( response.body.billing ).toEqual( updatedCustomerBilling );
		expect( response.body.shipping ).toEqual( updatedCustomerShipping );
	} );

	it( 'can permanently delete an order', async () => {
		const response = await ordersApi.delete.order( orderId, true );
		expect( response.status ).toEqual( ordersApi.delete.responseCode );

		const getOrderResponse = await ordersApi.retrieve.order( orderId );
		expect( getOrderResponse.status ).toEqual( 404 );
	} );

	describe( 'List all orders', () => {
		const ORDERS_COUNT = 9;

		it( 'pagination', async () => {
			const pageSize = 4;
			const page1 = await ordersApi.listAll.orders( {
				per_page: pageSize,
			} );
			const page2 = await ordersApi.listAll.orders( {
				per_page: pageSize,
				page: 2,
			} );
			expect( page1.statusCode ).toEqual( 200 );
			expect( page2.statusCode ).toEqual( 200 );

			// Verify total page count.
			expect( page1.headers[ 'x-wp-total' ] ).toEqual(
				ORDERS_COUNT.toString()
			);
			expect( page1.headers[ 'x-wp-totalpages' ] ).toEqual( '3' );

			// Verify we get pageSize'd arrays.
			expect( Array.isArray( page1.body ) ).toBe( true );
			expect( Array.isArray( page2.body ) ).toBe( true );
			expect( page1.body ).toHaveLength( pageSize );
			expect( page2.body ).toHaveLength( pageSize );

			// Ensure all of the order IDs are unique (no page overlap).
			const allOrderIds = page1.body
				.concat( page2.body )
				.reduce( ( acc, { id } ) => {
					acc[ id ] = 1;
					return acc;
				}, {} );
			expect( Object.keys( allOrderIds ) ).toHaveLength( pageSize * 2 );

			// Verify that offset takes precedent over page number.
			const page2Offset = await ordersApi.listAll.orders( {
				per_page: pageSize,
				page: 2,
				offset: pageSize + 1,
			} );
			// The offset pushes the result set 1 order past the start of page 2.
			expect( page2Offset.body ).toEqual(
				expect.not.arrayContaining( [
					expect.objectContaining( { id: page2.body[ 0 ].id } ),
				] )
			);
			expect( page2Offset.body[ 0 ].id ).toEqual( page2.body[ 1 ].id );

			// Verify the last page only has 1 order as we expect.
			const lastPage = await ordersApi.listAll.orders( {
				per_page: pageSize,
				page: 3,
			} );
			expect( Array.isArray( lastPage.body ) ).toBe( true );
			expect( lastPage.body ).toHaveLength( 1 );

			// Verify a page outside the total page count is empty.
			const page6 = await ordersApi.listAll.orders( {
				per_page: pageSize,
				page: 6,
			} );
			expect( Array.isArray( page6.body ) ).toBe( true );
			expect( page6.body ).toHaveLength( 0 );
		} );
	} );
} );
