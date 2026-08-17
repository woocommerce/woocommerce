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
} );
