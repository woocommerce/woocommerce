/**
 * Internal dependencies
 */

import { parser } from '../parser';

describe( 'parser', () => {
	it( 'should parse a null literal', () => {
		const result = parser.parse( 'null' );

		expect( result ).toEqual( null );
	} );

	it( 'should parse a boolean true literal', () => {
		const result = parser.parse( 'true' );

		expect( result ).toEqual( true );
	} );

	it( 'should parse a boolean false literal', () => {
		const result = parser.parse( 'false' );

		expect( result ).toEqual( false );
	} );

	it( 'should parse a numeric integer literal', () => {
		const result = parser.parse( '23' );

		expect( result ).toEqual( 23 );
	} );

	it( 'should parse a signed negative integer literal', () => {
		const result = parser.parse( '-1' );

		expect( result ).toEqual( -1 );
	} );

	it( 'should parse a signed positive integer literal', () => {
		const result = parser.parse( '+1' );

		expect( result ).toEqual( 1 );
	} );

	it( 'should parse a numeric floating point literal', () => {
		const result = parser.parse( '5.23' );

		expect( result ).toEqual( 5.23 );
	} );

	it( 'should parse a signed negative floating point literal', () => {
		const result = parser.parse( '-9.95' );

		expect( result ).toEqual( -9.95 );
	} );

	it( 'should parse a signed positive floating point literal', () => {
		const result = parser.parse( '+9.95' );

		expect( result ).toEqual( 9.95 );
	} );

	it( 'should parse a numeric hexadecimal literal', () => {
		const result = parser.parse( '0x23' );

		expect( result ).toEqual( 35 );
	} );

	it( 'should parse a string literal with double quotes', () => {
		const result = parser.parse( '"foo"' );

		expect( result ).toEqual( 'foo' );
	} );

	it( 'should parse a string literal with double quotes and single quotes', () => {
		const result = parser.parse( '"foo \'bar\'"' );

		expect( result ).toEqual( "foo 'bar'" );
	} );

	it( 'should parse a string literal with double quotes and escaped double quotes', () => {
		const result = parser.parse( '"foo \\"bar\\""' );

		expect( result ).toEqual( 'foo "bar"' );
	} );

	it( 'should parse a string literal with double quotes and escaped backslashes', () => {
		// eslint-disable-next-line prettier/prettier
		const result = parser.parse( '"foo \\\\\\"bar\\\\\\""' );

		expect( result ).toEqual( 'foo \\"bar\\"' );
	} );

	it( 'should parse a string literal with single quotes', () => {
		const result = parser.parse( "'foo'" );

		expect( result ).toEqual( 'foo' );
	} );

	it( 'should parse a string literal with single quotes and double quotes', () => {
		// eslint-disable-next-line prettier/prettier
		const result = parser.parse( "'foo \"bar\"'" );

		expect( result ).toEqual( 'foo "bar"' );
	} );

	it( 'should parse a string literal with single quotes and escaped single quotes', () => {
		const result = parser.parse( "'foo \\'bar\\''" );

		expect( result ).toEqual( "foo 'bar'" );
	} );

	it( 'should parse a string literal with single quotes and escaped backslashes', () => {
		// eslint-disable-next-line prettier/prettier
		const result = parser.parse( "'foo \\\\\\'bar\\\\\\''" );

		expect( result ).toEqual( "foo \\'bar\\'" );
	} );

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

	it( 'should parse an strict equality expression', () => {
		const result = parser.parse( 'foo === "bar"', {
			context: {
				foo: 'bar',
			},
		} );

		expect( result ).toEqual( true );
	} );

	it( 'should parse an strict inequality expression', () => {
		const result = parser.parse( 'foo !== "bar"', {
			context: {
				foo: 'bar',
			},
		} );

		expect( result ).toEqual( false );
	} );

	it( 'should parse an equality expression', () => {
		const result = parser.parse( 'foo == "bar"', {
			context: {
				foo: 'bar',
			},
		} );

		expect( result ).toEqual( true );
	} );

	it( 'should parse an inequality expression', () => {
		const result = parser.parse( 'foo != "bar"', {
			context: {
				foo: 'bar',
			},
		} );

		expect( result ).toEqual( false );
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

	it( 'should parse a multiline expression', () => {
		const result = parser.parse(
			`foo
			|| bar
			|| baz`,
			{
				context: {
					foo: false,
					bar: false,
					baz: true,
				},
			}
		);

		expect( result ).toEqual( true );
	} );

	it( 'should throw an error if the expression is invalid', () => {
		expect( () => parser.parse( '= 1' ) ).toThrow();
	} );
} );
