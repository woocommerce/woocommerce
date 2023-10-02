/**
 * Internal dependencies
 */

import { parser } from '../parser';

describe( 'parser', () => {
	it( 'should parse a top-level context property', () => {
		const result = parser.parse( 'foo', {
			context: {
				foo: 'bar',
			},
		} );

		expect( result ).toEqual( 'bar' );
	} );

	it( 'should parse a nested context property', () => {
		const result = parser.parse( 'foo.bar', {
			context: {
				foo: {
					bar: 'baz',
				},
			},
		} );

		expect( result ).toEqual( 'baz' );
	} );

	it( 'should parse a logical OR expression', () => {
		const result = parser.parse( 'foo || bar', {
			context: {
				foo: true,
				bar: false,
			},
		} );

		expect( result ).toEqual( true );
	} );

	it( 'should parse a logical AND expression', () => {
		const result = parser.parse( 'foo && bar', {
			context: {
				foo: true,
				bar: false,
			},
		} );

		expect( result ).toEqual( false );
	} );

	it( 'should parse a NOT expression', () => {
		const result = parser.parse( '!foo', {
			context: {
				foo: true,
			},
		} );

		expect( result ).toEqual( false );
	} );

	it( 'should parse a double NOT expression', () => {
		const result = parser.parse( '!!foo', {
			context: {
				foo: true,
			},
		} );

		expect( result ).toEqual( true );
	} );

	it( 'should throw an error if the expression is invalid', () => {
		expect( () => parser.parse( '1' ) ).toThrow();
	} );
} );
