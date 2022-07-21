/**
 * Internal dependencies
 */
import {
	applyNamespace,
	cleanQuery,
	getKey,
	getNamespaceKeys,
	getRestPath,
	getUrlParameters,
	parseId,
} from '../utils';

describe( 'utils', () => {
	it( 'should get the rest path when no parameters are given', () => {
		const path = getRestPath( 'test/path', {}, [] );
		expect( path ).toEqual( 'test/path' );
	} );

	it( 'should replace the rest path parameters when provided', () => {
		const path = getRestPath( 'test/{parent}/path', {}, [ 'insert' ] );
		expect( path ).toEqual( 'test/insert/path' );
	} );

	it( 'should replace the rest path parameters when multiple are provided', () => {
		const path = getRestPath( 'test/{parent}/{other}/path', {}, [
			'insert',
			'next',
		] );
		expect( path ).toEqual( 'test/insert/next/path' );
	} );

	it( 'should throw an error when not all parameters are replaced', () => {
		expect( () =>
			getRestPath( 'test/{parent}/{other}/path', {}, [ 'insert' ] )
		).toThrow( Error );
	} );

	it( 'should get the key when no parent is provided', () => {
		const key = getKey( 3, [] );
		expect( key ).toEqual( 3 );
	} );

	it( 'should get the key when a parent is provided', () => {
		const key = getKey( 3, [ 5 ] );
		expect( key ).toEqual( '5/3' );
	} );

	it( 'should get the correct ID information when only an ID is given', () => {
		const parsed = parseId( 3 );
		expect( parsed.key ).toEqual( 3 );
		expect( parsed.id ).toEqual( 3 );
	} );

	it( 'should get the correct ID information when an object is given', () => {
		const parsed = parseId( { id: 3, parent_id: 5 }, [ 5 ] );
		expect( parsed.key ).toEqual( '5/3' );
		expect( parsed.id ).toEqual( 3 );
	} );

	it( 'should apply the namespace as an argument to a given function', () => {
		const namespace = 'test/namespace';
		const mockedCallback = jest.fn();
		const wrappedFunction = applyNamespace( mockedCallback, namespace );
		wrappedFunction( 'a' );

		expect( mockedCallback ).toBeCalledTimes( 1 );
		expect( mockedCallback ).toBeCalledWith( 'a', 'test/namespace' );
	} );

	it( 'should get the keys from a namespace', () => {
		const namespace = 'test/{first}/namespace/{second}';
		const keys = getNamespaceKeys( namespace );
		expect( keys.length ).toBe( 2 );
		expect( keys[ 0 ] ).toBe( 'first' );
		expect( keys[ 1 ] ).toBe( 'second' );
	} );

	it( 'should return an empty array from a namespace without params', () => {
		const namespace = 'test/namespace';
		const keys = getNamespaceKeys( namespace );
		expect( keys.length ).toBe( 0 );
	} );

	it( 'should get the URL parameters given a namespace and query', () => {
		const namespace = 'test/{my_attribute}/namespace/{other_attribute}';
		const params = getUrlParameters( namespace, {
			id: 5,
			my_attribute: 'flavortown',
			other_attribute: 'donkeysauce',
		} );
		expect( params.length ).toBe( 2 );
		expect( params[ 0 ] ).toBe( 'flavortown' );
		expect( params[ 1 ] ).toBe( 'donkeysauce' );
	} );

	it( 'should return an empty array when no namespace variables are matched', () => {
		const namespace = 'test/{my_attribute}/namespace';
		const params = getUrlParameters( namespace, {
			id: 5,
			different_attribute: 'flavortown',
		} );
		expect( params.length ).toBe( 0 );
	} );

	it( 'should remove namespace params from a given query', () => {
		const namespace = 'test/{my_attribute}/namespace';
		const query = {
			other_attribute: 'a',
			my_attribute: 'b',
		};
		const params = cleanQuery( query, namespace );
		expect( params.other_attribute ).toBe( 'a' );
		expect( params.my_attribute ).toBeUndefined();
	} );
} );
