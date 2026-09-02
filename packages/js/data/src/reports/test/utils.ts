/**
 * Internal dependencies
 */
import { getFilterQuery, getReportChartData, getRequestQuery } from '../utils';

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

type QueryOptions = Parameters< typeof getRequestQuery >[ 0 ];

const fields = [ 'orders_count', 'items_sold' ];

const productFilter = {
	param: 'filter',
	filters: [
		{ value: 'all' },
		{
			value: 'select_product',
			subFilters: [
				{
					value: 'single_product',
					settings: { param: 'products' },
				},
			],
		},
		{ value: 'advanced' },
	],
};

const variationFilter = {
	param: 'filter-variations',
	filters: [
		{ value: 'all' },
		{
			value: 'select_variation',
			subFilters: [
				{
					value: 'single_variation',
					settings: { param: 'variations' },
				},
			],
		},
	],
};

const advancedFilters = {
	filters: {
		product: {
			rules: [ { value: 'includes' }, { value: 'excludes' } ],
			input: { component: 'Search' },
		},
	},
};

const createOptions = ( query: Record< string, string > ): QueryOptions =>
	( {
		endpoint: 'products',
		dataType: 'primary',
		query,
		limitBy: [ 'products', 'variations' ],
		filters: [ productFilter, variationFilter ],
		advancedFilters,
		defaultDateRange: 'period=month&compare=previous_year',
		tableQuery: {},
		fields,
		selector: {} as QueryOptions[ 'selector' ],
		select: jest.fn(),
	} ) as QueryOptions;

describe( 'report request query mapping', () => {
	beforeAll( () => {
		jest.useFakeTimers();
		jest.setSystemTime( new Date( '2022-02-15T12:00:00Z' ) );
	} );

	afterAll( () => {
		jest.useRealTimers();
	} );

	it( 'maps report filter queries into REST requests', () => {
		const cases = [
			{
				name: 'last month',
				query: {
					period: 'last_month',
					compare: 'previous_year',
				},
				filterQuery: {},
				after: '2022-01-01T00:00:00',
				before: '2022-01-31T23:59:59',
			},
			{
				name: 'custom range',
				query: {
					period: 'custom',
					compare: 'previous_year',
					after: '2022-01-01',
					before: '2022-01-30',
				},
				filterQuery: {},
				after: '2022-01-01T00:00:00',
				before: '2022-01-30T23:59:59',
			},
			{
				name: 'advanced product include',
				query: {
					period: 'last_month',
					compare: 'previous_year',
					filter: 'advanced',
					product_includes: '101',
				},
				filterQuery: { match: 'all', product_includes: '101' },
				after: '2022-01-01T00:00:00',
				before: '2022-01-31T23:59:59',
			},
			{
				name: 'single product',
				query: {
					period: 'last_month',
					compare: 'previous_year',
					filter: 'single_product',
					products: '101',
				},
				filterQuery: { products: '101' },
				after: '2022-01-01T00:00:00',
				before: '2022-01-31T23:59:59',
			},
			{
				name: 'single variation',
				query: {
					period: 'last_month',
					compare: 'previous_year',
					'filter-variations': 'single_variation',
					variations: '202',
				},
				filterQuery: { variations: '202' },
				after: '2022-01-01T00:00:00',
				before: '2022-01-31T23:59:59',
			},
		];

		cases.forEach( ( testCase ) => {
			const options = createOptions( testCase.query );
			const filterQuery = getFilterQuery( options );
			const requestQuery = getRequestQuery( options );

			expect( {
				name: testCase.name,
				filterQuery,
				requestQuery,
			} ).toEqual( {
				name: testCase.name,
				filterQuery: testCase.filterQuery,
				requestQuery: {
					order: 'asc',
					interval: 'day',
					per_page: 100,
					after: testCase.after,
					before: testCase.before,
					segmentby: undefined,
					fields,
					...testCase.filterQuery,
				},
			} );
		} );
	} );
} );
