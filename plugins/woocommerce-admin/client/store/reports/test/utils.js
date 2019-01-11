/*
* @format
*/

/**
 * Internal dependencies
 */
import {
	isReportDataEmpty,
	getReportChartData,
	getSummaryNumbers,
	getFilterQuery,
	getReportTableData,
} from '../utils';
import * as ordersConfig from 'analytics/report/orders/config';

jest.mock( '../utils', () => ( {
	...require.requireActual( '../utils' ),
	getReportTableQuery: () => ( {
		after: '2018-10-10',
		before: '2018-10-10',
	} ),
} ) );

describe( 'isReportDataEmpty()', () => {
	it( 'returns false if report is valid', () => {
		const report = {
			data: {
				totals: {
					orders_count: 10,
					num_items_sold: 9,
				},
				intervals: [ 0, 1, 2 ],
			},
		};
		expect( isReportDataEmpty( report ) ).toEqual( false );
	} );
	it( 'returns true if report object is undefined', () => {
		expect( isReportDataEmpty( undefined ) ).toEqual( true );
	} );
	it( 'returns true if data response object is missing', () => {
		expect( isReportDataEmpty( {} ) ).toEqual( true );
	} );
	it( 'returns true if totals response object is missing', () => {
		expect( isReportDataEmpty( { data: {} } ) ).toEqual( true );
	} );
	it( 'returns true if intervals response object is empty', () => {
		expect( isReportDataEmpty( { data: { intervals: [], totals: 2 } } ) ).toEqual( true );
	} );
} );

