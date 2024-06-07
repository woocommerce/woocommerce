const api = require( './api' );

const deleteAllCoupons = async () => {
	console.log( 'Deleting all coupons...' );

	let coupons,
		page = 1;

	while (
		( coupons = await api.get.coupons( { per_page: 100, page: page++ } ) )
			.length > 0
	) {
		const ids = coupons.map( ( { id } ) => id );
		await api.deletePost.coupons( ids );
	}

	console.log( 'Done.' );
};

const deleteAllProducts = async () => {
	console.log( 'Deleting all products...' );

	let products,
		page = 1;

	while (
		( products = await api.get.products( { per_page: 100, page: page++ } ) )
			.length > 0
	) {
		const ids = products.map( ( { id } ) => id );
		await api.deletePost.products( ids );
	}

	console.log( 'Done.' );
};

const deleteAllProductAttributes = async () => {
	console.log( 'Deleting all product attributes...' );

	let attributes,
		page = 1;

	while (
		( attributes = await api.get.productAttributes( {
			per_page: 100,
			page: page++,
		} ) ).length > 0
	) {
		const ids = attributes.map( ( { id } ) => id );
		await api.deletePost.productAttributes( ids );
	}

	console.log( 'Done.' );
};

const deleteAllProductCategories = async () => {
	console.log( 'Deleting all product categories...' );

	let categories,
		page = 1;

	// Exclude "Uncategorized" as it cannot be deleted
	while (
		( categories = (
			await api.get.productCategories( { per_page: 100, page: page++ } )
		 ).filter( ( { slug } ) => slug !== 'uncategorized' ) ).length > 0
	) {
		const ids = categories.map( ( { id } ) => id );
		await api.deletePost.productCategories( ids );
	}

	console.log( 'Done.' );
};

const deleteAllProductTags = async () => {
	console.log( 'Deleting all product tags...' );

	let tags,
		page = 1;

	while (
		( tags = await api.get.productTags( {
			per_page: 100,
			page: page++,
		} ) ).length > 0
	) {
		const ids = tags.map( ( { id } ) => id );
		await api.deletePost.productTags( ids );
	}

	console.log( 'Done.' );
};

const deleteAllOrders = async () => {
	console.log( 'Deleting all orders...' );

	let orders,
		page = 1;

	while (
		( orders = await api.get.orders( { per_page: 100, page: page++ } ) )
			.length > 0
	) {
		const ids = orders.map( ( { id } ) => id );
		await api.deletePost.orders( ids );
	}

	console.log( 'Done.' );
};

const deleteAllShippingZones = async () => {
	console.log( 'Deleting all shipping zones...' );

	let shippingZones,
		page = 1;

	// Exclude "Locations not covered by your other zones" as it cannot be deleted.
	while (
		( shippingZones = (
			await api.get.shippingZones( {
				per_page: 100,
				page: page++,
			} )
		 ).filter(
			( { name } ) => name !== 'Locations not covered by your other zones'
		) ).length > 0
	) {
		const ids = shippingZones.map( ( { id } ) => id );
		for ( const id of ids ) {
			await api.deletePost.shippingZone( id );
		}
	}

	console.log( 'Done.' );
};

const deleteAllShippingClasses = async () => {
	console.log( 'Deleting all shipping classes...' );

	let shippingClasses,
		page = 1;

	while (
		( shippingClasses = await api.get.shippingClasses( {
			per_page: 100,
			page: page++,
		} ) ).length > 0
	) {
		const ids = shippingClasses.map( ( { id } ) => id );
		await api.deletePost.shippingClasses( ids );
	}

	console.log( 'Done.' );
};

const deleteAllShippingMethodsInDefaultShippingZone = async () => {
	console.log( 'Deleting all shipping methods...' );

	let shippingMethods;

	while (
		( shippingMethods = await api.get.shippingZoneMethods( 0 ) ).length > 0
	) {
		const ids = shippingMethods.map( ( { id } ) => id );
		for ( const id of ids ) {
			await api.deletePost.shippingZoneMethod( 0, id );
		}
	}

	console.log( 'Done.' );
};

const deleteAllTaxClasses = async () => {
	console.log( 'Deleting all non-default tax classes...' );

	let taxClasses;

	const getExistingNonDefaultTaxClasses = async () => {
		return ( await api.get.taxClasses() ).filter(
			( { slug } ) =>
				! [ 'standard', 'reduced-rate', 'zero-rate' ].includes( slug )
		);
	};

	while (
		( taxClasses = await getExistingNonDefaultTaxClasses() ).length > 0
	) {
		const slugs = taxClasses.map( ( { slug } ) => slug );
		for ( const slug of slugs ) {
			await api.deletePost.taxClass( slug );
		}
	}

	console.log( 'Done.' );
};

const deleteAllTaxRates = async () => {
	console.log( 'Deleting all tax rates...' );

	let taxes,
		page = 1;

	while (
		( taxes = await api.get.taxRates( { per_page: 100, page: page++ } ) )
			.length > 0
	) {
		const ids = taxes.map( ( { id } ) => id );
		await api.deletePost.taxRates( ids );
	}

	console.log( 'Done.' );
};

/**
 * Reset the test site. Useful when running E2E tests on a hosted test site to reset it to a somewhat pristine state prior to running tests.
 *
 * @param {string} cKey    Consumer key
 * @param {string} cSecret Consumer secret
 */
const reset = async ( cKey, cSecret ) => {
	console.log( '--------------------------' );
	console.log( 'Resetting test site...' );
	console.log( '--------------------------' );

	api.constructWith( cKey, cSecret );

	await deleteAllCoupons();
	await deleteAllProducts();
	await deleteAllProductAttributes();
	await deleteAllProductCategories();
	await deleteAllProductTags();
	await deleteAllOrders();
	await deleteAllShippingClasses();
	await deleteAllShippingZones();
	await deleteAllShippingMethodsInDefaultShippingZone();
	await deleteAllTaxClasses();
	await deleteAllTaxRates();
};

/**
 * Convert Cart and Checkout pages to shortcode.
 */
const useCartCheckoutShortcodes = async ( baseURL, userAgent, admin ) => {
	const { request: apiRequest } = require( '@playwright/test' );
	const { encodeCredentials } = require( './plugin-utils' );

	const basicAuth = encodeCredentials( admin.username, admin.password );
	const Authorization = `Basic ${ basicAuth }`;
	const extraHTTPHeaders = {
		Authorization,
	};
	const options = {
		baseURL,
		userAgent,
		extraHTTPHeaders,
	};
	const request = await apiRequest.newContext( options );

	// List all pages
	const response_list = await request.get(
		'/wp-json/wp/v2/pages?slug=cart,checkout',
		{
			data: {
				_fields: [ 'id', 'slug' ],
			},
			failOnStatusCode: true,
		}
	);

	const list = await response_list.json();

	// Find the cart and checkout pages
	const cart = list.find( ( page ) => page.slug === 'cart' );
	const checkout = list.find( ( page ) => page.slug === 'checkout' );

	// Convert their contents to shortcodes
	await request.put( `/wp-json/wp/v2/pages/${ cart.id }`, {
		data: {
			content: {
				raw: '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->',
			},
		},
		failOnStatusCode: true,
	} );
	console.log( 'Cart page converted to shortcode.' );

	await request.put( `/wp-json/wp/v2/pages/${ checkout.id }`, {
		data: {
			content: {
				raw: '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
			},
		},
		failOnStatusCode: true,
	} );
	console.log( 'Checkout page converted to shortcode.' );
};

module.exports = {
	reset,
	useCartCheckoutShortcodes,
};
