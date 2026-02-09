/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { getSetting } from '@woocommerce/settings';
import { blocksConfig } from '@woocommerce/block-settings';

/**
 * Get product query requests for the Store API.
 *
 * @param {Object}                     request           A query object with the list of selected products and search term.
 * @param {number[]}                   request.selected  Currently selected products.
 * @param {string=}                    request.search    Search string.
 * @param {(Record<string, unknown>)=} request.queryArgs Query args to pass in.
 */
const getProductsRequests = ( {
	selected = [],
	search = '',
	queryArgs = {},
} ) => {
	// Since the Store API has a maximum of 100 products per request, selected products
	// might not be present in the main request and will need to be fetched separately.
	const loadSelected = blocksConfig.productCount > 100 && selected.length > 0;

	// Main request for products matching search criteria.
	const requests = [
		addQueryArgs( '/wc/store/v1/products', {
			per_page: 100,
			catalog_visibility: 'any',
			search,
			orderby: 'title',
			order: 'asc',
			exclude: loadSelected ? selected : [],
			...queryArgs,
		} ),
	];

	if ( loadSelected ) {
		const selectedPages = Math.ceil( selected.length / 100 );
		for ( let page = 1; page <= selectedPages; page++ ) {
			requests.push(
				addQueryArgs( '/wc/store/v1/products', {
					catalog_visibility: 'any',
					include: selected,
					per_page: 100,
					page,
				} )
			);
		}
	}

	return requests;
};

/**
 * Get a promise that resolves to a list of products from the Store API.
 *
 * @param {Object}                     request           A query object with the list of selected products and search term.
 * @param {number[]=}                  request.selected  Currently selected products.
 * @param {string=}                    request.search    Search string.
 * @param {(Record<string, unknown>)=} request.queryArgs Query args to pass in.
 * @return {Promise<unknown>} Promise resolving to a Product list.
 * @throws Exception if there is an error.
 */
export const getProducts = ( {
	selected = [],
	search = '',
	queryArgs = {},
} ) => {
	const requests = getProductsRequests( { selected, search, queryArgs } );

	return Promise.all( requests.map( ( path ) => apiFetch( { path } ) ) ).then(
		( data ) => [
			...new Map(
				data.flatMap( ( products ) =>
					products.map( ( item ) => [
						item.id,
						{ ...item, parent: 0 },
					] )
				)
			).values(),
		]
	);
};

/**
 * Get a promise that resolves to a product object from the Store API.
 *
 * @param {number} productId Id of the product to retrieve.
 */
export const getProduct = ( productId ) => {
	return apiFetch( {
		path: `/wc/store/v1/products/${ productId }`,
	} );
};

/**
 * Get a promise that resolves to a list of attribute objects from the Store API.
 */
export const getAttributes = () => {
	return apiFetch( {
		path: `wc/store/v1/products/attributes`,
	} );
};

/**
 * Get a promise that resolves to a list of attribute term objects from the Store API.
 *
 * @param {number} attribute Id of the attribute to retrieve terms for.
 */
export const getTerms = ( attribute ) => {
	return apiFetch( {
		path: `wc/store/v1/products/attributes/${ attribute }/terms`,
	} );
};

/**
 * Get product tag query requests for the Store API.
 *
 * @param {Object} request          A query object with the list of selected products and search term.
 * @param {Array}  request.selected Currently selected tags.
 * @param {string} request.search   Search string.
 */
const getProductTagsRequests = ( { selected = [], search } ) => {
	const limitTags = getSetting( 'limitTags', false );
	const requests = [
		addQueryArgs( `wc/store/v1/products/tags`, {
			per_page: limitTags ? 100 : 0,
			orderby: limitTags ? 'count' : 'name',
			order: limitTags ? 'desc' : 'asc',
			search,
		} ),
	];

	// If we have a large catalog, we might not get all selected products in the first page.
	if ( limitTags && selected.length ) {
		requests.push(
			addQueryArgs( `wc/store/v1/products/tags`, {
				include: selected,
			} )
		);
	}

	return requests;
};

