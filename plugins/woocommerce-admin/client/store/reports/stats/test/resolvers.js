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

const { getReportStats } = resolvers;

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( 'getReportStats', () => {
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
			items_sold: 5,
			gross_revenue: 999.99,
		},
		intervals: [
			{
				interval: 'week',
				subtotals: {},
			},
		],
	};

	beforeAll( () => {
		apiFetch.mockImplementation( options => {
			if ( options.path === '/wc/v3/reports/revenue/stats' ) {
				return Promise.resolve( REPORT_1 );
			}
			if ( options.path === '/wc/v3/reports/products/stats?interval=week' ) {
				return Promise.resolve( REPORT_2 );
			}
		} );
	} );

	it( 'returns requested report data', async () => {
		getReportStats( 'revenue' ).then( data => expect( data ).toEqual( REPORT_1 ) );
	} );

	it( 'returns requested report data for a specific query', async () => {
		getReportStats( 'products', { interval: 'week' } ).then( data =>
			expect( data ).toEqual( REPORT_2 )
		);
	} );
} );
