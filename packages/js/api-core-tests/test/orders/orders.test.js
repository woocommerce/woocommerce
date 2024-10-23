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
			// eslint-disable-next-line
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
			// eslint-disable-next-line
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
			// eslint-disable-next-line
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
			// eslint-disable-next-line
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

		// NOTE: This does not verify the `taxes` array nested in line items.
		// While the precision parameter doesn't affect those values, after some
		// discussion it seems `dp` may not be supported in v4 of the API.
		it( 'dp (precision)', async () => {
			const expectPrecisionToMatch = ( value, dp ) => {
				expect( value ).toEqual(
					Number.parseFloat( value ).toFixed( dp )
				);
			};

			// eslint-disable-next-line
			const verifyOrderPrecision = ( order, dp ) => {
				expectPrecisionToMatch( order.discount_total, dp );
				expectPrecisionToMatch( order.discount_tax, dp );
				expectPrecisionToMatch( order.shipping_total, dp );
				expectPrecisionToMatch( order.shipping_tax, dp );
				expectPrecisionToMatch( order.cart_tax, dp );
				expectPrecisionToMatch( order.total, dp );
				expectPrecisionToMatch( order.total_tax, dp );

				order.line_items.forEach( ( lineItem ) => {
					expectPrecisionToMatch( lineItem.total, dp );
					expectPrecisionToMatch( lineItem.total_tax, dp );
				} );

				order.tax_lines.forEach( ( taxLine ) => {
					expectPrecisionToMatch( taxLine.tax_total, dp );
					expectPrecisionToMatch( taxLine.shipping_tax_total, dp );
				} );

				order.shipping_lines.forEach( ( shippingLine ) => {
					expectPrecisionToMatch( shippingLine.total, dp );
					expectPrecisionToMatch( shippingLine.total_tax, dp );
				} );

				order.fee_lines.forEach( ( feeLine ) => {
					expectPrecisionToMatch( feeLine.total, dp );
					expectPrecisionToMatch( feeLine.total_tax, dp );
				} );

				order.refunds.forEach( ( refund ) => {
					expectPrecisionToMatch( refund.total, dp );
				} );
			};

			const result1 = await ordersApi.retrieve.order(
				sampleData.precisionOrder.id,
				{
					dp: 1,
				}
			);
			expect( result1.statusCode ).toEqual( 200 );
			verifyOrderPrecision( result1.body, 1 );

			const result2 = await ordersApi.retrieve.order(
				sampleData.precisionOrder.id,
				{
					dp: 3,
				}
			);
			expect( result2.statusCode ).toEqual( 200 );
			verifyOrderPrecision( result2.body, 3 );

			const result3 = await ordersApi.retrieve.order(
				sampleData.precisionOrder.id
			);
			expect( result3.statusCode ).toEqual( 200 );
			verifyOrderPrecision( result3.body, 2 ); // The default value for 'dp' is 2.
		} );

		it( 'search', async () => {
			// By default, 'search' looks in:
			// - _billing_address_index
			// - _shipping_address_index
			// - _billing_last_name
			// - _billing_email
			// - order_item_name

			// Test billing email.
			const result1 = await ordersApi.listAll.orders( {
				search: 'example.com',
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( 7 );
			// eslint-disable-next-line
			result1.body.forEach( ( order ) =>
				expect( order.billing.email ).toContain( 'example.com' )
			);

			// Test billing address.
			const result2 = await ordersApi.listAll.orders( {
				search: 'gainesville', // Intentionally lowercase.
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toHaveLength( 1 );
			expect( result2.body[ 0 ].id ).toEqual( sampleData.guestOrder.id );

			// Test shipping address.
			const result3 = await ordersApi.listAll.orders( {
				search: 'Incognito',
			} );
			expect( result3.statusCode ).toEqual( 200 );
			expect( result3.body ).toHaveLength( 1 );
			expect( result3.body[ 0 ].id ).toEqual( sampleData.guestOrder.id );

			// Test billing last name.
			const result4 = await ordersApi.listAll.orders( {
				search: 'Doe',
			} );
			expect( result4.statusCode ).toEqual( 200 );
			expect( result4.body ).toHaveLength( 5 );
			// eslint-disable-next-line
			result4.body.forEach( ( order ) =>
				expect( order.billing.last_name ).toEqual( 'Doe' )
			);

			// Test order item name.
			const result5 = await ordersApi.listAll.orders( {
				search: 'Pennant',
			} );
			expect( result5.statusCode ).toEqual( 200 );
			expect( result5.body ).toHaveLength( 2 );
			// eslint-disable-next-line
			result5.body.forEach( ( order ) =>
				expect( order ).toEqual(
					expect.objectContaining( {
						line_items: expect.arrayContaining( [
							expect.objectContaining( {
								name: 'WordPress Pennant',
							} ),
						] ),
					} )
				)
			);
		} );

		describe( 'orderby', () => {
			// The orders endpoint `orderby` parameter uses WP_Query, so our tests won't
			// include slug and title, since they are programmatically generated.
			it( 'default', async () => {
				// Default = date desc.
				const result = await ordersApi.listAll.orders();
				expect( result.statusCode ).toEqual( 200 );

				// Verify all dates are in descending order.
				let lastDate = Date.now();
				// eslint-disable-next-line
				result.body.forEach( ( { date_created } ) => {
					// eslint-disable-next-line
					const created = Date.parse( date_created + '.000Z' );
					expect( lastDate ).toBeGreaterThanOrEqual( created );
					lastDate = created;
				} );
			} );

			it( 'date', async () => {
				const result = await ordersApi.listAll.orders( {
					order: 'asc',
					orderby: 'date',
				} );
				expect( result.statusCode ).toEqual( 200 );

				// Verify all dates are in ascending order.
				let lastDate = 0;
				// eslint-disable-next-line
				result.body.forEach( ( { date_created } ) => {
					// eslint-disable-next-line
					const created = Date.parse( date_created + '.000Z' );
					expect( created ).toBeGreaterThanOrEqual( lastDate );
					lastDate = created;
				} );
			} );

			it( 'id', async () => {
				const result1 = await ordersApi.listAll.orders( {
					order: 'asc',
					orderby: 'id',
				} );
				expect( result1.statusCode ).toEqual( 200 );

				// Verify all results are in ascending order.
				let lastId = 0;
				result1.body.forEach( ( { id } ) => {
					expect( id ).toBeGreaterThan( lastId );
					lastId = id;
				} );

				const result2 = await ordersApi.listAll.orders( {
					order: 'desc',
					orderby: 'id',
				} );
				expect( result2.statusCode ).toEqual( 200 );

				// Verify all results are in descending order.
				lastId = Number.MAX_SAFE_INTEGER;
				result2.body.forEach( ( { id } ) => {
					expect( lastId ).toBeGreaterThan( id );
					lastId = id;
				} );
			} );

			it( 'include', async () => {
				const includeIds = [
					sampleData.precisionOrder.id,
					sampleData.hierarchicalOrders.parent.id,
					sampleData.guestOrder.id,
				];

				const result1 = await ordersApi.listAll.orders( {
					order: 'asc',
					orderby: 'include',
					include: includeIds.join( ',' ),
				} );
				expect( result1.statusCode ).toEqual( 200 );
				expect( result1.body ).toHaveLength( includeIds.length );

				// Verify all results are in proper order.
				result1.body.forEach( ( { id }, idx ) => {
					expect( id ).toBe( includeIds[ idx ] );
				} );

				const result2 = await ordersApi.listAll.orders( {
					order: 'desc',
					orderby: 'include',
					include: includeIds.join( ',' ),
				} );
				expect( result2.statusCode ).toEqual( 200 );
				expect( result2.body ).toHaveLength( includeIds.length );

				// Verify all results are in proper order.
				result2.body.forEach( ( { id }, idx ) => {
					expect( id ).toBe( includeIds[ idx ] );
				} );
			} );
		} );
	} );
} );