/**
 * Get a promise that resolves to a list of tags from the Store API.
 *
 * @param {Object} props          A query object with the list of selected products and search term.
 * @param {Array}  props.selected
 * @param {string} props.search
 */
export const getProductTags = ( { selected = [], search } ) => {
	const requests = getProductTagsRequests( { selected, search } );

	return Promise.all( requests.map( ( path ) => apiFetch( { path } ) ) ).then(
		( data ) => [
			...new Map(
				data.flatMap( ( tags ) =>
					tags.map( ( item ) => [ item.id, item ] )
				)
			).values(),
		]
	);
};

/**
 * Get a promise that resolves to a list of category objects from the Store API.
 *
 * @param {Object} queryArgs Query args to pass in.
 */
export const getCategories = ( queryArgs ) => {
	return apiFetch( {
		path: addQueryArgs( `wc/store/v1/products/categories`, {
			per_page: 0,
			...queryArgs,
		} ),
	} );
};

/**
 * Get a promise that resolves to a category object from the API.
 *
 * @param {number} categoryId Id of the product to retrieve.
 */
export const getCategory = ( categoryId ) => {
	return apiFetch( {
		path: `wc/store/v1/products/categories/${ categoryId }`,
	} );
};

/**
 * Get a promise that resolves to a list of variation objects from the Store API
 * and the total number of variations.
 *
 * @param {number} product Product ID.
 * @param {Object} args    Query args to pass in.
 */
export const getProductVariationsWithTotal = ( product, args = {} ) => {
	return apiFetch( {
		path: addQueryArgs( `wc/store/v1/products`, {
			type: 'variation',
			parent: product,
			orderby: 'title',
			per_page: 25,
			...args,
		} ),
		parse: false,
	} ).then( ( response ) => {
		return response.json().then( ( data ) => {
			const totalHeader = response.headers.get( 'x-wp-total' );
			return {
				variations: data,
				total: totalHeader ? Number( totalHeader ) : null,
			};
		} );
	} );
};

/**
 * Get a promise that resolves to a list of variation objects from the Store API.
 *
 * NOTE: If implementing new features, prefer using the
 * `getProductVariationsWithTotal()` function above, as it doesn't default to
 * `per_page: 0`.
 * See: https://github.com/woocommerce/woocommerce/pull/61755#issuecomment-3499859585
 *
 * @param {number} product Product ID.
 */
export const getProductVariations = ( product ) => {
	// Fetch the first page to get total page count from headers
	return apiFetch( {
		path: addQueryArgs( `wc/store/v1/products`, {
			per_page: 100,
			type: 'variation',
			parent: product,
			page: 1,
		} ),
		parse: false,
	} )
		.then( ( response ) => {
			const totalPages = parseInt(
				response.headers.get( 'X-WP-TotalPages' ) || '1',
				10
			);

			// Parse the first page data
			const firstPagePromise = response.json();

			// Build array of fetch promises for remaining pages (starting at page 2)
			const remainingRequests = [];
			for ( let page = 2; page <= totalPages; page++ ) {
				remainingRequests.push(
					apiFetch( {
						path: addQueryArgs( `wc/store/v1/products`, {
							per_page: 100,
							type: 'variation',
							parent: product,
							page,
						} ),
					} )
				);
			}

			// Combine first page with remaining pages
			return Promise.all( [ firstPagePromise, ...remainingRequests ] );
		} )
		.then( ( data ) => [
			...new Map(
				data.flatMap( ( variations ) =>
					variations.map( ( item ) => [ item.id, item ] )
				)
			).values(),
		] );
};

/**
 * Given a page object and an array of page, format the title.
 *
 * @param {Object} page           Page object.
 * @param {Object} page.title     Page title object.
 * @param {string} page.title.raw Page title.
 * @param {string} page.slug      Page slug.
 * @param {Array}  pages          Array of all pages.
 * @return {string}                Formatted page title to display.
 */
export const formatTitle = ( page, pages ) => {
	if ( ! page.title.raw ) {
		return page.slug;
	}
	const isUnique =
		pages.filter( ( p ) => p.title.raw === page.title.raw ).length === 1;
	return page.title.raw + ( ! isUnique ? ` - ${ page.slug }` : '' );
};