describe( 'getReportChartData()', () => {
	const select = jest.fn().mockReturnValue( {} );
	const response = {
		isEmpty: false,
		isError: false,
		isRequesting: false,
		data: {
			totals: null,
			intervals: [],
		},
	};

	beforeAll( () => {
		select( 'wc-api' ).getReportStats = jest.fn().mockReturnValue( {} );
		select( 'wc-api' ).isReportStatsRequesting = jest.fn().mockReturnValue( false );
		select( 'wc-api' ).getReportStatsError = jest.fn().mockReturnValue( false );
	} );

	afterAll( () => {
		select( 'wc-api' ).getReportStats.mockRestore();
		select( 'wc-api' ).isReportStatsRequesting.mockRestore();
		select( 'wc-api' ).getReportStatsError.mockRestore();
	} );

	function setGetReportStats( func ) {
		select( 'wc-api' ).getReportStats.mockImplementation( ( ...args ) => func( ...args ) );
	}

	function setIsReportStatsRequesting( func ) {
		select( 'wc-api' ).isReportStatsRequesting.mockImplementation( ( ...args ) => func( ...args ) );
	}

	function setGetReportStatsError( func ) {
		select( 'wc-api' ).getReportStatsError.mockImplementation( ( ...args ) => func( ...args ) );
	}

	it( 'returns isRequesting if first request is in progress', () => {
		setIsReportStatsRequesting( () => {
			return true;
		} );
		const result = getReportChartData( 'revenue', 'primary', {}, select );
		expect( result ).toEqual( { ...response, isRequesting: true } );
	} );

	it( 'returns isError if first request errors', () => {
		setIsReportStatsRequesting( () => {
			return false;
		} );
		setGetReportStatsError( () => {
			return { error: 'Error' };
		} );
		const result = getReportChartData( 'revenue', 'primary', {}, select );
		expect( result ).toEqual( { ...response, isError: true } );
	} );

	it( 'returns results after single page of data', () => {
		const data = {
			totals: {
				orders_count: 115,
				gross_revenue: 13966.92,
			},
			intervals: [
				{
					interval: 'day',
					date_start: '2018-07-01 00:00:00',
					subtotals: {
						orders_count: 115,
						gross_revenue: 13966.92,
					},
				},
			],
		};

		setIsReportStatsRequesting( () => {
			return false;
		} );
		setGetReportStatsError( () => {
			return undefined;
		} );
		setGetReportStats( () => {
			return {
				totalResults: 1,
				data,
			};
		} );

		const result = getReportChartData( 'revenue', 'primary', {}, select );
		expect( result ).toEqual( { ...response, data: { ...data } } );
	} );

	it( 'returns combined results for multiple pages of data', () => {
		const totalResults = 110;
		const orders_count = 115;
		const gross_revenue = 13966.92;
		const intervals = [];
		for ( let i = 0; i < totalResults; i++ ) {
			intervals.push( {
				interval: 'day',
				date_start: '2018-07-01 00:00:00',
				subtotals: { orders_count, gross_revenue },
			} );
		}
		const totals = {
			orders_count: orders_count * totalResults,
			gross_revenue: gross_revenue * totalResults,
		};

		setIsReportStatsRequesting( () => {
			return false;
		} );
		setGetReportStatsError( () => {
			return undefined;
		} );
		setGetReportStats( ( endpoint, query ) => {
			if ( 2 === query.page ) {
				return {
					totalResults,
					data: {
						totals,
						intervals: intervals.slice( 100, 110 ),
					},
				};
			}
			return {
				totalResults,
				data: {
					totals,
					intervals: intervals.slice( 0, 100 ),
				},
			};
		} );

		const actualResponse = getReportChartData( 'revenue', 'primary', {}, select );
		const expectedResponse = {
			...response,
			data: {
				totals,
				intervals,
			},
		};

		expect( actualResponse ).toEqual( expectedResponse );
	} );

	it( 'returns isRequesting if additional requests are in progress', () => {
		setIsReportStatsRequesting( ( endpoint, query ) => {
			if ( 2 === query.page ) {
				return true;
			}
			return false;
		} );
		setGetReportStatsError( () => {
			return undefined;
		} );

		const result = getReportChartData( 'revenue', 'primary', {}, select );
		expect( result ).toEqual( { ...response, isRequesting: true } );
	} );

	it( 'returns isError if additional requests return an error', () => {
		setIsReportStatsRequesting( () => {
			return false;
		} );
		setGetReportStatsError( ( endpoint, query ) => {
			if ( 2 === query.page ) {
				return { error: 'Error' };
			}
			return undefined;
		} );
		const result = getReportChartData( 'revenue', 'primary', {}, select );
		expect( result ).toEqual( { ...response, isError: true } );
	} );

	it( 'returns empty state if a query returns no data', () => {
		setIsReportStatsRequesting( () => {
			return false;
		} );
		setGetReportStatsError( () => {
			return undefined;
		} );
		setGetReportStats( () => {
			return {
				totalResults: undefined,
				data: {},
			};
		} );

		const result = getReportChartData( 'revenue', 'primary', {}, select );
		expect( result ).toEqual( { ...response, isEmpty: true } );
	} );
} );

