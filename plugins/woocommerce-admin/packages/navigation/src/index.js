/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { parse } from 'qs';
import { pick, uniq } from 'lodash';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { getHistory } from './history';
import * as navUtils from './index';
// For the above, import the module into itself. Functions consumed from this import can be mocked in tests.

// Expose history so all uses get the same history object.
export { getHistory };

// Export all filter utilities
export * from './filters';

/**
 * Get the current path from history.
 *
 * @return {string}  Current path.
 */
export const getPath = () => getHistory().location.pathname;

/**
 * Gets query parameters that should persist between screens or updates
 * to reports, such as filtering.
 *
 * @param {Object} query Query containing the parameters.
 * @return {Object} Object containing the persisted queries.
 */
export const getPersistedQuery = ( query = navUtils.getQuery() ) => {
	const params = applyFilters( 'woocommerce_admin_persisted_queries', [
		'period',
		'compare',
		'before',
		'after',
		'interval',
		'type',
	] );
	return pick( query, params );
};

/**
 * Get an array of IDs from a comma-separated query parameter.
 *
 * @param {string} queryString string value extracted from URL.
 * @return {Array} List of IDs converted to numbers.
 */
export function getIdsFromQuery( queryString = '' ) {
	return uniq(
		queryString
			.split( ',' )
			.map( ( id ) => parseInt( id, 10 ) )
			.filter( Boolean )
	);
}

/**
 * Get an array of searched words given a query.
 *
 * @param {Object} query Query object.
 * @return {Array} List of search words.
 */
export function getSearchWords( query = navUtils.getQuery() ) {
	if ( typeof query !== 'object' ) {
		throw new Error(
			'Invalid parameter passed to getSearchWords, it expects an object or no parameters.'
		);
	}
	const { search } = query;
	if ( ! search ) {
		return [];
	}
	if ( typeof search !== 'string' ) {
		throw new Error(
			"Invalid 'search' type. getSearchWords expects query's 'search' property to be a string."
		);
	}
	return search
		.split( ',' )
		.map( ( searchWord ) => searchWord.replace( '%2C', ',' ) );
}

/**
 * Return a URL with set query parameters.
 *
 * @param {Object} query object of params to be updated.
 * @param {string} path Relative path (defaults to current path).
 * @param {Object} currentQuery object of current query params (defaults to current querystring).
 * @return {string}  Updated URL merging query params into existing params.
 */
export function getNewPath(
	query,
	path = getPath(),
	currentQuery = getQuery()
) {
	const args = { page: 'wc-admin', ...currentQuery, ...query };
	if ( path !== '/' ) {
		args.path = path;
	}
	return addQueryArgs( 'admin.php', args );
}

/**
 * Get the current query string, parsed into an object, from history.
 *
 * @return {Object}  Current query object, defaults to empty object.
 */
export function getQuery() {
	const search = getHistory().location.search;
	if ( search.length ) {
		return parse( search.substring( 1 ) ) || {};
	}
	return {};
}

/**
 * This function returns an event handler for the given `param`
 *
 * @param {string} param The parameter in the querystring which should be updated (ex `page`, `per_page`)
 * @param {string} path Relative path (defaults to current path).
 * @param {string} query object of current query params (defaults to current querystring).
 * @return {Function} A callback which will update `param` to the passed value when called.
 */
export function onQueryChange( param, path = getPath(), query = getQuery() ) {
	switch ( param ) {
		case 'sort':
			return ( key, dir ) =>
				updateQueryString( { orderby: key, order: dir }, path, query );
		case 'compare':
			return ( key, queryParam, ids ) =>
				updateQueryString(
					{
						[ queryParam ]: `compare-${ key }`,
						[ key ]: ids,
						search: undefined,
					},
					path,
					query
				);
		default:
			return ( value ) =>
				updateQueryString( { [ param ]: value }, path, query );
	}
}

/**
 * Updates the query parameters of the current page.
 *
 * @param {Object} query object of params to be updated.
 * @param {string} path Relative path (defaults to current path).
 * @param {Object} currentQuery object of current query params (defaults to current querystring).
 */
export function updateQueryString(
	query,
	path = getPath(),
	currentQuery = getQuery()
) {
	const newPath = getNewPath( query, path, currentQuery );
	getHistory().push( newPath );
}
