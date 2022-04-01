/**
 * Internal dependencies
 */
const { getRequest, postRequest, putRequest, deleteRequest } = require('../utils/request');

/**
 * WooCommerce Products endpoints.
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#products
 */
const productsApi = {
	name: 'Products',
	create: {
		name: 'Create a product',
		method: 'POST',
		path: 'products',
		responseCode: 201,
		product: async ( productDetails ) => postRequest( 'products', productDetails ),
	},
	retrieve: {
		name: 'Retrieve a product',
		method: 'GET',
		path: 'products/<id>',
		responseCode: 200,
		product: async ( productId ) => getRequest( `products/${productId}` ),
	},
	listAll: {
		name: 'List all products',
		method: 'GET',
		path: 'products',
		responseCode: 200,
		products: async ( productsQuery = {} ) => getRequest( 'products', productsQuery ),
	},
	update: {
		name: 'Update a product',
		method: 'PUT',
		path: 'products/<id>',
		responseCode: 200,
		product: async ( productId, productDetails ) => putRequest( `products/${productId}`, productDetails ),
	},
	delete: {
		name: 'Delete a product',
		method: 'DELETE',
		path: 'products/<id>',
		responseCode: 200,
		payload: {
			force: false
		},
		product: async ( productId, deletePermanently ) => deleteRequest( `products/${productId}`, deletePermanently ),
	},
	batch: {
		name: 'Batch update products',
		method: 'POST',
		path: 'products/batch',
		responseCode: 200,
		products: async ( batchUpdatePayload ) => postRequest( `products/batch`, batchUpdatePayload ),
	},
};

module.exports = {
	productsApi,
};
