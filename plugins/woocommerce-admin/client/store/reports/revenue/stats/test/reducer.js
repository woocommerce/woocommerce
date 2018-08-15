/**
 * @format
 */

/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';
import reportsRevenueStatsReducer from '../reducer';

describe( 'reportsRevenueStatsReducer()', () => {
	it( 'returns an empty data object by default', () => {
		const state = reportsRevenueStatsReducer( undefined, {} );
		expect( state ).toEqual( { queries: {} } );
	} );

	it( 'returns with received report data', () => {
		const originalState = deepFreeze( {} );
		const query = {
			after: '2018-01-04T00:00:00+00:00',
			before: '2018-07-14T00:00:00+00:00',
			interval: 'day',
		};
		const report = {
			totals: {
				orders_count: 10,
				num_items_sold: 9,
			},
			interval: [ 0, 1, 2 ],
		};

		const state = reportsRevenueStatsReducer( originalState, {
			type: 'SET_REPORT_REVENUE_STATS',
			query,
			report,
		} );

		expect( state.queries[ JSON.stringify( query ) ] ).toEqual( report );
	} );

	it( 'returns with received error data', () => {
		const originalState = deepFreeze( {} );
		const query = {
			after: '2018-01-04T00:00:00+00:00',
			before: '2018-07-14T00:00:00+00:00',
			interval: 'day',
		};

		const state = reportsRevenueStatsReducer( originalState, {
			type: 'SET_REPORT_REVENUE_STATS_ERROR',
			query,
		} );

		expect( state.queries[ JSON.stringify( query ) ] ).toEqual( ERROR );
	} );
} );
