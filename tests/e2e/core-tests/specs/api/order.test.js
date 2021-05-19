/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
 const { HTTPClientFactory, Order } = require( '@woocommerce/api' );

 /**
  * External dependencies
  */
 const config = require( 'config' );
 const {
	 it,
	 describe,
	 beforeAll,
 } = require( '@jest/globals' );

 /**
 * Creates an order and tests interactions with it via the API.
 */
const runOrderApiTest = () => {
	describe('REST API > Order', () => {
		let client;
		let order;
		let repository;

		beforeAll(async () => {
			order = config.get( 'orders.basicPaidOrder' );
			const admin = config.get( 'users.admin' );
			const url = config.get( 'url' );

			client = HTTPClientFactory.build( url )
				.withBasicAuth( admin.username, admin.password )
				.withIndexPermalinks()
				.create();
		} );

		it('can create an order', async () => {
			repository = Order.restRepository( client );

			// Check properties of the order in the create order response.
			order = await repository.create( order );
			expect( order ).toEqual( expect.objectContaining( order ) );
		});

		it('can retrieve an order', async () => {
			const orderProperties = {
				id: order.id,
				payment_method: order.paymentMethod,
				status: order.status,
			};

			// Read order directly from API to compare.
			const response = await client.get( `/wc/v3/orders/${order.id}` );
			expect( response.statusCode ).toBe( 200 );
			expect( response.data ).toEqual( expect.objectContaining( orderProperties ) );
		});

		it('can update an order', async () => {
			const updatedOrderProperties = {
				payment_method: 'bacs',
				status: 'completed',
			};

			await repository.update( order.id, updatedOrderProperties );

			// Check the order response for the updated values.
			const response = await client.get( `/wc/v3/orders/${order.id}` );
			expect( response.statusCode ).toBe( 200 );
			expect( response.data ).toEqual( expect.objectContaining( updatedOrderProperties ) );
		});

		it('can delete an order', async () => {
			// Delete the order
			const status = await repository.delete( order.id );

			// If the delete is successful, the response comes back truthy
			expect( status ).toBeTruthy();
		});
	});
};

module.exports = runOrderApiTest;
