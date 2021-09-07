/**
 * Internal dependencies
 */
const { getRequest, postRequest, putRequest, deleteRequest } = require('../utils/request');
const { order, shared } = require('../data');

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
		payload: order,
		order: async ( orderDetails ) => postRequest( 'orders', orderDetails ),
	},
	retrieve: {
		name: 'Retrieve an order',
		method: 'GET',
		path: 'orders/<id>',
		responseCode: 200,
		order: async ( orderId ) => getRequest( `orders/${orderId}` ),
	},
	listAll: {
		name: 'List all orders',
		method: 'GET',
		path: 'orders',
		responseCode: 200,
		orders: async () => { getRequest( 'orders' ) },
	},
	update: {
		name: 'Update an order',
		method: 'PUT',
		path: 'orders/<id>',
		responseCode: 200,
		payload: order.order,
		order: async ( orderId, orderDetails ) => putRequest( `orders/${orderId}`, orderDetails ),
	},
	delete: {
		name: 'Delete an order',
		method: 'DELETE',
		path: 'orders/<id>',
		responseCode: 200,
		payload: {
			force: false
		},
		order: async ( orderId, deletePermanently ) => deleteRequest( `orders/${orderId}`, deletePermanently ),
	},
	batch: {
		name: 'Batch update orders',
		method: 'POST',
		path: 'orders/batch',
		responseCode: 200,
		payload: shared.getBatchPayloadExample( order.order ),
		orders: async ( batchUpdatePayload ) => postRequest( `orders/batch`, batchUpdatePayload ),
	},
};

module.exports = {
	ordersApi,
};
