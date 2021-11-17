/**
 * Internal dependencies
 */
const {
	getRequest,
	postRequest,
	deleteRequest,
} = require( '../utils/request' );

/**
 * WooCommerce Refunds endpoints.
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#refunds
 */
const refundsApi = {
	name: 'Refunds',
	create: {
		name: 'Create a refund',
		method: 'POST',
		path: 'orders/<id>/refunds',
		responseCode: 201,
		refund: async ( orderId, refundDetails ) =>
			postRequest( `orders/${ orderId }/refunds`, refundDetails ),
	},
	retrieve: {
		name: 'Retrieve a refund',
		method: 'GET',
		path: 'orders/<id>/refunds/<refund_id>',
		responseCode: 200,
		refund: async ( orderId, refundId ) =>
			getRequest( `orders/${ orderId }/refunds/${ refundId }` ),
	},
	listAll: {
		name: 'List all refunds',
		method: 'GET',
		path: 'orders/<id>/refunds',
		responseCode: 200,
		refunds: async ( orderId ) =>
			getRequest( `orders/${ orderId }/refunds` ),
	},
	delete: {
		name: 'Delete a refund',
		method: 'DELETE',
		path: 'orders/<id>/refunds/<refund_id>',
		responseCode: 200,
		payload: {
			force: false,
		},
		refund: async ( orderId, refundId, deletePermanently ) =>
			deleteRequest(
				`orders/${ orderId }/refunds/${ refundId }`,
				deletePermanently
			),
	},
};

module.exports = {
	refundsApi: refundsApi,
};
