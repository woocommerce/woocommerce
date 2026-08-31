/**
 * Internal dependencies
 */
import { getReportChartData } from '../utils';

describe( 'getReportChartData()', () => {
	it( 'returns updated data when a report is refetched for the same query', () => {
		let stats = {
			data: { totals: { customers: 1 } },
			totalResults: 1,
		};
		const selector = {
			getReportStats: jest.fn( () => stats ),
			getReportStatsError: jest.fn( () => undefined ),
			isResolving: jest.fn( () => false ),
		};
		const options = {
			endpoint: 'customers' as const,
			dataType: 'primary' as const,
			query: {},
			limitBy: [],
			filters: [],
			advancedFilters: {},
			defaultDateRange: 'period=month&compare=previous_year',
			tableQuery: {},
			fields: [],
			selector: selector as never,
			select: undefined as never,
		};

		const firstResponse = getReportChartData( options );
		stats = {
			data: { totals: { customers: 2 } },
			totalResults: 1,
		};
		const secondResponse = getReportChartData( options );

		expect( firstResponse.data.totals ).toEqual( { customers: 1 } );
		expect( secondResponse.data.totals ).toEqual( { customers: 2 } );
	} );

	it( 'reuses chart data when paged report responses are unchanged', () => {
		const responses = [
			{
				data: {
					totals: { orders_count: 231 },
					intervals: [ { interval: 'page-1' } ],
				},
				totalResults: 231,
			},
			{ data: { intervals: [ { interval: 'page-2' } ] } },
			{ data: { intervals: [ { interval: 'page-3' } ] } },
		];
		const selector = {
			getReportStats: jest.fn(
				( _endpoint, query ) => responses[ ( query.page || 1 ) - 1 ]
			),
			getReportStatsError: jest.fn( () => undefined ),
			isResolving: jest.fn( () => false ),
		};
		const options = {
			endpoint: 'orders' as const,
			dataType: 'primary' as const,
			query: {},
			limitBy: [],
			filters: [],
			advancedFilters: {},
			defaultDateRange: 'period=year&compare=previous_year',
			tableQuery: {},
			fields: [],
			selector: selector as never,
			select: undefined as never,
		};

		const firstResponse = getReportChartData( options );
		const secondResponse = getReportChartData( options );

		expect( secondResponse ).toBe( firstResponse );
		expect( secondResponse.data.intervals ).toEqual( [
			{ interval: 'page-1' },
			{ interval: 'page-2' },
			{ interval: 'page-3' },
		] );

		responses[ 1 ] = {
			data: { intervals: [ { interval: 'updated-page-2' } ] },
		};
		const updatedResponse = getReportChartData( options );

		expect( updatedResponse ).not.toBe( firstResponse );
		expect( updatedResponse.data.intervals[ 1 ] ).toEqual( {
			interval: 'updated-page-2',
		} );
	} );
} );
