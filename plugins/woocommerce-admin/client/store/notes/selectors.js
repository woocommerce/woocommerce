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
 * Returns notes for a specific query.
 *
 * @param {Object} state Current state
 * @param {Object} query Note query parameters
 * @return {Array} Notes
 */
function getNotes( state, query = {} ) {
	return get( state, [ 'notes' ], getJsonString( query ), [] );
}

export default {
	getNotes,

	/**
	 * Returns true if a query is pending.
	 *
	 * @param  {Object} state   Current state
	 * @return {Boolean}        True if the `getNotes` request is pending, false otherwise
	 */
	isGetNotesRequesting( state, ...args ) {
		return select( 'core/data' ).isResolving( 'wc-admin', 'getNotes', args );
	},

	/**
	 * Returns true if a get notes request has returned an error.
	 *
	 * @param  {Object} state     Current state
	 * @param  {Object} query     Query parameters
	 * @return {Boolean}          True if the `getNotes` request has failed, false otherwise
	 */
	isGetNotesError( state, query ) {
		return ERROR === getNotes( state, query );
	},
};
