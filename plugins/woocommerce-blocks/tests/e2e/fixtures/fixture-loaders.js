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

const setupPageSettings = () => {
	axios.get( WPAPI ).then( ( response ) => {
		const fixture = fixtures.PageSettings( response.data );
		WooCommerce.post( 'settings/advanced/batch', {
			update: fixture,
		} );
	} );
};

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
 * Create Product Categories.
 *
 * @param {Object[]} fixture An array of objects describing our data, defaults
 * to our fixture.
 * @return {Promise} a promise that resolves to an array of newly created categories,
 * or rejects if the request failed.
 */
const createCategories = ( fixture = fixtures.Categories() ) =>
	WooCommerce.post( 'products/categories/batch', {
		create: fixture,
	} ).then( ( response ) => response.data.create );

/**
 * Delete Product Categories.
 *
 * @param {Object[]} categories an array of categories to delete.
 *
 * @return {Promise} return a promise that resolves to the deleted data or
 * reject if the request failed.
 */
const deleteCategories = ( categories ) => {
	const ids = categories.map( ( category ) => category.id );

	return WooCommerce.post( 'products/categories/batch', {
		delete: ids,
	} );
};

/**
 * Create Products.
 *
 * currently this only creates a single product for the sake of reviews.
 *
 * @todo  add more products to e2e fixtures data.
 *
 * @param {Array}    categories Array of category objects so we can replace names with ids in the request.
 * @param {Array}    attributes Array of attribute objects so we can replace names with ids in the request.
 * @param {Object[]} fixture An array of objects describing our data, defaults
 * to our fixture.
 * @return {Promise} a promise that resolves to an array of newly created products,
 * or rejects if the request failed.
 */
const createProducts = (
	categories,
	attributes,
	fixture = fixtures.Products()
) => {
	const hydratedFixture = fixture.map( ( product ) => {
		if ( categories && product.categories ) {
			product.categories = product.categories.map( ( categoryName ) =>
				categories.find(
					( category ) => category.name === categoryName
				)
			);
		}
		if ( attributes && product.attributes ) {
			product.attributes = product.attributes.map(
				( productAttribute ) => {
					return {
						...attributes.find(
							( attribute ) =>
								attribute.name === productAttribute.name
						),
						...productAttribute,
					};
				}
			);
		}
		return product;
	} );
	return WooCommerce.post( 'products/batch', {
		create: hydratedFixture,
	} ).then( ( products ) => {
		return products.data.create.map( ( product ) => product.id );
	} );
};

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
 * Enable payment gateways.
 *
 * It calls other individual payment gateway functions.
 *
 * @return {Promise} a promise that resolves to an array of server response
 * data, or rejects if the request failed.
 */
const enablePaymentGateways = () => Promise.all( [ enableCheque() ] );

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

/**
 * Create Products attributes and terms.
 *
 * @param {Object[]} fixture An array of objects describing our data, defaults
 *                           to our fixture.
 * @return {Promise} a promise that resolves to an array of newly created product attributes IDs, or rejects if the request failed.
 */
const createProductAttributes = ( fixture = fixtures.Attributes() ) => {
	return Promise.all(
		fixture.map( ( { attribute, terms } ) => {
			return WooCommerce.post( 'products/attributes', attribute )
				.then( ( response ) => {
					return response.data.id;
				} )
				.then( ( attributeId ) => {
					const termsPromise = WooCommerce.put(
						`products/attributes/${ attributeId }/terms/batch`,
						{ create: terms }
					);

					return [ attributeId, termsPromise ];
				} )
				.then( ( [ attributeId, termsPromise ] ) =>
					Promise.all( [ attributeId, termsPromise ] ).then( () => ( {
						name: attribute.name,
						id: attributeId,
					} ) )
				)
				.catch( () => {
					// At this point, the attributes probably already exist. Get them and return them instead.
					return WooCommerce.get( 'products/attributes' ).then(
						( response ) => response.data
					);
				} );
		} )
	);
};

/**
 * Delete Products attributes.
 *
 * Deleting all passed product attributes, will also delete terms within it.
 *
 * @param {number[]} ids an array of product attributes IDs to delete.
 *
 * @return {Promise} return a promise that resolves to an array of deleted data or
 * reject if the request failed.
 */
const deleteProductAttributes = ( ids ) => {
	return WooCommerce.post( 'products/attributes/batch', { delete: ids } );
};

module.exports = {
	createProductAttributes,
	deleteProductAttributes,
	setupSettings,
	setupPageSettings,
	createTaxes,
	deleteTaxes,
	createCoupons,
	deleteCoupons,
	createCategories,
	deleteCategories,
	createProducts,
	deleteProducts,
	createReviews,
	enablePaymentGateways,
	createShippingZones,
	deleteShippingZones,
	createBlockPages,
	deleteBlockPages,
};
