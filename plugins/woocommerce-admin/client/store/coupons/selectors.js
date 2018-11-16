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
 * Returns coupons for a specific query.
 *
 * @param  {Object} state     Current state
 * @param  {Object} query     Report query parameters
 * @return {Array}            Report details
 */
function getCoupons( state, query = {} ) {
	return get( state, [ 'coupons', getJsonString( query ) ], [] );
}

export default {
	getCoupons,

	/**
	 * Returns true if a getCoupons request is pending.
	 *
	 * @param  {Object} state   Current state
	 * @return {Boolean}        True if the `getCoupons` request is pending, false otherwise
	 */
	isGetCouponsRequesting( state, ...args ) {
		return select( 'core/data' ).isResolving( 'wc-admin', 'getCoupons', args );
	},

	/**
	 * Returns true if a getCoupons request has returned an error.
	 *
	 * @param  {Object} state     Current state
	 * @param  {Object} query     Query parameters
	 * @return {Boolean}          True if the `getCoupons` request has failed, false otherwise
	 */
	isGetCouponsError( state, query ) {
		return ERROR === getCoupons( state, query );
	},
};
