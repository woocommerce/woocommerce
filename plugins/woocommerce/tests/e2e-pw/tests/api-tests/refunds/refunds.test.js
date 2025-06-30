const { test, expect } = require( '../../../fixtures/api-tests-fixtures' );
const { refund } = require( '../../../data' );

let productId;
let expectedRefund;
let orderId;

test.describe( 'Refunds API tests', () => {
	test.beforeAll( async ( { request } ) => {
		// Create a product and save its product ID
		const product = {
			name: 'Simple Product for Refunds API tests',
			regular_price: '100',
		};
		const createProductResponse = await request.post(
			`./wp-json/wc/v3/products`,
			{
				data: product,
			}
		);
		const createProductResponseJSON = await createProductResponse.json();
		productId = createProductResponseJSON.id;
		expectedRefund = {
			...refund,
			line_items: [
				{
					product_id: productId,
				},
			],
		};
	} );

	test.beforeEach( async ( { request } ) => {
		const order = {
			status: 'pending',
			line_items: [
				{
					product_id: productId,
				},
			],
		};
		const createOrderResponse = await request.post(
			`./wp-json/wc/v3/orders`,
			{
				data: order,
			}
		);
		const createOrderResponseJSON = await createOrderResponse.json();
		orderId = createOrderResponseJSON.id;
	} );

	test.afterEach( async ( { request } ) => {
		await request
			.delete( `./wp-json/wc/v3/orders/${ orderId }`, {
				data: { force: true },
			} )
			.catch( () => {} );
	} );

	test.afterAll( async ( { request } ) => {
		// Cleanup the created product
		await request.delete( `./wp-json/wc/v3/products/${ productId }`, {
			data: { force: true },
		} );
	} );

	test( 'can create a refund', async ( { request } ) => {
		const response = await request.post(
			`./wp-json/wc/v3/orders/${ orderId }/refunds`,
			{
				data: expectedRefund,
			}
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 201 );
		expect( responseJSON.id ).toBeDefined();
		// Verify that the order was refunded.
		const getOrderResponse = await request.get(
			`./wp-json/wc/v3/orders/${ orderId }`
		);
		const getOrderResponseJSON = await getOrderResponse.json();
		expect( getOrderResponseJSON.refunds ).toHaveLength( 1 );
		expect( getOrderResponseJSON.refunds[ 0 ].id ).toEqual(
			responseJSON.id
		);
		expect( getOrderResponseJSON.refunds[ 0 ].reason ).toEqual(
			expectedRefund.reason
		);
		expect( getOrderResponseJSON.refunds[ 0 ].total ).toEqual(
			`-${ expectedRefund.amount }`
		);
	} );

	test( 'can retrieve a refund', async ( { request } ) => {
		const refundCreateResponse = await request.post(
			`./wp-json/wc/v3/orders/${ orderId }/refunds`,
			{
				data: expectedRefund,
			}
		);
		const refundJSON = await refundCreateResponse.json();
		const refundGetResponse = await request.get(
			`./wp-json/wc/v3/orders/${ orderId }/refunds/${ refundJSON.id }`
		);
		const responseJSON = await refundGetResponse.json();
		expect( refundGetResponse.status() ).toEqual( 200 );
		expect( responseJSON.id ).toEqual( refundJSON.id );
	} );

	test( 'can retrieve refund info from refund endpoint', async ( {
		request,
	} ) => {
		const refundCreateResponse = await request.post(
			`./wp-json/wc/v3/orders/${ orderId }/refunds`,
			{
				data: expectedRefund,
			}
		);
		const refundJSON = await refundCreateResponse.json();
		const refundsListResponse = await request.get(
			`./wp-json/wc/v3/refunds/`
		);
		const responseJSON = await refundsListResponse.json();
		expect( refundsListResponse.status() ).toEqual( 200 );
		expect( responseJSON.length ).toBeGreaterThan( 0 );
		const foundRefund = responseJSON.find(
			( r ) => r.id === refundJSON.id
		);
		expect( foundRefund ).toBeDefined();
		expect( foundRefund.reason ).toEqual( expectedRefund.reason );
		expect( foundRefund.amount ).toEqual( expectedRefund.amount );
	} );

	test( 'can list all refunds', async ( { request } ) => {
		const refundCreateResponse = await request.post(
			`./wp-json/wc/v3/orders/${ orderId }/refunds`,
			{
				data: expectedRefund,
			}
		);
		const refundJSON = await refundCreateResponse.json();
		const refundsListResponse = await request.get(
			`./wp-json/wc/v3/orders/${ orderId }/refunds`
		);
		const responseJSON = await refundsListResponse.json();
		expect( refundsListResponse.status() ).toEqual( 200 );
		expect( responseJSON ).toHaveLength( 1 );
		expect( responseJSON[ 0 ].id ).toEqual( refundJSON.id );
	} );

	test( 'can delete a refund', async ( { request } ) => {
		const refundCreateResponse = await request.post(
			`./wp-json/wc/v3/orders/${ orderId }/refunds`,
			{
				data: expectedRefund,
			}
		);
		const refundJSON = await refundCreateResponse.json();
		const refundDeleteResponse = await request.delete(
			`./wp-json/wc/v3/orders/${ orderId }/refunds/${ refundJSON.id }`,
			{
				data: { force: true },
			}
		);
		const responseJSON = await refundDeleteResponse.json();
		expect( refundDeleteResponse.status() ).toEqual( 200 );
		expect( responseJSON.id ).toEqual( refundJSON.id );
		const refundRetrieveResponse = await request.get(
			`./wp-json/wc/v3/orders/${ orderId }/refunds/${ refundJSON.id }`
		);
		expect( refundRetrieveResponse.status() ).toEqual( 404 );
		const orderRetrieveResponse = await request.get(
			`./wp-json/wc/v3/orders/${ orderId }`
		);
		const retrieveOrderResponseJSON = await orderRetrieveResponse.json();
		expect( retrieveOrderResponseJSON.refunds ).toHaveLength( 0 );
	} );
} );
