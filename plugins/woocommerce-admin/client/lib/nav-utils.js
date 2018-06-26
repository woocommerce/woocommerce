/** @format */

/**
 * External dependencies
 */
import history from './history';
import { parse, stringify as stringifyQueryObject } from 'qs';

/* Returns a string with the site's wp-admin URL appended. JS version of `admin_url`.
 *
 * @param {String} path Relative path.
 * @return {String} Full admin URL.
 */
export const getAdminLink = path => {
	return wcSettings.adminUrl + path;
};

/* Updates the query parameters of the current page.
 *
 * @param {Object} Query parameters to be updated.
 */
export const updateQueryString = query => {
	const path = history.location.pathname;
	const currentQuery = parse( history.location.search.substring( 1 ) );
	const queryString = stringifyQueryObject( Object.assign( currentQuery, query ) );
	history.push( `${ path }?${ queryString }` );
};
