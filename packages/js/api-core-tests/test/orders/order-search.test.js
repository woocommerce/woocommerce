const { ordersApi } = require( '../../endpoints' );
const { getOrderExample, shared } = require( '../../data' );

/**
 * Order to be searched
 */
const order = {
	...getOrderExample(),
	shipping: {
		...shared.customerShipping,
		company: 'Murphy LLC',
		phone: '6146524353',
	},
	shipping_lines: [],
	fee_lines: [],
	coupon_lines: [],
};

/**
 * Search parameters to be used.
 * The following scenarios are not covered in this test suite because they're already covered in the `List all orders > search` test in `orders.test.js`
 * ```
 * can search by billing address 1
 * can search by shipping address 1
 * can search by billing last name
 * can search by billing email
 * can search by item name
 * ```
 */
const searchParams = [
	[ 'orderId', 'orderId' ],
	[ 'billing first name', order.billing.first_name ],
	[ 'billing company name', order.billing.company ],
	[ 'billing address 2', order.billing.address_2 ],
	[ 'billing city name', order.billing.city ],
	[ 'billing post code', order.billing.postcode ],
	[ 'billing phone', order.billing.phone ],
	[ 'billing state', order.billing.state ],
	[ 'shipping first name', order.shipping.first_name ],
	[ 'shipping last name', order.shipping.last_name ],
	[ 'shipping address 2', order.shipping.address_2 ],
	[ 'shipping city', order.shipping.city ],
	[ 'shipping post code', order.shipping.postcode ],
	[ 'shipping state', order.shipping.state ],
];

/**
 * Tests for the WooCommerce Order Search API.
 *
 * @group api
 * @group orders
 *
 */
describe( 'Order Search API tests', () => {
	beforeAll( async () => {
		// Create an order and save its ID
		const { body } = await ordersApi.create.order( order );
		order.id = body.id;
	} );

	afterAll( async () => {
		// Cleanup: Delete the order
		await ordersApi.delete.order( order.id, true );
	} );

	it.each( searchParams )( 'can search by %s', async ( title, param ) => {
		const searchValue = param === 'orderId' ? order.id : param;

		const { status, body } = await ordersApi.listAll.orders( {
			search: searchValue,
		} );

		expect( status ).toEqual( ordersApi.listAll.responseCode );
		expect( body ).toHaveLength( 1 );
		expect( body[ 0 ].id ).toEqual( order.id );
	} );

	it( 'can return an empty result set when no matches were found', async () => {
		const { status, body } = await ordersApi.listAll.orders( {
			search: 'Chauncey Smith Kunde',
		} );

		expect( status ).toEqual( ordersApi.listAll.responseCode );
		expect( body ).toEqual( [] );
	} );
} );
