const { ordersApi } = require( '../../endpoints' );
const { getOrderExample } = require( '../../data' );

/**
 * Order to be searched
 */
const order = {
	...getOrderExample(),
	shipping_lines: [],
	fee_lines: [],
	coupon_lines: [],
};

/**
 * Search parameters to be used
 */
const searchParams = [
	[ 'orderId', 'orderId' ],
	[ 'billing first name', order.billing.first_name ],
	[ 'billing last name', order.billing.last_name ],
	[ 'billing company name', order.billing.company ],
	[ 'billing address 1', order.billing.address_1 ],
	[ 'billing address 2', order.billing.address_2 ],
	[ 'billing city name', order.billing.city ],
	[ 'billing post code', order.billing.postcode ],
	[ 'billing email', order.billing.email ],
	[ 'billing phone', order.billing.phone ],
	[ 'billing state', order.billing.state ],
	[ 'shipping first name', order.shipping.first_name ],
	[ 'shipping last name', order.shipping.last_name ],
	[ 'shipping address 1', order.shipping.address_1 ],
	[ 'shipping address 2', order.shipping.address_2 ],
	[ 'shipping city', order.shipping.city ],
	[ 'shipping post code', order.shipping.postcode ],
	[ 'shipping state', order.shipping.state ],
	[ 'item name', order.line_items[ 0 ].name ],
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

		const { body } = await ordersApi.listAll.orders( {
			search: searchValue,
		} );

		expect( body ).toHaveLength( 1 );
		expect( body[ 0 ].id ).toEqual( order.id );
	} );
} );
