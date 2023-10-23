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
 * @param {string} cKey Consumer key
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
 * Set up cart and checkout shortcode pages
 */
const setupCartCheckoutShortcodePages = async ( baseURL, userAgent, admin ) => {
	const { APIRequestContext } = require( '@playwright/test' );

	/**
	 * @typedef {Object} WPPage
	 * @property {number} id
	 * @property {string} slug
	 * @property {{raw: string}} content
	 */

	/**
	 * Construct an API request context.
	 * @returns {Promise<APIRequestContext>}
	 */
	async function createRequestContext() {
		const { request } = require( '@playwright/test' );
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

		return request.newContext( options );
	}

	/**
	 * Create shortcode versions of the cart and checkout pages, except when those pages already exist.
	 * @param {APIRequestContext} request
	 * @returns {Promise<[WPPage, WPPage]>}
	 */
	async function createWPPages( request ) {
		/**
		 * @returns {Promise<WPPage[]>}
		 */
		async function listPages() {
			const response = await request.get( '/wp-json/wp/v2/pages', {
				params: { context: 'edit' },
				data: {
					_fields: [ 'id', 'slug', 'content' ],
				},
				failOnStatusCode: true,
			} );

			return response.json();
		}

		/**
		 * @param {WPPage[]} pages
		 * @returns {(WPPage|undefined)}
		 */
		function findExistingShortcodeCartPage( pages ) {
			const match = pages.find( ( page ) =>
				page.content.raw.includes( '[woocommerce_cart]' )
			);

			const message = match
				? 'Shortcode version of Cart page already exists.'
				: 'No shortcode version of Cart page found.';
			console.log( message );

			return match;
		}

		/**
		 * @param {WPPage[]} pages
		 * @returns {(WPPage|undefined)}
		 */
		function findExistingShortcodeCheckoutPage( pages ) {
			const match = pages.find( ( page ) =>
				page.content.raw.includes( '[woocommerce_checkout]' )
			);

			const message = match
				? 'Shortcode version of Checkout page already exists.'
				: 'No shortcode version of Checkout page found.';
			console.log( message );

			return match;
		}

		/**
		 * @returns {Promise<WPPage>}
		 */
		async function createNewShortcodeCartPage() {
			const response = await request.post( '/wp-json/wp/v2/pages', {
				data: {
					slug: 'cart-sc',
					status: 'publish',
					title: {
						raw: 'Cart (shortcode)',
					},
					content: {
						raw: '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->',
					},
				},
				failOnStatusCode: true,
			} );

			console.log( 'New shortcode Cart page created.' );

			return response.json();
		}

		/**
		 * @returns {Promise<WPPage>}
		 */
		async function createNewShortcodeCheckoutPage() {
			const response = await request.post( '/wp-json/wp/v2/pages', {
				data: {
					slug: 'checkout-sc',
					status: 'publish',
					title: {
						raw: 'Checkout (shortcode)',
					},
					content: {
						raw: '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
					},
				},
				failOnStatusCode: true,
			} );

			console.log( 'New shortcode Checkout page created.' );

			return response.json();
		}

		const pages = await listPages();

		const cart =
			findExistingShortcodeCartPage( pages ) ||
			( await createNewShortcodeCartPage() );

		const checkout =
			findExistingShortcodeCheckoutPage( pages ) ||
			( await createNewShortcodeCheckoutPage() );

		return [ cart, checkout ];
	}

	/**
	 * Set the WC Cart and Checkout pages to the shortcode versions.
	 * @param {APIRequestContext} request
	 * @param {WPPage} cart
	 * @param {WPPage} checkout
	 */
	async function setWCPages( request, cart, checkout ) {
		await request.put(
			'/wp-json/wc/v3/settings/advanced/woocommerce_cart_page_id',
			{ data: { value: `${ cart.id }` }, failOnStatusCode: true }
		);

		console.log( `WC Cart page set to ID ${ cart.id }` );

		await request.put(
			'/wp-json/wc/v3/settings/advanced/woocommerce_checkout_page_id',
			{ data: { value: `${ checkout.id }` }, failOnStatusCode: true }
		);

		console.log( `WC Checkout page set to ID ${ checkout.id }` );
	}

	const request = await createRequestContext();
	const [ cart, checkout ] = await createWPPages( request );
	await setWCPages( request, cart, checkout );
};

module.exports = {
	reset,
	setupCartCheckoutShortcodePages,
};
