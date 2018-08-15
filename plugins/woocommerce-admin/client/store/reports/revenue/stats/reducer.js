/** @format */

/**
 * External dependencies
 */
import { get } from 'lodash';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';

const DEFAULT_STATE = {
	queries: {},
};

export default function reportRevenueStatsReducer( state = DEFAULT_STATE, action ) {
	if ( 'SET_REPORT_REVENUE_STATS' === action.type ) {
		const prevQueries = get( state, 'queries', {} );
		const query = JSON.stringify( action.query, Object.keys( action.query ).sort() );
		const queries = {
			...prevQueries,
			[ query ]: {
				...action.report,
			},
		};

		return {
			...state,
			queries,
		};
	}

	if ( 'SET_REPORT_REVENUE_STATS_ERROR' === action.type ) {
		const prevQueries = get( state, 'queries', {} );
		const query = JSON.stringify( action.query, Object.keys( action.query ).sort() );
		const queries = {
			...prevQueries,
			[ query ]: ERROR,
		};
		return {
			...state,
			queries,
		};
	}

	return state;
}
