/**
 * External dependencies
 */
import { addFilter, removeFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import {
	getFilterQuery,
	getReportChartData,
	getRequestQuery,
	usesServerSideSearch,
} from '../utils';

type FilterQueryOptions = Parameters< typeof getFilterQuery >[ 0 ];

/**
 * Calls getFilterQuery with only the options a given test cares about.
 *
 * @param {Object} options Partial getFilterQuery options.
 * @return {Object} The filter query.
 */
const filterQuery = ( options: Partial< FilterQueryOptions > ) =>
	getFilterQuery( options as FilterQueryOptions );

describe( 'usesServerSideSearch', () => {
	it( 'should be true when the search resolves to products', () => {
		expect( usesServerSideSearch( [ 'products' ] ) ).toBe( true );
	} );

	it( 'should be true when another filter limits the report as well', () => {
		// The single category view searches products and limits the request to the
		// category. The endpoint intersects the two.
		expect( usesServerSideSearch( [ 'products', 'categories' ] ) ).toBe(
			true
		);
	} );

	it( 'should be false when the search resolves to something else', () => {
		// The Categories report list view searches category names, not products.
		expect( usesServerSideSearch( [ 'categories', 'products' ] ) ).toBe(
			false
		);
		expect( usesServerSideSearch( [ 'categories' ] ) ).toBe( false );
		expect( usesServerSideSearch( [ 'coupons' ] ) ).toBe( false );
	} );

	it( 'should be false when nothing limits the report', () => {
		expect( usesServerSideSearch( [] ) ).toBe( false );
	} );

	it( 'should be false when the limit properties are not a list', () => {
		const limitProperties = ( value: unknown ) => value as string[];

		expect( usesServerSideSearch( limitProperties( undefined ) ) ).toBe(
			false
		);
		expect( usesServerSideSearch( limitProperties( null ) ) ).toBe( false );
		expect( usesServerSideSearch( limitProperties( 'products' ) ) ).toBe(
			false
		);
	} );

	describe( 'woocommerce_admin_report_server_side_search_item_types', () => {
		const hook = 'woocommerce_admin_report_server_side_search_item_types';

		afterEach( () => {
			removeFilter( hook, 'test' );
		} );

		it( 'should let an integration opt an item type out', () => {
			// An integration replacing the products report route with a handler that does
			// not read `search` needs the client to resolve the term into IDs instead.
			addFilter( hook, 'test', ( itemTypes: string[] ) =>
				itemTypes.filter( ( itemType ) => itemType !== 'products' )
			);

			expect( usesServerSideSearch( [ 'products' ] ) ).toBe( false );
		} );

		it( 'should take a callback registered after the module loaded into account', () => {
			expect( usesServerSideSearch( [ 'coupons' ] ) ).toBe( false );

			addFilter( hook, 'test', ( itemTypes: string[] ) => [
				...itemTypes,
				'coupons',
			] );

			expect( usesServerSideSearch( [ 'coupons' ] ) ).toBe( true );
		} );

		it( 'should be false when a callback returns something other than a list', () => {
			addFilter( hook, 'test', () => undefined );

			expect( usesServerSideSearch( [ 'products' ] ) ).toBe( false );
		} );
	} );
} );

describe( 'getFilterQuery', () => {
	it( 'should send the search term to the products endpoint instead of a list of IDs', () => {
		expect(
			filterQuery( {
				endpoint: 'products',
				query: { search: 'widget' },
			} )
		).toEqual( { search: [ 'widget' ] } );
	} );

	it( 'should keep an active product filter alongside the search term', () => {
		// Picking a comparison or a single product does not clear the search, and the
		// endpoint intersects the two, so dropping the IDs would widen the report.
		expect(
			filterQuery( {
				endpoint: 'products',
				query: { search: 'widget', products: '1,2,3' },
			} )
		).toEqual( { search: [ 'widget' ], products: '1,2,3' } );
	} );

	it( 'should not send an empty product filter alongside the search term', () => {
		expect(
			filterQuery( {
				endpoint: 'products',
				query: { search: 'widget', products: '' },
			} )
		).toEqual( { search: [ 'widget' ] } );
	} );

	it( 'should split a comma separated search into separate terms', () => {
		expect(
			filterQuery( {
				endpoint: 'products',
				query: { search: 'widget,gadget' },
			} )
		).toEqual( { search: [ 'widget', 'gadget' ] } );
	} );

	it( 'should unescape a comma inside a single search term', () => {
		expect(
			filterQuery( {
				endpoint: 'products',
				query: { search: 'widget%2C large' },
			} )
		).toEqual( { search: [ 'widget, large' ] } );
	} );

	it( 'should send the search term alongside the other limits of a product request', () => {
		// The single category view. The endpoint intersects the term with the category,
		// instead of the client capping the search at one page of resolved IDs.
		expect(
			filterQuery( {
				endpoint: 'products',
				query: { search: 'widget', products: '1,2', categories: '5' },
				limitBy: [ 'products', 'categories' ],
			} )
		).toEqual( {
			search: [ 'widget' ],
			products: '1,2',
			categories: '5',
		} );
	} );

	it( 'should keep sending resolved IDs when the search resolves to something else', () => {
		// The Categories report list view searches category names against the products
		// endpoint, so the term is not the product search the endpoint would run.
		expect(
			filterQuery( {
				endpoint: 'products',
				query: { search: 'clothing', categories: '5,6' },
				limitBy: [ 'categories' ],
			} )
		).toEqual( { categories: '5,6' } );
	} );

	it( 'should keep sending resolved IDs for endpoints that do not resolve the search', () => {
		expect(
			filterQuery( {
				endpoint: 'coupons',
				query: { search: 'half off', coupons: '7,8' },
			} )
		).toEqual( { coupons: '7,8' } );
	} );

	it( 'should fall through to the configured filters when there is no search', () => {
		expect( filterQuery( { endpoint: 'products', query: {} } ) ).toEqual(
			{}
		);
	} );
} );

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
			const actualFilterQuery = getFilterQuery( options );
			const requestQuery = getRequestQuery( options );

			expect( {
				name: testCase.name,
				filterQuery: actualFilterQuery,
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
