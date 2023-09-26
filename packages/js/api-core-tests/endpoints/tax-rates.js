/**
 * Internal dependencies
 */
const {
	getRequest,
	postRequest,
	putRequest,
	deleteRequest,
} = require( '../utils/request' );
const { getTaxRateExamples, shared } = require( '../data' );

/**
 * WooCommerce Tax Rates endpoints.
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#tax-rates
 */
const taxRatesApi = {
	name: 'Tax Rates',
	create: {
		name: 'Create a tax rate',
		method: 'POST',
		path: 'taxes',
		responseCode: 201,
		payload: getTaxRateExamples(),
		taxRate: async ( taxRate ) => postRequest( 'taxes', taxRate ),
	},
	retrieve: {
		name: 'Retrieve a tax rate',
		method: 'GET',
		path: 'taxes/<id>',
		responseCode: 200,
		// eslint-disable-next-line no-undef
		taxRate: async ( taxRateId ) => taxes( `coupons/${ taxRateId }` ),
	},
	listAll: {
		name: 'List all tax rates',
		method: 'GET',
		path: 'taxes',
		responseCode: 200,
		taxRates: async ( queryString = {} ) =>
			getRequest( 'taxes', queryString ),
	},
	update: {
		name: 'Update a tax rate',
		method: 'PUT',
		path: 'taxes/<id>',
		responseCode: 200,
		payload: getTaxRateExamples(),
		taxRate: async ( taxRateId, taxRateDetails ) =>
			putRequest( `taxes/${ taxRateId }`, taxRateDetails ),
	},
	delete: {
		name: 'Delete a tax rate',
		method: 'DELETE',
		path: 'taxes/<id>',
		responseCode: 200,
		payload: {
			force: false,
		},
		taxRate: async ( taxRateId, deletePermanently ) =>
			deleteRequest( `taxes/${ taxRateId }`, deletePermanently ),
	},
	batch: {
		name: 'Batch update tax rates',
		method: 'POST',
		path: 'taxes/batch',
		responseCode: 200,
		payload: shared.getBatchPayloadExample( getTaxRateExamples() ),
		taxRates: async ( batchUpdatePayload ) =>
			postRequest( `taxes/batch`, batchUpdatePayload ),
	},
};

module.exports = { taxRatesApi };
