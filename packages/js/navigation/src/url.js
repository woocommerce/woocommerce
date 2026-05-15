/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { parse } from 'qs';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { getHistory } from './history';

/**
 * Get the current path from history.
 *
 * @return {string}  Current path.
 */
export const getPath = () => getHistory().location.pathname;

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
 * Resolve a relative path (starting with `./` or `../`) against a base
 * path. Absolute paths and paths without a leading relative segment are
 * returned unchanged.
 *
 * @param {string} path Path to resolve.
 * @param {string} base Base path to resolve against.
 * @return {string} Resolved path.
 */
function resolveRelativePath( path, base ) {
	if (
		typeof path !== 'string' ||
		( ! path.startsWith( './' ) &&
			! path.startsWith( '../' ) &&
			path !== '.' &&
			path !== '..' )
	) {
		return path;
	}

	// Determine the base segments. The current path coming from history is
	// a pathname like `/automatewoo/analytics`, but the value tracked in the
	// `path` query arg may also be the bare path (no leading slash). Treat
	// the base as a directory so `./foo` joins onto it instead of replacing
	// its last segment.
	const baseSegments = ( base || '' ).split( '/' ).filter( Boolean );
	const pathSegments = path.split( '/' );

	for ( const segment of pathSegments ) {
		if ( segment === '' || segment === '.' ) {
			continue;
		}
		if ( segment === '..' ) {
			baseSegments.pop();
			continue;
		}
		baseSegments.push( segment );
	}

	return '/' + baseSegments.join( '/' );
}

/**
 * Return a URL with set query parameters.
 *
 * @param {Object} query        object of params to be updated.
 * @param {string} path         Relative path (defaults to current path). Paths
 *                              starting with `./` or `../` are resolved against
 *                              the current path.
 * @param {Object} currentQuery object of current query params (defaults to current querystring).
 * @param {string} page         Page key (defaults to "wc-admin")
 * @return {string}  Updated URL merging query params into existing params.
 */
export function getNewPath(
	query,
	path = getPath(),
	currentQuery = getQuery(),
	page = 'wc-admin'
) {
	const resolvedPath = resolveRelativePath( path, getPath() );
	const args = { page, ...currentQuery, ...query };
	if ( resolvedPath !== '/' ) {
		args.path = resolvedPath;
	}
	return addQueryArgs( 'admin.php', args );
}

/**
 * Returns a parsed object for an absolute or relative admin URL.
 *
 * @param {*} url - the url to test.
 * @return {URL} - the URL object of the given url.
 */
export const parseAdminUrl = ( url ) => {
	if ( url.startsWith( 'http' ) ) {
		return new URL( url );
	}

	return /^\/?[a-z0-9]+.php/i.test( url )
		? new URL( `${ window.wcSettings.adminUrl }${ url }` )
		: new URL( getAdminLink( getNewPath( {}, url, {} ) ) );
};
