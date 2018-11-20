/** @format */
/**
 * Internal dependencies
 */
import { getPersistedQuery } from '../index';

jest.mock( '../index', () => ( {
	...require.requireActual( '../index' ),
	getQuery: jest.fn().mockReturnValue( {
		filter: 'advanced',
		product_includes: 127,
		period: 'year',
		compare: 'previous_year',
		after: '2018-02-01',
		before: '2018-01-01',
		interval: 'day',
	} ),
} ) );

describe( 'getPersistedQuery', () => {
	it( "should return an empty object it the query doesn't contain any time related parameters", () => {
		const query = {
			filter: 'advanced',
			product_includes: 127,
		};
		const persistedQuery = {};

		expect( getPersistedQuery( query ) ).toEqual( persistedQuery );
	} );

	it( 'should return time related parameters', () => {
		const query = {
			filter: 'advanced',
			product_includes: 127,
			period: 'year',
			compare: 'previous_year',
			after: '2018-02-01',
			before: '2018-01-01',
			type: 'bar',
			interval: 'day',
		};
		const persistedQuery = {
			period: 'year',
			compare: 'previous_year',
			after: '2018-02-01',
			before: '2018-01-01',
			type: 'bar',
			interval: 'day',
		};

		expect( getPersistedQuery( query ) ).toEqual( persistedQuery );
	} );

	it( 'should get the query from getQuery() when none is provided in the params', () => {
		const persistedQuery = {
			period: 'year',
			compare: 'previous_year',
			after: '2018-02-01',
			before: '2018-01-01',
			interval: 'day',
		};

		expect( getPersistedQuery() ).toEqual( persistedQuery );
	} );
} );