describe( 'getSummaryNumbers()', () => {
	const select = jest.fn().mockReturnValue( {} );
	const response = {
		isError: false,
		isRequesting: false,
		totals: {
			primary: null,
			secondary: null,
		},
	};

	const query = {
		after: '2018-10-10',
		before: '2018-10-10',
		period: 'custom',
		compare: 'previous_period',
	};

	beforeAll( () => {
		select( 'wc-api' ).getReportStats = jest.fn().mockReturnValue( {} );
		select( 'wc-api' ).isReportStatsRequesting = jest.fn().mockReturnValue( false );
		select( 'wc-api' ).getReportStatsError = jest.fn().mockReturnValue( false );
	} );

	afterAll( () => {
		select( 'wc-api' ).getReportStats.mockRestore();
		select( 'wc-api' ).isReportStatsRequesting.mockRestore();
		select( 'wc-api' ).getReportStatsError.mockRestore();
	} );

	function setGetReportStats( func ) {
		select( 'wc-api' ).getReportStats.mockImplementation( ( ...args ) => func( ...args ) );
	}

	function setIsReportStatsRequesting( func ) {
		select( 'wc-api' ).isReportStatsRequesting.mockImplementation( ( ...args ) => func( ...args ) );
	}

	function setGetReportStatsError( func ) {
		select( 'wc-api' ).getReportStatsError.mockImplementation( ( ...args ) => func( ...args ) );
	}

	it( 'returns isRequesting if a request is in progress', () => {
		setIsReportStatsRequesting( () => {
			return true;
		} );
		const result = getSummaryNumbers( 'revenue', query, select );
		expect( result ).toEqual( { ...response, isRequesting: true } );
	} );

	it( 'returns isError if request errors', () => {
		setIsReportStatsRequesting( () => {
			return false;
		} );
		setGetReportStatsError( () => {
			return { error: 'Error' };
		} );
		const result = getSummaryNumbers( 'revenue', query, select );
		expect( result ).toEqual( { ...response, isError: true } );
	} );

	it( 'returns results after queries finish', () => {
		const totals = {
			primary: {
				orders_count: 115,
				gross_revenue: 13966.92,
			},
			secondary: {
				orders_count: 85,
				gross_revenue: 10406.1,
			},
		};

		setIsReportStatsRequesting( () => {
			return false;
		} );
		setGetReportStatsError( () => {
			return undefined;
		} );
		setGetReportStats( () => {
			return {
				totals,
			};
		} );

		setGetReportStats( ( endpoint, _query ) => {
			if ( '2018-10-10T00:00:00+00:00' === _query.after ) {
				return {
					data: {
						totals: totals.primary,
						intervals: [],
					},
				};
			}
			return {
				data: {
					totals: totals.secondary,
					intervals: [],
				},
			};
		} );

		const result = getSummaryNumbers( 'revenue', query, select );
		expect( result ).toEqual( { ...response, totals } );
	} );
} );

describe( 'getFilterQuery', () => {
	/**
	 * Mock the orders config
	 */
	const filters = [
		{
			param: 'filter',
			filters: [
				{ value: 'top_meal', query: { lunch: 'burritos' } },
				{ value: 'top_dessert', query: { dinner: 'ice_cream' } },
				{ value: 'compare-cuisines', settings: { param: 'region' } },
				{
					value: 'food_destination',
					subFilters: [
						{ value: 'choose_a_european_city', settings: { param: 'european_cities' } },
					],
				},
			],
		},
	];

	const advancedFilters = {
		filters: {
			mexican: {
				rules: [ { value: 'is' }, { value: 'is_not' } ],
			},
			french: {
				rules: [ { value: 'includes' }, { value: 'excludes' } ],
			},
		},
	};

	ordersConfig.filters = filters;
	ordersConfig.advancedFilters = advancedFilters;

	it( 'should return an empty object if no filter param is given', () => {
		const query = {};
		const filterQuery = getFilterQuery( 'orders', query );

		expect( filterQuery ).toEqual( {} );
	} );

	it( 'should return an empty object if filter parameter is not in configs', () => {
		const query = { filter: 'canned_meat' };
		const filterQuery = getFilterQuery( 'orders', query );

		expect( filterQuery ).toEqual( {} );
	} );

	it( 'should return the query for an advanced filter', () => {
		const query = { filter: 'advanced', mexican_is: 'delicious' };
		const filterQuery = getFilterQuery( 'orders', query );

		expect( filterQuery ).toEqual( { mexican_is: 'delicious', match: 'all' } );
	} );

	it( 'should ignore other queries not defined by filter configs', () => {
		const query = {
			filter: 'advanced',
			mexican_is_not: 'healthy',
			orderby: 'calories',
			topping: 'salsa-verde',
		};
		const filterQuery = getFilterQuery( 'orders', query );

		expect( filterQuery ).toEqual( { mexican_is_not: 'healthy', match: 'all' } );
	} );

	it( 'should apply the match parameter advanced filters', () => {
		const query = {
			filter: 'advanced',
			french_includes: 'le-fromage',
			match: 'any',
		};
		const filterQuery = getFilterQuery( 'orders', query );

		expect( filterQuery ).toEqual( { french_includes: 'le-fromage', match: 'any' } );
	} );

	it( 'should return the query for compare filters', () => {
		const query = { filter: 'compare-cuisines', region: 'vietnam,malaysia,thailand' };
		const filterQuery = getFilterQuery( 'orders', query );

		expect( filterQuery ).toEqual( { region: 'vietnam,malaysia,thailand' } );
	} );

	it( 'should return the query for subFilters', () => {
		const query = { filter: 'choose_a_european_city', european_cities: 'paris,rome,barcelona' };
		const filterQuery = getFilterQuery( 'orders', query );

		expect( filterQuery ).toEqual( { european_cities: 'paris,rome,barcelona' } );
	} );
} );

