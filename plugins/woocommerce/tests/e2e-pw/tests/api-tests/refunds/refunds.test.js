const { test, expect } = require( '../../../fixtures/api-tests-fixtures' );
const { refund } = require( '../../../data' );

test.describe( 'Refunds API tests', () => {
	let expectedRefund;
	let orderId;
	let productId;

	test.beforeAll( async ( { request } ) => {
		// Create a product and save its product ID
		const product = {
			name: 'Simple Product for Refunds API tests',
			regular_price: '100',
		};

		// call API to create a product
		const createProductResponse = await request.post(
			`/wp-json/wc/v3/products`,
			{
				data: product,
			}
		);
		const createProductResponseJSON = await createProductResponse.json();

		// Save product id for testing later
		productId = createProductResponseJSON.id;

		// Create an order with a product line item, and save its Order ID
		const order = {
			status: 'pending',
			line_items: [
				{
					product_id: productId,
				},
			],
		};

		// Call API to create an order (containing the previously created product id)
		const createOrderResponse = await request.post(
			`/wp-json/wc/v3/orders`,
			{
				data: order,
			}
		);
		const createOrderResponseJSON = await createOrderResponse.json();

		//save id for deletion later
		orderId = createOrderResponseJSON.id;

		// Setup the expected refund object
		expectedRefund = {
			...refund,
			line_items: [
				{
					product_id: productId,
				},
			],
		};
	} );

	test.afterAll( async ( { request } ) => {
		// Cleanup the created product and order
		// call API to delete the product
		await request.delete( `/wp-json/wc/v3/products/${ productId }`, {
			data: { force: true },
		} );

		// call API to delete the order
		await request.delete( `/wp-json/wc/v3/orders/${ orderId }`, {
			data: { force: true },
		} );
	} );

	test( 'can create a refund', async ( { request } ) => {
		// call API to create a refund
		const response = await request.post(
			`/wp-json/wc/v3/orders/${ orderId }/refunds`,
			{
				data: expectedRefund,
			}
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 201 );
		expect( responseJSON.id ).toBeDefined();

		// Save the refund ID
		expectedRefund.id = responseJSON.id;

		// Verify that the order was refunded.
		// call API to get the order
		const getOrderResponse = await request.get(
			`/wp-json/wc/v3/orders/${ orderId }`
		);
		const getOrderResponseJSON = await getOrderResponse.json();
		expect( getOrderResponseJSON.refunds ).toHaveLength( 1 );
		expect( getOrderResponseJSON.refunds[ 0 ].id ).toEqual(
			expectedRefund.id
		);
		expect( getOrderResponseJSON.refunds[ 0 ].reason ).toEqual(
			expectedRefund.reason
		);
		expect( getOrderResponseJSON.refunds[ 0 ].total ).toEqual(
			`-${ expectedRefund.amount }`
		);
	} );

	test( 'can retrieve a refund', async ( { request } ) => {
		// call API to retrieve the refund from the order
		const response = await request.get(
			`/wp-json/wc/v3/orders/${ orderId }/refunds/${ expectedRefund.id }`
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( responseJSON.id ).toEqual( expectedRefund.id );
	} );

	test( 'can retrieve refund info from refund endpoint', async ( {
		request,
	} ) => {
		// call API to retrieve the refund from the order
		const response = await request.get( `/wp-json/wc/v3/refunds/` );
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( responseJSON ).toHaveLength( 1 );
		expect( responseJSON[ 0 ].id ).toEqual( expectedRefund.id );
		expect( responseJSON[ 0 ].reason ).toEqual( expectedRefund.reason );
		expect( responseJSON[ 0 ].amount ).toEqual( expectedRefund.amount );
	} );

	test( 'can list all refunds', async ( { request } ) => {
		// call API to retrieve all the refunds
		const response = await request.get(
			`/wp-json/wc/v3/orders/${ orderId }/refunds`
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( responseJSON ).toHaveLength( 1 );
		expect( responseJSON[ 0 ].id ).toEqual( expectedRefund.id );
	} );

	test( 'can delete a refund', async ( { request } ) => {
		// call API to delete the previously created refund
		const response = await request.delete(
			`/wp-json/wc/v3/orders/${ orderId }/refunds/${ expectedRefund.id }`,
			{
				data: { force: true },
			}
		);
		const responseJSON = await response.json();

		expect( response.status() ).toEqual( 200 );
		expect( responseJSON.id ).toEqual( expectedRefund.id );

		// Verify that the refund cannot be retrieved
		// call API to attempt to retrieve the refund that was previously deleted
		const retrieveRefundResponse = await request.get(
			`/wp-json/wc/v3/orders/${ orderId }/refunds/${ expectedRefund.id }`
		);
		expect( retrieveRefundResponse.status() ).toEqual( 404 );

		// Verify that the order no longer has a refund
		// call API to retrieve the order
		const retrieveOrderResponse = await request.get(
			`/wp-json/wc/v3/orders/${ orderId }`
		);
		const retrieveOrderResponseJSON = await retrieveOrderResponse.json();
		expect( retrieveOrderResponseJSON.refunds ).toHaveLength( 0 );
	} );
} );
