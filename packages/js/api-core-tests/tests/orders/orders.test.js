const { ordersApi } = require('../../endpoints/orders');
const { order } = require('../../data');
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
describe('Orders API tests', () => {
	let orderId, sampleData;

	beforeAll( async () => {
		sampleData = await createSampleData();
	}, 100000 );

	afterAll( async () => {
		await deleteSampleData( sampleData );
	}, 10000 );

	it('can create an order', async () => {
		const response = await ordersApi.create.order( order );
		expect( response.status ).toEqual( ordersApi.create.responseCode );
		expect( response.body.id ).toBeDefined();
		orderId = response.body.id;

		// Validate the data type and verify the order is in a pending state
		expect( typeof response.body.status ).toBe('string');
		expect( response.body.status ).toEqual('pending');
	});

	it('can retrieve an order', async () => {
		const response = await ordersApi.retrieve.order( orderId );
		expect( response.status ).toEqual( ordersApi.retrieve.responseCode );
		expect( response.body.id ).toEqual( orderId );
	});

	it('can add shipping and billing contacts to an order', async () => {
		// Update the billing and shipping fields on the order
		order.billing = updatedCustomerBilling;
		order.shipping = updatedCustomerShipping;

		const response = await ordersApi.update.order( orderId, order );
		expect( response.status).toEqual( ordersApi.update.responseCode );

		expect( response.body.billing ).toEqual( updatedCustomerBilling );
		expect( response.body.shipping ).toEqual( updatedCustomerShipping );
	});

	it('can permanently delete an order', async () => {
		const response = await ordersApi.delete.order( orderId, true );
		expect( response.status ).toEqual( ordersApi.delete.responseCode );

		const getOrderResponse = await ordersApi.retrieve.order( orderId );
		expect( getOrderResponse.status ).toEqual( 404 );
	});
});
