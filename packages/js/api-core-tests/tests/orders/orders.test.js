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
		const ORDERS_COUNT = 10;

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
			expect( lastPage.body ).toHaveLength( 2 );

			// Verify a page outside the total page count is empty.
			const page6 = await ordersApi.listAll.orders( {
				per_page: pageSize,
				page: 6,
			} );
			expect( Array.isArray( page6.body ) ).toBe( true );
			expect( page6.body ).toHaveLength( 0 );
		} );

		it( 'inclusion / exclusion', async () => {
			const allOrders = await ordersApi.listAll.orders( {
				per_page: 10,
			} );
			expect( allOrders.statusCode ).toEqual( 200 );
			const allOrdersIds = allOrders.body.map( ( order ) => order.id );
			expect( allOrdersIds ).toHaveLength( ORDERS_COUNT );

			const ordersToFilter = [
				allOrdersIds[ 0 ],
				allOrdersIds[ 2 ],
				allOrdersIds[ 4 ],
				allOrdersIds[ 7 ],
			];

			const included = await ordersApi.listAll.orders( {
				per_page: 20,
				include: ordersToFilter.join( ',' ),
			} );
			expect( included.statusCode ).toEqual( 200 );
			expect( included.body ).toHaveLength( ordersToFilter.length );
			expect( included.body ).toEqual(
				expect.arrayContaining(
					ordersToFilter.map( ( id ) =>
						expect.objectContaining( { id } )
					)
				)
			);

			const excluded = await ordersApi.listAll.orders( {
				per_page: 20,
				exclude: ordersToFilter.join( ',' ),
			} );
			expect( excluded.statusCode ).toEqual( 200 );
			expect( excluded.body ).toHaveLength(
				ORDERS_COUNT - ordersToFilter.length
			);
			expect( excluded.body ).toEqual(
				expect.not.arrayContaining(
					ordersToFilter.map( ( id ) =>
						expect.objectContaining( { id } )
					)
				)
			);
		} );

		it( 'parent', async () => {
			const result1 = await ordersApi.listAll.orders( {
				parent: sampleData.hierarchicalOrders.parent.id,
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( 1 );
			expect( result1.body[ 0 ].id ).toBe(
				sampleData.hierarchicalOrders.child.id
			);

			const result2 = await ordersApi.listAll.orders( {
				parent_exclude: sampleData.hierarchicalOrders.parent.id,
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toEqual(
				expect.not.arrayContaining( [
					expect.objectContaining( {
						id: sampleData.hierarchicalOrders.child.id,
					} ),
				] )
			);
		} );

		it( 'status', async () => {
			const result1 = await ordersApi.listAll.orders( {
				status: 'completed',
			} );

			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( 2 );
			expect( result1.body ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						status: 'completed',
						customer_id: 0,
						line_items: expect.arrayContaining( [
							expect.objectContaining( {
								name: 'Single',
								quantity: 2,
							} ),
							expect.objectContaining( {
								name: 'Beanie with Logo',
								quantity: 3,
							} ),
							expect.objectContaining( {
								name: 'T-Shirt',
								quantity: 1,
							} ),
						] ),
					} ),
					expect.objectContaining( {
						status: 'completed',
						customer_id: sampleData.customers.tina.id,
						line_items: expect.arrayContaining( [
							expect.objectContaining( {
								name: 'Sunglasses',
								quantity: 1,
							} ),
						] ),
					} ),
				] )
			);

			const result2 = await ordersApi.listAll.orders( {
				status: 'processing',
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toHaveLength( 8 );
			expect( result2.body ).toEqual(
				expect.not.arrayContaining(
					result1.body.map( ( { id } ) =>
						expect.objectContaining( { id } )
					)
				)
			);
		} );

		it( 'customer', async () => {
			const result1 = await ordersApi.listAll.orders( {
				customer: sampleData.customers.john.id,
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( 5 );
			result1.body.forEach( ( order ) =>
				expect( order ).toEqual(
					expect.objectContaining( {
						customer_id: sampleData.customers.john.id,
					} )
				)
			);

			const result2 = await ordersApi.listAll.orders( {
				customer: 0,
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toHaveLength( 3 );
			result2.body.forEach( ( order ) =>
				expect( order ).toEqual(
					expect.objectContaining( {
						customer_id: 0,
					} )
				)
			);
		} );

		it( 'product', async () => {
			const beanie = sampleData.testProductData.simpleProducts.find(
				( p ) => p.name === 'Beanie'
			);
			const result1 = await ordersApi.listAll.orders( {
				product: beanie.id,
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( 2 );
			result1.body.forEach( ( order ) =>
				expect( order ).toEqual(
					expect.objectContaining( {
						line_items: expect.arrayContaining( [
							expect.objectContaining( {
								name: 'Beanie',
							} ),
						] ),
					} )
				)
			);
		} );
	} );
} );
