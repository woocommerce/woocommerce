/*
* @format
*/

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import resolvers from '../resolvers';

const { getReportRevenueStats } = resolvers;

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( 'getReportRevenueStats', () => {
	const REPORT_1 = {
		totals: {
			orders_count: 10,
			num_items_sold: 9,
		},
		interval: [ 0, 1, 2 ],
	};

	const REPORT_2 = {
		totals: {
			orders_count: 5,
			num_items_sold: 5,
		},
		interval: [ 0, 1, 2, 3 ],
	};

	beforeAll( () => {
		apiFetch.mockImplementation( options => {
			if ( options.path === '/wc/v3/reports/revenue/stats' ) {
				return Promise.resolve( REPORT_1 );
			}
			if ( options.path === '/wc/v3/reports/revenue/stats?interval=week' ) {
				return Promise.resolve( REPORT_2 );
			}
		} );
	} );

	it( 'returns requested report data', async () => {
		getReportRevenueStats().then( data => expect( data ).toEqual( REPORT_1 ) );
	} );

	it( 'returns requested report data for a specific query', async () => {
		getReportRevenueStats( { interval: 'week' } ).then( data =>
			expect( data ).toEqual( REPORT_2 )
		);
	} );
} );
