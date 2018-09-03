/** @format */

/**
 * External dependencies
 */
import { get } from 'lodash';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';
import { getJsonString } from 'store/utils';

/**
 * Returns revenue report details for a specific report query.
 *
 * @param  {Object} state  Current state
 * @param  {Object} query  Report query paremters
 * @return {Object}        Report details
 */
function getProducts( state, query = {} ) {
	const queries = get( state, 'products.queries', {} );
	return queries[ getJsonString( query ) ];
}

export default {
	getProducts,

	/**
	 * Returns true if a products request is pending.
	 *
	 * @param  {Object} state  Current state
	 * @return {Object}        True if the `getProducts` request is pending, false otherwise
	 */
	isProductsRequesting( state, ...args ) {
		return select( 'core/data' ).isResolving( 'wc-admin', 'getProducts', args );
	},

	/**
	 * Returns true if a products request has returned an error.
	 *
	 * @param  {Object} state  Current state
	 * @param  {Object} query  Report query paremters
	 * @return {Object}        True if the `getProducts` request has failed, false otherwise
	 */
	isProductsError( state, query ) {
		return ERROR === getProducts( state, query );
	},
};
