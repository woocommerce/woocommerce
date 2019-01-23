/*
* @format
*/

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import resolvers from '../resolvers';

const { getReportStats } = resolvers;

jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn().mockReturnValue( {
		setReportStats: jest.fn(),
	} ),
} ) );
jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( 'getReportStats', () => {
	const REPORT_1 = {
		totals: {
			orders_count: 10,
			num_items_sold: 9,
		},
		interval: [ 0, 1, 2 ],
	};
	const REPORT_1_TOTALS = {
		'x-wp-total': 10,
		'x-wp-totalpages': 2,
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
	const REPORT_2_TOTALS = {
		'x-wp-total': 20,
		'x-wp-totalpages': 4,
	};

	beforeAll( () => {
		apiFetch.mockImplementation( options => {
			if ( options.path === '/wc/v4/reports/revenue/stats' ) {
				return Promise.resolve( {
					headers: {
						get: header => REPORT_1_TOTALS[ header ],
					},
					json: () => Promise.resolve( REPORT_1 ),
				} );
			}
			if ( options.path === '/wc/v4/reports/products/stats?interval=week' ) {
				return Promise.resolve( {
					headers: {
						get: header => REPORT_2_TOTALS[ header ],
					},
					json: () => Promise.resolve( REPORT_2 ),
				} );
			}
		} );
	} );

	it( 'returns requested report data', async () => {
		expect.assertions( 1 );
		const endpoint = 'revenue';

		await getReportStats( endpoint, undefined );

		expect( dispatch().setReportStats ).toHaveBeenCalledWith(
			endpoint,
			REPORT_1,
			undefined,
			REPORT_1_TOTALS[ 'x-wp-total' ],
			REPORT_1_TOTALS[ 'x-wp-totalpages' ]
		);
	} );

	it( 'returns requested report data for a specific query', async () => {
		expect.assertions( 1 );
		const endpoint = 'products';
		const query = { interval: 'week' };

		await getReportStats( endpoint, query );

		expect( dispatch().setReportStats ).toHaveBeenCalledWith(
			endpoint,
			REPORT_2,
			query,
			REPORT_2_TOTALS[ 'x-wp-total' ],
			REPORT_2_TOTALS[ 'x-wp-totalpages' ]
		);
	} );
} );
