/**
 * Internal dependencies
 */
import { getFilterQuery, usesServerSideSearch } from '../utils';

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
	it( 'should be true when products are the only thing limiting the report', () => {
		expect( usesServerSideSearch( [ 'products' ] ) ).toBe( true );
	} );

	it( 'should be false when something else limits the report as well', () => {
		expect( usesServerSideSearch( [ 'products', 'categories' ] ) ).toBe(
			false
		);
	} );

	it( 'should be false for endpoints that do not resolve a search themselves', () => {
		expect( usesServerSideSearch( [ 'categories' ] ) ).toBe( false );
		expect( usesServerSideSearch( [ 'coupons' ] ) ).toBe( false );
	} );

	it( 'should be false when nothing limits the report', () => {
		expect( usesServerSideSearch( [] ) ).toBe( false );
	} );
} );

describe( 'getFilterQuery', () => {
	it( 'should send the search term to the products endpoint instead of a list of IDs', () => {
		expect(
			filterQuery( {
				endpoint: 'products',
				query: { search: 'widget', products: '1,2,3' },
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

	it( 'should keep sending resolved IDs when the report is limited by more than products', () => {
		expect(
			filterQuery( {
				endpoint: 'products',
				query: { search: 'widget', products: '1,2', categories: '5' },
				limitBy: [ 'products', 'categories' ],
			} )
		).toEqual( { products: '1,2', categories: '5' } );
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
