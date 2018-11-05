/** @format */
/**
 * Internal dependencies
 */
import { getTimeRelatedQuery } from '../index';

jest.mock( '../index', () => ( {
	...require.requireActual( '../index' ),
	getQuery: jest.fn().mockReturnValue( {
		filter: 'advanced',
		product_includes: 127,
		period: 'year',
		compare: 'previous_year',
		after: '2018-02-01',
		before: '2018-01-01',
	} ),
} ) );

describe( 'getTimeRelatedQuery', () => {
	it( "should return an empty object it the query doesn't contain any time related parameters", () => {
		const query = {
			filter: 'advanced',
			product_includes: 127,
		};
		const timeRelatedQuery = {};

		expect( getTimeRelatedQuery( query ) ).toEqual( timeRelatedQuery );
	} );

	it( 'should return time related parameters', () => {
		const query = {
			filter: 'advanced',
			product_includes: 127,
			period: 'year',
			compare: 'previous_year',
			after: '2018-02-01',
			before: '2018-01-01',
		};
		const timeRelatedQuery = {
			period: 'year',
			compare: 'previous_year',
			after: '2018-02-01',
			before: '2018-01-01',
		};

		expect( getTimeRelatedQuery( query ) ).toEqual( timeRelatedQuery );
	} );

	it( 'should get the query from getQuery() when none is provided in the params', () => {
		const timeRelatedQuery = {
			period: 'year',
			compare: 'previous_year',
			after: '2018-02-01',
			before: '2018-01-01',
		};

		expect( getTimeRelatedQuery() ).toEqual( timeRelatedQuery );
	} );
} );
