const { coupon, shared } = require( '../data' );

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
	},
	retrieve: {
		name: 'Retrieve a coupon',
		method: 'GET',
		path: 'coupons/<id>',
		responseCode: 200,
	},
	listAll: {
		name: 'List all coupons',
		method: 'GET',
		path: 'coupons',
		responseCode: 200,
	},
	update: {
		name: 'Update a coupon',
		method: 'PUT',
		path: 'coupons/<id>',
		responseCode: 200,
		payload: coupon,
	},
	delete: {
		name: 'Delete a coupon',
		method: 'DELETE',
		path: 'coupons/<id>',
		responseCode: 200,
		payload: {
			force: false,
		},
	},
	batch: {
		name: 'Batch update coupons',
		method: 'POST',
		path: 'coupons/batch',
		responseCode: 200,
		payload: shared.getBatchPayloadExample( coupon ),
	},
};

module.exports = { couponsApi };
