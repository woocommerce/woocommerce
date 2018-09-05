/*
* @format
*/

/**
 * Internal dependencies
 */
import { isReportDataEmpty, getAllReportData } from '../utils';

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

// TODO Use more general selectors from https://github.com/woocommerce/wc-admin/pull/307
describe( 'getAllReportData()', () => {
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
		select( 'wc-admin' ).getReportStats = jest.fn().mockReturnValue( {} );
		select( 'wc-admin' ).isReportStatsRequesting = jest.fn().mockReturnValue( false );
		select( 'wc-admin' ).isReportStatsError = jest.fn().mockReturnValue( false );
	} );

	afterAll( () => {
		select( 'wc-admin' ).getReportStats.mockRestore();
		select( 'wc-admin' ).isReportStatsRequesting.mockRestore();
		select( 'wc-admin' ).isReportStatsError.mockRestore();
	} );

	function setGetReportStats( func ) {
		select( 'wc-admin' ).getReportStats.mockImplementation( ( ...args ) => func( ...args ) );
	}

	function setIsReportStatsRequesting( func ) {
		select( 'wc-admin' ).isReportStatsRequesting.mockImplementation( ( ...args ) =>
			func( ...args )
		);
	}

	function setIsReportStatsError( func ) {
		select( 'wc-admin' ).isReportStatsError.mockImplementation( ( ...args ) => func( ...args ) );
	}

	it( 'returns isRequesting if first request is in progress', () => {
		setIsReportStatsRequesting( () => {
			return true;
		} );
		const result = getAllReportData( 'revenue', {}, select );
		expect( result ).toEqual( { ...response, isRequesting: true } );
	} );

	it( 'returns isError if first request errors', () => {
		setIsReportStatsRequesting( () => {
			return false;
		} );
		setIsReportStatsError( () => {
			return true;
		} );
		const result = getAllReportData( 'revenue', {}, select );
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
		setIsReportStatsError( () => {
			return false;
		} );
		setGetReportStats( () => {
			return {
				totalResults: 1,
				data,
			};
		} );

		const result = getAllReportData( 'revenue', {}, select );
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
		setIsReportStatsError( () => {
			return false;
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

		const actualResponse = getAllReportData( 'revenue', {}, select );
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
		setIsReportStatsError( () => {
			return false;
		} );

		const result = getAllReportData( 'revenue', {}, select );
		expect( result ).toEqual( { ...response, isRequesting: true } );
	} );

	it( 'returns isError if additional requests return an error', () => {
		setIsReportStatsRequesting( () => {
			return false;
		} );
		setIsReportStatsError( ( endpoint, query ) => {
			if ( 2 === query.page ) {
				return true;
			}
			return false;
		} );
		const result = getAllReportData( 'revenue', {}, select );
		expect( result ).toEqual( { ...response, isError: true } );
	} );

	it( 'returns empty state if a query returns no data', () => {
		setIsReportStatsRequesting( () => {
			return false;
		} );
		setIsReportStatsError( () => {
			return false;
		} );
		setGetReportStats( () => {
			return {
				totalResults: undefined,
				data: {},
			};
		} );

		const result = getAllReportData( 'revenue', {}, select );
		expect( result ).toEqual( { ...response, isEmpty: true } );
	} );
} );
