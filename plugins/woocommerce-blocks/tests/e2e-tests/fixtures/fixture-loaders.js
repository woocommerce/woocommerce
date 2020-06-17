/**
 * External dependencies
 */
const WooCommerceRestApi = require( '@woocommerce/woocommerce-rest-api' )
	.default;
const glob = require( 'glob-promise' );
const { dirname } = require( 'path' );
const { readJson } = require( 'fs-extra' );
const axios = require( 'axios' ).default;
require( 'dotenv' ).config();

/**
 * Internal dependencies
 */
const fixtures = require( './fixture-data' );

// global.process.env.WORDPRESS_BASE_URL = `${ process.env.WORDPRESS_BASE_URL }:8889`;

/**
 * ConsumerKey and ConsumerSecret are not used, we use basic auth, but
 * not providing them will throw an error.
 */
const WooCommerce = new WooCommerceRestApi( {
	url: `${ process.env.WORDPRESS_BASE_URL }/`,
	consumerKey: 'consumer_key', // Your consumer key
	consumerSecret: 'consumer_secret', // Your consumer secret
	version: 'wc/v3',
	axiosConfig: {
		auth: {
			username: process.env.WORDPRESS_LOGIN,
			password: process.env.WORDPRESS_PASSWORD,
		},
	},
} );

const WPAPI = `${ process.env.WORDPRESS_BASE_URL }/wp-json/wp/v2/pages`;

/**
 * prepare some store settings.
 *
 * @param {Object[]} fixture An array of objects describing our data, defaults
 *                           to our fixture.
 * @return {Promise} return a promise that resolves to the created data or
 * reject if the request failed.
 */
const setupSettings = ( fixture = fixtures.Settings() ) =>
	WooCommerce.post( 'settings/general/batch', {
		update: fixture,
	} );

/**
 * Create taxes.
 *
 * @param {Object[]} fixture An array of objects describing our data, defaults
 * to our fixture.
 * @return {Promise} a promise that resolves to an array of newly created taxes,
 * or rejects if the request failed.
 */
const createTaxes = ( fixture = fixtures.Taxes() ) =>
	WooCommerce.post( 'taxes/batch', {
		create: fixture,
	} ).then( ( response ) =>
		response.data.create.map( ( taxes ) => taxes.id )
	);
/**
 * Delete taxes.
 *
 * @param {number[]} ids an array of taxes IDs to delete.
 *
 * @return {Promise} return a promise that resolves to the deleted data or
 * reject if the request failed.
 */
const deleteTaxes = ( ids ) =>
	WooCommerce.post( 'taxes/batch', {
		delete: ids,
	} );

/**
 * Create Coupons.
 *
 * @param {Object[]} fixture An array of objects describing our data, defaults
 * to our fixture.
 * @return {Promise} a promise that resolves to an array of newly created coupons,
 * or rejects if the request failed.
 */
const createCoupons = ( fixture = fixtures.Coupons() ) =>
	WooCommerce.post( 'coupons/batch', {
		create: fixture,
	} ).then( ( response ) =>
		response.data.create.map( ( coupon ) => coupon.id )
	);

/**
 * Delete coupons.
 *
 * @param {number[]} ids an array of coupons IDs to delete.
 *
 * @return {Promise} return a promise that resolves to the deleted data or
 * reject if the request failed.
 */
const deleteCoupons = ( ids ) =>
	WooCommerce.post( 'coupons/batch', {
		delete: ids,
	} );
/**
 * Create Products and call createReviews.
 *
 * currently this only creates a single product for the sake of reviews.
 *
 * @todo  add more products to e2e fixtures data.
 *
 * @param {Object[]} fixture An array of objects describing our data, defaults
 * to our fixture.
 * @return {Promise} a promise that resolves to an array of newly created products,
 * or rejects if the request failed.
 */
const createProducts = ( fixture = fixtures.Products() ) =>
	WooCommerce.post( 'products/batch', {
		create: fixture,
	} ).then( ( products ) => {
		createReviews( products.data.create[ 0 ].id );
		return products.data.create.map( ( product ) => product.id );
	} );

/**
 * Delete products.
 *
 * Deleting products will also delete review.
 *
 * @param {number[]} ids an array of products IDs to delete.
 *
 * @return {Promise} return a promise that resolves to the deleted data or
 * reject if the request failed.
 */
const deleteProducts = ( ids ) =>
	WooCommerce.post( 'products/batch', {
		delete: ids,
	} );

