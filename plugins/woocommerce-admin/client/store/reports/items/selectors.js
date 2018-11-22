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
 * Returns report items for a specific endpoint query.
 *
 * @param  {Object} state     Current state
 * @param  {String} endpoint  Stats endpoint
 * @param  {Object} query     Report query parameters
 * @return {Object}           Report details
 */
function getReportItems( state, endpoint, query = {} ) {
	return get( state, [ 'reports', 'items', endpoint, getJsonString( query ) ], [] );
}

export default {
	getReportItems,

	/**
	 * Returns true if a getReportItems request is pending.
	 *
	 * @param  {Object} state   Current state
	 * @return {Boolean}        True if the `getReportItems` request is pending, false otherwise
	 */
	isGetReportItemsRequesting( state, ...args ) {
		return select( 'core/data' ).isResolving( 'wc-admin', 'getReportItems', args );
	},

	/**
	 * Returns true if a getReportItems request has returned an error.
	 *
	 * @param  {Object} state     Current state
	 * @param  {String} endpoint  Items endpoint
	 * @param  {Object} query     Report query parameters
	 * @return {Boolean}          True if the `getReportItems` request has failed, false otherwise
	 */
	isGetReportItemsError( state, endpoint, query ) {
		return ERROR === getReportItems( state, endpoint, query );
	},
};
