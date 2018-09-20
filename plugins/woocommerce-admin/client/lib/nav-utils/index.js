/** @format */
/**
 * External dependencies
 */
import history from 'lib/history';
import { parse, stringify } from 'qs';
import { uniq } from 'lodash';

/**
 * Returns a string with the site's wp-admin URL appended. JS version of `admin_url`.
 *
 * @param {String} path Relative path.
 * @return {String} Full admin URL.
 */
export const getAdminLink = path => wcSettings.adminUrl + path;

/**
 * Get the current path from history.
 *
 * @return {String}  Current path.
 */
export const getPath = () => history.location.pathname;

/**
 * Converts a query object to a query string.
 *
 * @param {Object} query parameters to be converted.
 * @return {String} Query string.
 */
export const stringifyQuery = query => ( query ? '?' + stringify( query ) : '' );

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
			.map( id => parseInt( id, 10 ) )
			.filter( Boolean )
	);
}

/**
 * Return a URL with set query parameters.
 *
 * @param {Object} query object of params to be updated.
 * @param {String} path Relative path (defaults to current path).
 * @param {Object} currentQuery object of current query params (defaults to current querystring).
 * @return {String}  Updated URL merging query params into existing params.
 */
export function getNewPath( query, path = getPath(), currentQuery = getQuery() ) {
	const queryString = stringifyQuery( { ...currentQuery, ...query } );
	return `${ path }${ queryString }`;
}

/**
 * Get the current query string, parsed into an object, from history.
 *
 * @return {Object}  Current query object, defaults to empty object.
 */
export function getQuery() {
	const search = history.location.search;
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
 * @return {function} A callback which will update `param` to the passed value when called.
 */
export function onQueryChange( param, path = getPath(), query = getQuery() ) {
	switch ( param ) {
		case 'sort':
			return ( key, dir ) => updateQueryString( { orderby: key, order: dir }, path, query );
		case 'compare':
			return ( key, ids ) =>
				updateQueryString( { filter: `compare-${ key }`, [ key ]: ids }, path, query );
		default:
			return value => updateQueryString( { [ param ]: value }, path, query );
	}
}

/**
 * Updates the query parameters of the current page.
 *
 * @param {Object} query object of params to be updated.
 * @param {String} path Relative path (defaults to current path).
 * @param {Object} currentQuery object of current query params (defaults to current querystring).
 */
export function updateQueryString( query, path = getPath(), currentQuery = getQuery() ) {
	const newPath = getNewPath( query, path, currentQuery );
	history.push( newPath );
}
