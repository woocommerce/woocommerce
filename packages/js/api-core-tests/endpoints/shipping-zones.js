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
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#shipping-zones
 */
const shippingZonesApi = {
	name: 'Shipping zones',
	create: {
		name: 'Create a shipping zone',
		method: 'POST',
		path: 'shipping/zones',
		responseCode: 201,
		shippingZone: async ( shippingZone ) =>
			postRequest( `shipping/zones`, shippingZone ),
	},
	retrieve: {
		name: 'Retrieve a shipping zone',
		method: 'GET',
		path: 'shipping/zones/<id>',
		responseCode: 200,
		shippingZone: async ( shippingZoneId ) =>
			getRequest( `shipping/zones/${ shippingZoneId }` ),
	},
	listAll: {
		name: 'List all shipping zones',
		method: 'GET',
		path: 'shipping/zones',
		responseCode: 200,
		shippingZone: async () => getRequest( `shipping/zones` ),
	},
	delete: {
		name: 'Delete a shipping zone',
		method: 'DELETE',
		path: 'shipping/zones/<id>',
		responseCode: 200,
		payload: {
			force: false,
		},
		shippingZone: async ( shippingZoneId, deletePermanently ) =>
			deleteRequest(
				`shipping/zones/${ shippingZoneId }`,
				deletePermanently
			),
	},
};

module.exports = {
	shippingZonesApi,
};
