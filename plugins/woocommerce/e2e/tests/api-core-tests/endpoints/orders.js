const { getOrderExample, shared } = require( '../data' );

/**
 * WooCommerce Orders endpoints.
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#orders
 */
const ordersApi = {
	name: 'Orders',
	create: {
		name: 'Create an order',
		method: 'POST',
		path: 'orders',
		responseCode: 201,
		payload: getOrderExample(),
	},
	retrieve: {
		name: 'Retrieve an order',
		method: 'GET',
		path: 'orders/<id>',
		responseCode: 200,
	},
	listAll: {
		name: 'List all orders',
		method: 'GET',
		path: 'orders',
		responseCode: 200,
	},
	update: {
		name: 'Update an order',
		method: 'PUT',
		path: 'orders/<id>',
		responseCode: 200,
		payload: getOrderExample(),
	},
	delete: {
		name: 'Delete an order',
		method: 'DELETE',
		path: 'orders/<id>',
		responseCode: 200,
		payload: {
			force: false,
		},
	},
	batch: {
		name: 'Batch update orders',
		method: 'POST',
		path: 'orders/batch',
		responseCode: 200,
		payload: shared.getBatchPayloadExample( getOrderExample() ),
	},
};

module.exports = {
	ordersApi,
};
