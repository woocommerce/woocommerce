/**
 * Internal dependencies
 */
import {
	getIdsFromQuery,
	getSetOfIdsFromQuery,
	getHistory,
	getPersistedQuery,
	getSearchWords,
	getNewPath,
	addHistoryListener,
} from '../index';

global.window = Object.create( window );
global.window.wcNavigation = {};

describe( 'getPersistedQuery', () => {
	beforeEach( () => {
		getHistory().push(
			getNewPath(
				{
					filter: 'advanced',
					product_includes: 127,
					period: 'year',
					compare: 'previous_year',
					after: '2018-02-01',
					before: '2018-01-01',
					interval: 'day',
					search: 'lorem',
				},
				'/',
				{}
			)
		);
	} );

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

describe( 'getSearchWords', () => {
	it( 'should get the search words from a query object', () => {
		const query = {
			search: 'lorem,dolor sit',
		};
		const searchWords = [ 'lorem', 'dolor sit' ];

		expect( getSearchWords( query ) ).toEqual( searchWords );
	} );

	it( 'should parse `%2C` as commas', () => {
		const query = {
			search: 'lorem%2Cipsum,dolor sit',
		};
		const searchWords = [ 'lorem,ipsum', 'dolor sit' ];

		expect( getSearchWords( query ) ).toEqual( searchWords );
	} );

	it( 'should return an empty array if the query has no `search` property', () => {
		const query = {};
		const searchWords = [];

		expect( getSearchWords( query ) ).toEqual( searchWords );
	} );

	it( 'should use the persisted query when it receives no params', () => {
		const searchWords = [ 'lorem' ];

		expect( getSearchWords() ).toEqual( searchWords );
	} );

	it( 'should throw an error if the param is not an object', () => {
		expect( () => getSearchWords( 'lorem' ) ).toThrow( Error );
	} );

	it( 'should throw an error if the `search` property is not a string', () => {
		const query = {
			search: new Object(),
		};

		expect( () => getSearchWords( query ) ).toThrow( Error );
	} );
} );

describe( 'getNewPath', () => {
	it( 'should have default page as "wc-admin"', () => {
		const path = getNewPath( {}, '', {} );

		expect( path ).toEqual( 'admin.php?page=wc-admin&path=' );
	} );

	it( 'should override default page when page parameter is specified', () => {
		const path = getNewPath( {}, '', {}, 'custom-page' );

		expect( path ).toEqual( 'admin.php?page=custom-page&path=' );
	} );

	it( 'should override default page by query parameter over page parameter', () => {
		const path = getNewPath(
			{
				page: 'custom-page',
			},
			'',
			{},
			'default-page'
		);

		expect( path ).toEqual( 'admin.php?page=custom-page&path=' );
	} );
} );

describe( 'addHistoryListener', () => {
	it( 'should add a custom event to the browser pushState', () => {
		const mockCallback = jest.fn();
		const removeListener = addHistoryListener( mockCallback );
		window.history.pushState( {}, 'Test pushState' );
		window.history.pushState( {}, 'Test pushState 2' );

		expect( mockCallback.mock.calls.length ).toBe( 2 );

		// Check that events are no longer called after removing the listener.
		removeListener();
		window.history.pushState( {}, 'Test pushState 3' );
		expect( mockCallback.mock.calls.length ).toBe( 2 );
	} );

	it( 'should add a custom event to the browser replaceState', () => {
		const mockCallback = jest.fn();
		const removeListener = addHistoryListener( mockCallback );
		window.history.replaceState( {}, 'Test replaceState' );
		window.history.replaceState( {}, 'Test replaceState 2' );

		expect( mockCallback.mock.calls.length ).toBe( 2 );

		// Check that events are no longer called after removing the listener.
		removeListener();
		window.history.replaceState( {}, 'Test replaceState 3' );
		expect( mockCallback.mock.calls.length ).toBe( 2 );
	} );
} );

describe( 'getIdsFromQuery', () => {
	it( 'if the given query is empty, should return an empty array', () => {
		expect( getIdsFromQuery( '' ) ).toEqual( [] );
	} );

	it( 'if the given query is undefined, should return an empty array', () => {
		expect( getIdsFromQuery( undefined ) ).toEqual( [] );
	} );

	it( 'if the given query is does not contain any coma-separated numbers, should return an empty array', () => {
		expect( getIdsFromQuery( 'foo123,bar,baz1.' ) ).toEqual( [] );
	} );

	describe( 'if the given query contains numbers', () => {
		it( 'should return an array of them', () => {
			expect( getIdsFromQuery( '77,8,-1' ) ).toContain( 77, 8, -1 );
		} );
		it( 'should consider `0` a valid id', () => {
			expect( getIdsFromQuery( '0' ) ).toContain( 0 );
		} );
		it( 'should map floats to integers', () => {
			expect( getIdsFromQuery( '77,8.54' ) ).toEqual( [ 77, 8 ] );
		} );
		it( 'should ignore duplicates', () => {
			expect( getIdsFromQuery( '77,8,8' ) ).toEqual( [ 77, 8 ] );
			// Consider two floats that maps to the same integer a duplicate.
			expect( getIdsFromQuery( '77,8.5,8.4' ) ).toEqual( [ 77, 8 ] );
		} );
		it( 'should ignore non numbers entries in the coma-separated list', () => {
			expect( getIdsFromQuery( '77,,8,foo,null,9' ) ).toEqual( [
				77,
				8,
				9,
			] );
		} );
	} );
} );

describe( 'getSetOfIdsFromQuery', () => {
	it( 'if the given query is empty, should return an empty set', () => {
		expect( getSetOfIdsFromQuery( '' ) ).toEqual( new Set() );
	} );

	it( 'if the given query is undefined, should return an empty set', () => {
		expect( getSetOfIdsFromQuery( undefined ) ).toEqual( new Set() );
	} );

	it( 'if the given query is does not contain any coma-separated numbers, should return an empty set', () => {
		expect( getSetOfIdsFromQuery( 'foo123,bar,baz1.' ) ).toEqual(
			new Set()
		);
	} );

	describe( 'if the given query contains numbers', () => {
		it( 'should return a set of them', () => {
			expect( getSetOfIdsFromQuery( '77,8,-1' ) ).toEqual(
				new Set( [ 77, 8, -1 ] )
			);
		} );
		it( 'should consider `0` a valid id', () => {
			expect( getSetOfIdsFromQuery( '0' ) ).toContain( 0 );
			expect( getSetOfIdsFromQuery( '77,0,1' ) ).toContain( 0 );
		} );
		it( 'should map floats to integers', () => {
			expect( getSetOfIdsFromQuery( '77,8.54' ) ).toEqual(
				new Set( [ 77, 8 ] )
			);
		} );
		it( 'should ignore duplicates', () => {
			expect( getSetOfIdsFromQuery( '77,8,8' ) ).toBeInstanceOf( Set );
		} );
		it( 'should ignore non numbers entries in the coma-separated list', () => {
			expect( getSetOfIdsFromQuery( '77,,8,foo,null,9' ) ).toEqual(
				new Set( [ 77, 8, 9 ] )
			);
		} );
	} );
} );
