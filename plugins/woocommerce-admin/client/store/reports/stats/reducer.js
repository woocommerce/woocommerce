/** @format */

/**
 * External dependencies
 */
import { merge } from 'lodash';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';
import { getJsonString } from 'store/util';

const DEFAULT_STATE = {};

export default function reportStatsReducer( state = DEFAULT_STATE, action ) {
	if ( 'SET_REPORT_STATS' === action.type ) {
		const queryKey = getJsonString( action.query );
		return merge( {}, state, {
			[ action.endpoint ]: {
				[ queryKey ]: action.report,
			},
		} );
	}

	if ( 'SET_REPORT_STATS_ERROR' === action.type ) {
		const queryKey = getJsonString( action.query );
		return merge( {}, state, {
			[ action.endpoint ]: {
				[ queryKey ]: ERROR,
			},
		} );
	}

	return state;
}
