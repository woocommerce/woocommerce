/**
 * Internal dependencies
 */
const {
	getRequest,
	postRequest,
	putRequest,
	deleteRequest,
} = require( '../utils/request' );
const { getVariationExample, shared } = require( '../data' );

/**
 * WooCommerce Product Variation endpoints.
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#product-variations
 */
const variationsApi = {
	name: 'Product variations',
	create: {
		name: 'Create a product variation',
		method: 'POST',
		path: 'products/<product_id>/variations',
		responseCode: 201,
		payload: getVariationExample(),
		variation: async ( productId, variation ) =>
			postRequest( `products/${ productId }/variations`, variation ),
	},
	retrieve: {
		name: 'Retrieve a product variation',
		method: 'GET',
		path: 'products/<product_id>/variations/<id>',
		responseCode: 200,
		variation: async ( productId, variationId ) =>
			`products/${ productId }/variations/${ variationId }`,
	},
	listAll: {
		name: 'List all product variations',
		method: 'GET',
		path: 'products/<product_id>/variations',
		responseCode: 200,
		variations: async ( productId, queryString = {} ) =>
			getRequest( `products/${ productId }/variations`, queryString ),
	},
	update: {
		name: 'Update a product variation',
		method: 'PUT',
		path: 'products/<product_id>/variations/<id>',
		responseCode: 200,
		payload: getVariationExample(),
		// eslint-disable-next-line
		variation: async ( productId, variationId, variationDetails ) =>
			putRequest(
				`products/${ productId }/variations/${ variationId }`,
				// eslint-disable-next-line
				taxRateDetails
			),
	},
	delete: {
		name: 'Delete a product variation',
		method: 'DELETE',
		path: 'products/<product_id>/variations/<id>',
		responseCode: 200,
		payload: {
			force: false,
		},
		variation: async ( productId, variationId, deletePermanently ) =>
			deleteRequest(
				`products/${ productId }/variations/${ variationId }`,
				deletePermanently
			),
	},
	batch: {
		name: 'Batch update product variations',
		method: 'POST',
		path: 'products/<product_id>/variations/batch',
		responseCode: 200,
		payload: shared.getBatchPayloadExample( getVariationExample() ),
		variations: async ( batchUpdatePayload ) =>
			postRequest(
				// eslint-disable-next-line
				`products/${ productId }/variations/${ variationId }`,
				batchUpdatePayload
			),
	},
};

module.exports = { variationsApi };