describe( 'getReportTableData()', () => {
	const select = jest.fn().mockReturnValue( {} );
	const response = {
		isError: false,
		isRequesting: false,
		items: {
			data: [],
		},
	};

	const query = {
		after: '2018-10-10',
		before: '2018-10-10',
	};

	beforeAll( () => {
		select( 'wc-api' ).getReportItems = jest.fn().mockReturnValue( {} );
		select( 'wc-api' ).isReportItemsRequesting = jest.fn().mockReturnValue( false );
		select( 'wc-api' ).getReportItemsError = jest.fn().mockReturnValue( undefined );
	} );

	afterAll( () => {
		select( 'wc-api' ).getReportItems.mockRestore();
		select( 'wc-api' ).isReportItemsRequesting.mockRestore();
		select( 'wc-api' ).getReportItemsError.mockRestore();
	} );

	function setGetReportItems( func ) {
		select( 'wc-api' ).getReportItems.mockImplementation( ( ...args ) => func( ...args ) );
	}

	function setIsReportItemsRequesting( func ) {
		select( 'wc-api' ).isReportItemsRequesting.mockImplementation( ( ...args ) => func( ...args ) );
	}

	function setGetReportItemsError( func ) {
		select( 'wc-api' ).getReportItemsError.mockImplementation( ( ...args ) => func( ...args ) );
	}

	it( 'returns isRequesting if a request is in progress', () => {
		setIsReportItemsRequesting( () => true );

		const result = getReportTableData( 'coupons', query, select );

		expect( result ).toEqual( { ...response, query, isRequesting: true } );
		expect( select( 'wc-api' ).getReportItems ).toHaveBeenLastCalledWith( 'coupons', query );
		expect( select( 'wc-api' ).isReportItemsRequesting ).toHaveBeenLastCalledWith(
			'coupons',
			query
		);
		expect( select( 'wc-api' ).getReportItemsError ).toHaveBeenCalledTimes( 0 );
	} );

	it( 'returns isError if request errors', () => {
		setIsReportItemsRequesting( () => false );
		setGetReportItemsError( () => ( { error: 'Error' } ) );

		const result = getReportTableData( 'coupons', query, select );

		expect( result ).toEqual( { ...response, query, isError: true } );
		expect( select( 'wc-api' ).getReportItems ).toHaveBeenLastCalledWith( 'coupons', query );
		expect( select( 'wc-api' ).isReportItemsRequesting ).toHaveBeenLastCalledWith(
			'coupons',
			query
		);
		expect( select( 'wc-api' ).getReportItemsError ).toHaveBeenLastCalledWith( 'coupons', query );
	} );

	it( 'returns results after queries finish', () => {
		const items = [ { id: 1 }, { id: 2 }, { id: 3 } ];
		setIsReportItemsRequesting( () => false );
		setGetReportItemsError( () => undefined );
		setGetReportItems( () => items );

		const result = getReportTableData( 'coupons', query, select );

		expect( result ).toEqual( { ...response, query, items } );
		expect( select( 'wc-api' ).getReportItems ).toHaveBeenLastCalledWith( 'coupons', query );
		expect( select( 'wc-api' ).isReportItemsRequesting ).toHaveBeenLastCalledWith(
			'coupons',
			query
		);
		expect( select( 'wc-api' ).getReportItemsError ).toHaveBeenLastCalledWith( 'coupons', query );
	} );
} );
