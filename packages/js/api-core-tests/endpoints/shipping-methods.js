/**
 * Internal dependencies
 */
const {
	getRequest,
	postRequest,
	putRequest,
	deleteRequest,
} = require( '../utils/request' );

/**
 * WooCommerce Shipping method endpoints.
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#shipping-methods
 */
const shippingMethodsApi = {
	name: 'Shipping methods',
	create: {
		name: 'Include a shipping method to a shipping zone',
		method: 'POST',
		path: 'shipping/zones/<id>/methods',
		responseCode: 200,
		shippingMethod: async ( shippingZoneId, shippingMethod ) =>
			postRequest(
				`shipping/zones/${ shippingZoneId }/methods`,
				shippingMethod
			),
	},
	retrieve: {
		name: 'Retrieve a shipping method from a shipping zone',
		method: 'GET',
		path: 'shipping/zones/<zone_id>/methods/<id>',
		responseCode: 200,
		shippingMethod: async ( shippingZoneId, shippingMethodInstanceId ) =>
			getRequest(
				`shipping/zones/${ shippingZoneId }/methods/${ shippingMethodInstanceId }`
			),
	},
	listAll: {
		name: 'List all shipping methods from a shipping zone',
		method: 'GET',
		path: 'shipping/zones/<id>/methods',
		responseCode: 200,
		shippingMethods: async ( shippingZoneId, params = {} ) =>
			getRequest( `shipping/zones/${ shippingZoneId }/methods`, params ),
	},
	update: {
		name: 'Update a shipping method of a shipping zone',
		method: 'PUT',
		path: 'shipping/zones/<zone_id>/methods/<id>',
		responseCode: 200,
		shippingMethod: async (
			shippingZoneId,
			shippingMethodInstanceId,
			updatedShippingMethod
		) =>
			putRequest(
				`shipping/zones/${ shippingZoneId }/methods/${ shippingMethodInstanceId }`,
				updatedShippingMethod
			),
	},
	delete: {
		name: 'Delete a shipping method from a shipping zone',
		method: 'DELETE',
		path: 'shipping/zones/<zone_id>/methods/<id>>',
		responseCode: 200,
		payload: {
			force: false,
		},
		shippingMethod: async (
			shippingZoneId,
			shippingMethodInstanceId,
			deletePermanently
		) =>
			deleteRequest(
				`shipping/zones/${ shippingZoneId }/methods/${ shippingMethodInstanceId }`,
				deletePermanently
			),
	},
};

module.exports = {
	shippingMethodsApi,
};
