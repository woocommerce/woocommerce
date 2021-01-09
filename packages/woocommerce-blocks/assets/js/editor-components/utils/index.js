/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { flatten, uniqBy } from 'lodash';
import { IS_LARGE_CATALOG, LIMIT_TAGS } from '@woocommerce/block-settings';

/**
 * Get product query requests for the Store API.
 *
 * @param {Object} request A query object with the list of selected products and search term.
 * @param {Array} request.selected Currently selected products.
 * @param {string} request.search Search string.
 * @param {Array} request.queryArgs Query args to pass in.
 */
const getProductsRequests = ( {
	selected = [],
	search = '',
	queryArgs = [],
} ) => {
	const defaultArgs = {
		per_page: IS_LARGE_CATALOG ? 100 : 0,
		catalog_visibility: 'any',
		search,
		orderby: 'title',
		order: 'asc',
	};
	const requests = [
		addQueryArgs( '/wc/store/products', { ...defaultArgs, ...queryArgs } ),
	];

	// If we have a large catalog, we might not get all selected products in the first page.
	if ( IS_LARGE_CATALOG && selected.length ) {
		requests.push(
			addQueryArgs( '/wc/store/products', {
				catalog_visibility: 'any',
				include: selected,
			} )
		);
	}

	return requests;
};

/**
 * Get a promise that resolves to a list of products from the Store API.
 *
 * @param {Object} request A query object with the list of selected products and search term.
 * @param {Array} request.selected Currently selected products.
 * @param {string} request.search Search string.
 * @param {Array} request.queryArgs Query args to pass in.
 */
export const getProducts = ( {
	selected = [],
	search = '',
	queryArgs = [],
} ) => {
	const requests = getProductsRequests( { selected, search, queryArgs } );

	return Promise.all( requests.map( ( path ) => apiFetch( { path } ) ) )
		.then( ( data ) => {
			const products = uniqBy( flatten( data ), 'id' );
			const list = products.map( ( product ) => ( {
				...product,
				parent: 0,
			} ) );
			return list;
		} )
		.catch( ( e ) => {
			throw e;
		} );
};

/**
 * Get a promise that resolves to a product object from the Store API.
 *
 * @param {number} productId Id of the product to retrieve.
 */
export const getProduct = ( productId ) => {
	return apiFetch( {
		path: `/wc/store/products/${ productId }`,
	} );
};

/**
 * Get a promise that resolves to a list of attribute objects from the Store API.
 */
export const getAttributes = () => {
	return apiFetch( {
		path: `wc/store/products/attributes`,
	} );
};

/**
 * Get a promise that resolves to a list of attribute term objects from the Store API.
 *
 * @param {number} attribute Id of the attribute to retrieve terms for.
 */
export const getTerms = ( attribute ) => {
	return apiFetch( {
		path: `wc/store/products/attributes/${ attribute }/terms`,
	} );
};

/**
 * Get product tag query requests for the Store API.
 *
 * @param {Object} request A query object with the list of selected products and search term.
 * @param {Array} request.selected Currently selected tags.
 * @param {string} request.search Search string.
 */
const getProductTagsRequests = ( { selected = [], search } ) => {
	const requests = [
		addQueryArgs( `wc/store/products/tags`, {
			per_page: LIMIT_TAGS ? 100 : 0,
			orderby: LIMIT_TAGS ? 'count' : 'name',
			order: LIMIT_TAGS ? 'desc' : 'asc',
			search,
		} ),
	];

	// If we have a large catalog, we might not get all selected products in the first page.
	if ( LIMIT_TAGS && selected.length ) {
		requests.push(
			addQueryArgs( `wc/store/products/tags`, {
				include: selected,
			} )
		);
	}

	return requests;
};

/**
 * Get a promise that resolves to a list of tags from the Store API.
 *
 * @param {Object} props A query object with the list of selected products and search term.
 * @param {Array} props.selected
 * @param {string} props.search
 */
export const getProductTags = ( { selected = [], search } ) => {
	const requests = getProductTagsRequests( { selected, search } );

	return Promise.all( requests.map( ( path ) => apiFetch( { path } ) ) ).then(
		( data ) => {
			return uniqBy( flatten( data ), 'id' );
		}
	);
};

/**
 * Get a promise that resolves to a list of category objects from the Store API.
 *
 * @param {Object} queryArgs Query args to pass in.
 */
export const getCategories = ( queryArgs ) => {
	return apiFetch( {
		path: addQueryArgs( `wc/store/products/categories`, {
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
		path: `wc/store/products/categories/${ categoryId }`,
	} );
};

/**
 * Get a promise that resolves to a list of variation objects from the Store API.
 *
 * @param {number} product Product ID.
 */
export const getProductVariations = ( product ) => {
	return apiFetch( {
		path: addQueryArgs( `wc/store/products`, {
			per_page: 0,
			type: 'variation',
			parent: product,
		} ),
	} );
};

/**
 * Given a page object and an array of page, format the title.
 *
 * @param  {Object} page           Page object.
 * @param  {Object} page.title     Page title object.
 * @param  {string} page.title.raw Page title.
 * @param  {string} page.slug      Page slug.
 * @param  {Array}  pages          Array of all pages.
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
