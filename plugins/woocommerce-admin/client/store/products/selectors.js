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
 * Returns products report details for a specific report query.
 *
 * @param  {Object} state  Current state
 * @param  {Object} query  Report query parameters
 * @return {Object}        Report details
 */
function getProducts( state, query = {} ) {
	return get( state, [ 'products', getJsonString( query ) ], [] );
}

export default {
	getProducts,

	/**
	 * Returns true if a getProducts request is pending.
	 *
	 * @param  {Object} state  Current state
	 * @return {Boolean}        True if the `getProducts` request is pending, false otherwise
	 */
	isGetProductsRequesting( state, ...args ) {
		return select( 'core/data' ).isResolving( 'wc-admin', 'getProducts', args );
	},

	/**
	 * Returns true if a getProducts request has returned an error.
	 *
	 * @param  {Object} state  Current state
	 * @param  {Object} query  Report query parameters
	 * @return {Boolean}        True if the `getProducts` request has failed, false otherwise
	 */
	isGetProductsError( state, query ) {
		return ERROR === getProducts( state, query );
	},
};
