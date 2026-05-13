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
 * Return a URL with set query parameters.
 *
 * @param {Object} query        object of params to be updated.
 * @param {string} path         Relative path (defaults to current path).
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
	const args = { page, ...currentQuery, ...query };
	if ( path !== '/' ) {
		args.path = path;
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
