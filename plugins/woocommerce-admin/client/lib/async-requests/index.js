/** @format */
/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { identity } from 'lodash';

/**
 * Internal dependencies
 */
import { getIdsFromQuery, stringifyQuery } from 'lib/nav-utils';

/**
 * Get a function that accepts ids as they are found in url parameter and
 * returns a promise with an optional method applied to results
 *
 * @param {string} path - api path
 * @param {Function} [handleData] - function applied to each iteration of data
 * @returns {Function} - a function of ids returning a promise
 */
export function getRequestByIdString( path, handleData = identity ) {
	return function( queryString = '' ) {
		const idList = getIdsFromQuery( queryString );
		if ( idList.length < 1 ) {
			return Promise.resolve( [] );
		}
		const payload = stringifyQuery( {
			include: idList.join( ',' ),
			per_page: idList.length,
		} );
		return apiFetch( { path: path + payload } ).then( data => data.map( handleData ) );
	};
}
