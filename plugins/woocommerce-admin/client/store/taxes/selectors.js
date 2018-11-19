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
 * Returns taxes for a specific query.
 *
 * @param  {Object} state     Current state
 * @param  {Object} query     Report query parameters
 * @return {Array}            Report details
 */
function getTaxes( state, query = {} ) {
	return get( state, [ 'taxes', getJsonString( query ) ], [] );
}

export default {
	getTaxes,

	/**
	 * Returns true if a getTaxes request is pending.
	 *
	 * @param  {Object} state   Current state
	 * @return {Boolean}        True if the `getTaxes` request is pending, false otherwise
	 */
	isGetTaxesRequesting( state, ...args ) {
		return select( 'core/data' ).isResolving( 'wc-admin', 'getTaxes', args );
	},

	/**
	 * Returns true if a getTaxes request has returned an error.
	 *
	 * @param  {Object} state     Current state
	 * @param  {Object} query     Query parameters
	 * @return {Boolean}          True if the `getTaxes` request has failed, false otherwise
	 */
	isGetTaxesError( state, query ) {
		return ERROR === getTaxes( state, query );
	},
};
