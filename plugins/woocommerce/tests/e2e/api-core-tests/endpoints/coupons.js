/**
 * Internal dependencies
 */
 const { getRequest, postRequest, putRequest, deleteRequest } = require('../utils/request');
 const { coupon, shared } = require('../data');

/**
 * WooCommerce Coupon endpoints.
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#coupons
 */
const couponsApi = {
	name: 'Coupons',
	create: {
		name: 'Create a coupon',
		method: 'POST',
		path: 'coupons',
		responseCode: 201,
		payload: coupon,
		coupon: async ( couponDetails ) => postRequest( 'coupons', couponDetails ),
	},
	retrieve: {
		name: 'Retrieve a coupon',
		method: 'GET',
		path: 'coupons/<id>',
		responseCode: 200,
		coupon: async ( couponId ) => getRequest( `coupons/${couponId}` ),
	},
	listAll: {
		name: 'List all coupons',
		method: 'GET',
		path: 'coupons',
		responseCode: 200,
		coupons: async () => getRequest( 'coupons' ),
	},
	update: {
		name: 'Update a coupon',
		method: 'PUT',
		path: 'coupons/<id>',
		responseCode: 200,
		payload: coupon,
		coupon: async ( couponId, couponDetails ) => putRequest( `coupons/${couponId}`, couponDetails ),
	},
	delete: {
		name: 'Delete a coupon',
		method: 'DELETE',
		path: 'coupons/<id>',
		responseCode: 200,
		payload: {
			force: false
		},
		coupon: async ( couponId, deletePermanently ) => deleteRequest( `coupons/${couponId}`, deletePermanently ),
	},
	batch: {
		name: 'Batch update coupons',
		method: 'POST',
		path: 'coupons/batch',
		responseCode: 200,
		payload: shared.getBatchPayloadExample( coupon ),
		coupons: async ( batchUpdatePayload ) => postRequest( `coupons/batch`, batchUpdatePayload ),
	},
};

 module.exports = { couponsApi };
