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
 * WooCommerce Shipping zone endpoints.
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
		shippingZones: async ( params = {} ) =>
			getRequest( `shipping/zones`, params ),
	},
	update: {
		name: 'Update a shipping zone',
		method: 'PUT',
		path: 'shipping/zones/<id>',
		responseCode: 200,
		shippingZone: async ( shippingZoneId, updatedShippingZone ) =>
			putRequest(
				`shipping/zones/${ shippingZoneId }`,
				updatedShippingZone
			),
	},
	updateRegion: {
		name: 'Update a shipping zone region',
		method: 'PUT',
		path: 'shipping/zones/<id>/locations',
		responseCode: 200,
		shippingZone: async ( shippingZoneId, shippingZoneRegion ) =>
			putRequest(
				`shipping/zones/${ shippingZoneId }/locations`,
				shippingZoneRegion
			),
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
