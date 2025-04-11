const { refundsApi } = require( '../../endpoints/refunds' );
const { ordersApi } = require( '../../endpoints/orders' );
const { productsApi } = require( '../../endpoints/products' );
const { refund } = require( '../../data' );

/**
 * Tests for the WooCommerce Refunds API.
 *
 * @group api
 * @group refunds
 *
 */
describe( 'Refunds API tests', () => {
	let expectedRefund;
	let orderId;
	let productId;

	beforeAll( async () => {
		// Create a product and save its product ID
		const product = {
			name: 'Simple Product for Refunds API tests',
			regular_price: '100',
		};
		const createProductResponse = await productsApi.create.product(
			product
		);
		productId = createProductResponse.body.id;

		// Create an order with a product line item, and save its Order ID
		const order = {
			status: 'pending',
			line_items: [
				{
					product_id: productId,
				},
			],
		};
		const createOrderResponse = await ordersApi.create.order( order );
		orderId = createOrderResponse.body.id;

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

	afterAll( async () => {
		// Cleanup the created product and order
		await productsApi.delete.product( productId, true );
		await ordersApi.delete.order( orderId, true );
	} );

	it( 'can create a refund', async () => {
		const { status, body } = await refundsApi.create.refund(
			orderId,
			expectedRefund
		);
		expect( status ).toEqual( refundsApi.create.responseCode );
		expect( body.id ).toBeDefined();

		// Save the refund ID
		expectedRefund.id = body.id;

		// Verify that the order was refunded.
		const getOrderResponse = await ordersApi.retrieve.order( orderId );
		expect( getOrderResponse.body.refunds ).toHaveLength( 1 );
		expect( getOrderResponse.body.refunds[ 0 ].id ).toEqual(
			expectedRefund.id
		);
		expect( getOrderResponse.body.refunds[ 0 ].reason ).toEqual(
			expectedRefund.reason
		);
		expect( getOrderResponse.body.refunds[ 0 ].total ).toEqual(
			`-${ expectedRefund.amount }`
		);
	} );

	it( 'can retrieve a refund', async () => {
		const { status, body } = await refundsApi.retrieve.refund(
			orderId,
			expectedRefund.id
		);

		expect( status ).toEqual( refundsApi.retrieve.responseCode );
		expect( body.id ).toEqual( expectedRefund.id );
	} );

	it( 'can list all refunds', async () => {
		const { status, body } = await refundsApi.listAll.refunds( orderId );

		expect( status ).toEqual( refundsApi.listAll.responseCode );
		expect( body ).toHaveLength( 1 );
		expect( body[ 0 ].id ).toEqual( expectedRefund.id );
	} );

	it( 'can delete a refund', async () => {
		const { status, body } = await refundsApi.delete.refund(
			orderId,
			expectedRefund.id,
			true
		);

		expect( status ).toEqual( refundsApi.delete.responseCode );
		expect( body.id ).toEqual( expectedRefund.id );

		// Verify that the refund cannot be retrieved
		const retrieveRefundResponse = await refundsApi.retrieve.refund(
			orderId,
			expectedRefund.id
		);
		expect( retrieveRefundResponse.status ).toEqual( 404 );

		// Verify that the order no longer has a refund
		const retrieveOrderResponse = await ordersApi.retrieve.order( orderId );
		expect( retrieveOrderResponse.body.refunds ).toHaveLength( 0 );
	} );
} );
