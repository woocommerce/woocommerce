/**
 * Internal dependencies
 */
import { getKey, getRestPath, parseId } from '../utils';

describe( 'utils', () => {
	it( 'should get the rest path when no parameters are given', () => {
		const path = getRestPath( 'test/path', {} );
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
		const key = getKey( 3, null );
		expect( key ).toEqual( 3 );
	} );

	it( 'should get the key when a parent is provided', () => {
		const key = getKey( 3, 5 );
		expect( key ).toEqual( '5/3' );
	} );

	it( 'should get the correct ID information when only an ID is given', () => {
		const parsed = parseId( 3 );
		expect( parsed.key ).toEqual( 3 );
		expect( parsed.id ).toEqual( 3 );
		expect( parsed.parent_id ).toEqual( null );
	} );

	it( 'should get the correct ID information when an object is given', () => {
		const parsed = parseId( { id: 3, parent_id: 5 } );
		expect( parsed.key ).toEqual( '5/3' );
		expect( parsed.id ).toEqual( 3 );
		expect( parsed.parent_id ).toEqual( 5 );
	} );
} );