/**
 * Create Reviews.
 *
 * This is not called directly but is called within createProducts.
 *
 * @param {number}   id      product id to assign reviews to.
 * @param {Object[]} fixture An array of objects describing our reviews, defaults
 *                           to our fixture.
 * @return {Promise} a promise that resolves to an server response data, or
 * rejects if the request failed.
 */
const createReviews = ( id, fixture = fixtures.ReviewsInProduct( id ) ) =>
	WooCommerce.post( 'products/reviews/batch', {
		create: fixture,
	} );

/**
 * Enable Cheque payments.
 *
 * This is not called directly but is called within enablePaymentGateways.
 *
 * @return {Promise} a promise that resolves to an server response data, or
 * rejects if the request failed.
 */
const enableCheque = () =>
	WooCommerce.post( 'payment_gateways/cheque', {
		enabled: true,
	} );

/**
 * Enable Paypal payments.
 *
 * This is not called directly but is called within enablePaymentGateways.
 *
 * @return {Promise} a promise that resolves to an server response data, or
 * rejects if the request failed.
 */
const enablePaypal = () =>
	WooCommerce.post( 'payment_gateways/paypal', {
		enabled: true,
	} );

/**
 * Enable payment gateways.
 *
 * It calls other individual payment gateway functions.
 *
 * @return {Promise} a promise that resolves to an array of server response
 * data, or rejects if the request failed.
 */
const enablePaymentGateways = () =>
	Promise.all( [ enableCheque(), enablePaypal() ] );

/**
 * Create shipping zones.
 *
 * Shipping locations need to be assigned to a zone, and shipping methods need
 * to be assigned to a shipping location, this create a shipping zone and
 * location and methods.
 *
 * @param {Object[]} fixture An array of objects describing our data, defaults
 *                           to our fixture.
 * @return {Promise} a promise that resolves to an array of newly created shipping
 * zones IDs, or rejects if the request failed.
 */
const createShippingZones = ( fixture = fixtures.Shipping() ) => {
	return Promise.all(
		fixture.map( ( { name, locations, methods } ) => {
			return WooCommerce.post( 'shipping/zones', { name } )
				.then( ( response ) => {
					return response.data.id;
				} )
				.then( ( zoneId ) => {
					const locationsPromise = WooCommerce.put(
						`shipping/zones/${ zoneId }/locations`,
						locations
					);

					return [ zoneId, locationsPromise ];
				} )
				.then( ( [ zoneId, locationsPromise ] ) => {
					const methodPromise = Promise.all(
						methods.map( ( method ) =>
							WooCommerce.post(
								`shipping/zones/${ zoneId }/methods`,
								method
							)
						)
					);
					return [ zoneId, methodPromise, locationsPromise ];
				} )
				.then( ( [ zoneId, methodPromise, locationsPromise ] ) =>
					Promise.all( [ methodPromise, locationsPromise ] ).then(
						() => zoneId
					)
				);
		} )
	);
};

/**
 * Delete Shipping zones.
 *
 * Deleting a shipping will also delete location and methods defined within it.
 *
 * @param {number[]} ids an array of shipping zones IDs to delete.
 *
 * @return {Promise} return a promise that resolves to an array of deleted data or
 * reject if the request failed.
 */
const deleteShippingZones = ( ids ) => {
	const deleteZone = ( id ) =>
		WooCommerce.delete( `shipping/zones/${ id }`, {
			force: true,
		} );
	return Promise.all( ids.map( deleteZone ) );
};

const createBlockPages = () => {
	return glob( `${ dirname( __filename ) }/../specs/**/*.fixture.json` ).then(
		( files ) => {
			return Promise.all(
				files.map( async ( filePath ) => {
					const file = await readJson( filePath );
					const { title, pageContent: content } = file;
					return axios
						.post(
							WPAPI,
							{
								title,
								content,
								status: 'publish',
							},
							{
								auth: {
									username: process.env.WORDPRESS_LOGIN,
									password: process.env.WORDPRESS_PASSWORD,
								},
							}
						)
						.then( ( response ) => response.data.id );
				} )
			);
		}
	);
};

const deleteBlockPages = ( ids ) => {
	return Promise.all(
		ids.map( ( id ) =>
			axios.delete( `${ WPAPI }/${ id }`, {
				params: {
					force: true,
				},
				auth: {
					username: process.env.WORDPRESS_LOGIN,
					password: process.env.WORDPRESS_PASSWORD,
				},
			} )
		)
	);
};

module.exports = {
	setupSettings,
	createTaxes,
	deleteTaxes,
	createCoupons,
	deleteCoupons,
	createProducts,
	deleteProducts,
	enablePaymentGateways,
	createShippingZones,
	deleteShippingZones,
	createBlockPages,
	deleteBlockPages,
};
