/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { flatten, uniqBy } from 'lodash';
import {
	ENDPOINTS,
	IS_LARGE_CATALOG,
	LIMIT_TAGS,
} from '@woocommerce/block-settings';

const getProductsRequests = ( {
	selected = [],
	search = '',
	queryArgs = [],
} ) => {
	const defaultArgs = {
		per_page: IS_LARGE_CATALOG ? 100 : -1,
		catalog_visibility: 'any',
		status: 'publish',
		search,
		orderby: 'title',
		order: 'asc',
	};
	const requests = [
		addQueryArgs( ENDPOINTS.products, { ...defaultArgs, ...queryArgs } ),
	];

	// If we have a large catalog, we might not get all selected products in the first page.
	if ( IS_LARGE_CATALOG && selected.length ) {
		requests.push(
			addQueryArgs( ENDPOINTS.products, {
				catalog_visibility: 'any',
				status: 'publish',
				include: selected,
			} )
		);
	}

	return requests;
};

/**
 * Get a promise that resolves to a list of products from the API.
 *
 * @param {Object} - A query object with the list of selected products and search term.
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
 * Get a promise that resolves to a product object from the API.
 *
 * @param {number} productId Id of the product to retrieve.
 */
export const getProduct = ( productId ) => {
	return apiFetch( {
		path: `${ ENDPOINTS.products }/${ productId }`,
	} );
};

const getProductTagsRequests = ( { selected = [], search } ) => {
	const requests = [
		addQueryArgs( `${ ENDPOINTS.products }/tags`, {
			per_page: LIMIT_TAGS ? 100 : -1,
			orderby: LIMIT_TAGS ? 'count' : 'name',
			order: LIMIT_TAGS ? 'desc' : 'asc',
			search,
		} ),
	];

	// If we have a large catalog, we might not get all selected products in the first page.
	if ( LIMIT_TAGS && selected.length ) {
		requests.push(
			addQueryArgs( `${ ENDPOINTS.products }/tags`, {
				include: selected,
			} )
		);
	}

	return requests;
};

/**
 * Get a promise that resolves to a list of tags from the API.
 *
 * @param {Object} - A query object with the list of selected products and search term.
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
 * Get a promise that resolves to a category object from the API.
 *
 * @param {number} categoryId Id of the product to retrieve.
 */
export const getCategory = ( categoryId ) => {
	return apiFetch( {
		path: `${ ENDPOINTS.categories }/${ categoryId }`,
	} );
};

/**
 * Get a promise that resolves to an array of category objects from the API.
 */
export const getCategories = ( queryArgs ) => {
	return apiFetch( {
		path: addQueryArgs( `${ ENDPOINTS.products }/categories`, {
			per_page: -1,
			...queryArgs,
		} ),
	} );
};

export const getProductVariations = ( product ) => {
	return apiFetch( {
		path: addQueryArgs( `${ ENDPOINTS.products }/${ product }/variations`, {
			per_page: -1,
		} ),
	} );
};

export const getAttributes = () => {
	return apiFetch( {
		path: addQueryArgs( `${ ENDPOINTS.products }/attributes`, {
			per_page: -1,
		} ),
	} );
};

export const getTerms = ( attribute ) => {
	return apiFetch( {
		path: addQueryArgs(
			`${ ENDPOINTS.products }/attributes/${ attribute }/terms`,
			{
				per_page: -1,
			}
		),
	} );
};
