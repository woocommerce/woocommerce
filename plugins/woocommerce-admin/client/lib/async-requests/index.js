/** @format */
/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { identity } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getIdsFromQuery, stringifyQuery } from '@woocommerce/navigation';

/**
 * Get a function that accepts ids as they are found in url parameter and
 * returns a promise with an optional method applied to results
 *
 * @param {string|function} path - api path string or a function of the query returning api path string
 * @param {Function} [handleData] - function applied to each iteration of data
 * @returns {Function} - a function of ids returning a promise
 */
export function getRequestByIdString( path, handleData = identity ) {
	return function( queryString = '', query ) {
		const pathString = 'function' === typeof path ? path( query ) : path;
		const idList = getIdsFromQuery( queryString );
		if ( idList.length < 1 ) {
			return Promise.resolve( [] );
		}
		const payload = stringifyQuery( {
			include: idList.join( ',' ),
			per_page: idList.length,
		} );
		return apiFetch( { path: pathString + payload } ).then( data => data.map( handleData ) );
	};
}
