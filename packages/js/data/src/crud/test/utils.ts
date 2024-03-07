/**
 * Internal dependencies
 */
import CRUD_ACTIONS from '../crud-actions';
import {
	applyNamespace,
	cleanQuery,
	getGenericActionName,
	getKey,
	getNamespaceKeys,
	getRequestIdentifier,
	getRestPath,
	getUrlParameters,
	maybeReplaceIdQuery,
	isValidIdQuery,
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

	it( 'should get the request identifier with no arguments', () => {
		const key = getRequestIdentifier( 'CREATE_ITEM' );
		expect( key ).toBe( 'CREATE_ITEM:[]' );
	} );

	it( 'should get the request identifier with a single argument', () => {
		const key = getRequestIdentifier( 'CREATE_ITEM', 'string_arg' );
		expect( key ).toBe( 'CREATE_ITEM:["string_arg"]' );
	} );

	it( 'should get the request identifier with array arguments', () => {
		const key = getRequestIdentifier( 'CREATE_ITEM', 'string_arg', [
			'A',
			'B',
		] );
		expect( key ).toBe( 'CREATE_ITEM:[["A","B"],"string_arg"]' );
	} );

	it( 'should get the request identifier with object arguments', () => {
		const key = getRequestIdentifier( 'CREATE_ITEM', 'string_arg', {
			object_property: { key: 'object_value' },
		} );
		expect( key ).toBe(
			'CREATE_ITEM:[{"object_property":{"key":"object_value"}},"string_arg"]'
		);
	} );

	it( 'should get the request identifier with any argument', () => {
		const key = getRequestIdentifier(
			'CREATE_ITEM',
			'string_arg',
			{
				object_property: { array_property: [ 'A', 'B' ] },
			},
			null,
			100,
			false
		);
		expect( key ).toBe(
			'CREATE_ITEM:[100,{"object_property":{"array_property":["A","B"]}},false,null,"string_arg"]'
		);
	} );

	it( 'should sort object properties in the request identifier', () => {
		const key = getRequestIdentifier( 'CREATE_ITEM', 'string_arg', {
			b: '2',
			a: '1',
		} );
		expect( key ).toBe( 'CREATE_ITEM:[{"a":"1","b":"2"},"string_arg"]' );
	} );

	it( 'should directly return the action when the action does not match the resource name', () => {
		const genercActionName = getGenericActionName(
			'createNonThing',
			'Thing'
		);
		expect( genercActionName ).toBe( 'createNonThing' );
	} );

	it( 'should get the generic create action name based on resource name', () => {
		const genercActionName = getGenericActionName( 'createThing', 'Thing' );
		expect( genercActionName ).toBe( CRUD_ACTIONS.CREATE_ITEM );
	} );

	it( 'should get the generic delete action name based on resource name', () => {
		const genercActionName = getGenericActionName( 'deleteThing', 'Thing' );
		expect( genercActionName ).toBe( CRUD_ACTIONS.DELETE_ITEM );
	} );

	it( 'should get the generic update action name based on resource name', () => {
		const genercActionName = getGenericActionName( 'updateThing', 'Thing' );
		expect( genercActionName ).toBe( CRUD_ACTIONS.UPDATE_ITEM );
	} );

	it( 'should return false when a valid ID query is not given', () => {
		expect( isValidIdQuery( { some: 'data' }, '/my/namespace' ) ).toBe(
			false
		);
	} );

	it( 'should return true when a valid ID is passed in an object', () => {
		expect( isValidIdQuery( { id: 22 }, '/my/namespace' ) ).toBe( true );
	} );

	it( 'should return true when a valid ID is passed directly', () => {
		expect( isValidIdQuery( 22, '/my/namespace' ) ).toBe( true );
	} );

	it( 'should return false when additional non-ID properties are provided', () => {
		expect( isValidIdQuery( { id: 22, other: 88 }, '/my/namespace' ) ).toBe(
			false
		);
	} );

	it( 'should return true when namespace ID properties are provided', () => {
		expect(
			isValidIdQuery(
				{ id: 22, parent_id: 88 },
				'/my/{parent_id}/namespace/'
			)
		).toBe( true );
	} );

	it( 'should replace the first argument when a valid ID query exists', () => {
		const args = [ { id: 22, parent_id: 88 }, 'second' ];
		const sanitizedArgs = maybeReplaceIdQuery(
			args,
			'/my/{parent_id}/namespace/'
		);
		expect( sanitizedArgs ).toEqual( [ '88/22', 'second' ] );
	} );

	it( 'should remain unchanged when the first argument is a string or number', () => {
		const args = [ 'first', 'second' ];
		const sanitizedArgs = maybeReplaceIdQuery( args, '/my/namespace/' );
		expect( sanitizedArgs ).toEqual( args );
	} );

	it( 'should remain unchanged when the first argument is not a valid ID query', () => {
		const args = [ { id: 22, parent_id: 88 }, 'second' ];
		const sanitizedArgs = maybeReplaceIdQuery( args, '/my/namespace/' );
		expect( sanitizedArgs ).toEqual( args );
	} );
} );
