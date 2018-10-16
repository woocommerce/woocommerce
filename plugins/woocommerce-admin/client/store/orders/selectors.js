/** @format */

/**
 * External dependencies
 */
import { get } from 'lodash';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getJsonString } from 'store/utils';
import { ERROR } from 'store/constants';

/**
 * Returns orders for a specific query.
 *
 * @param  {Object} state     Current state
 * @param  {Object} query     Report query paremters
 * @return {Array}            Report details
 */
function getOrders( state, query = {} ) {
	return get( state, [ 'orders', getJsonString( query ) ], [] );
}

export default {
	getOrders,

	/**
	 * Returns true if a query is pending.
	 *
	 * @param  {Object} state   Current state
	 * @return {Boolean}        True if the `getOrders` request is pending, false otherwise
	 */
	isGetOrdersRequesting( state, ...args ) {
		return select( 'core/data' ).isResolving( 'wc-admin', 'getOrders', args );
	},

	/**
	 * Returns true if a get orders request has returned an error.
	 *
	 * @param  {Object} state     Current state
	 * @param  {Object} query     Query parameters
	 * @return {Boolean}          True if the `getOrders` request has failed, false otherwise
	 */
	isGetOrdersError( state, query ) {
		return ERROR === getOrders( state, query );
	},
};
