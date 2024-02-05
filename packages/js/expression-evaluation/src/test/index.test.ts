/**
 * Internal dependencies
 */

import { evaluate } from '../';

describe( 'evaluate', () => {
	it( 'should evaluate a null literal', () => {
		const result = evaluate( 'null' );

		expect( result ).toEqual( null );
	} );

	it( 'should evaluate a boolean true literal', () => {
		const result = evaluate( 'true' );

		expect( result ).toEqual( true );
	} );

	it( 'should evaluate a boolean false literal', () => {
		const result = evaluate( 'false' );

		expect( result ).toEqual( false );
	} );

	it( 'should evaluate a numeric integer literal', () => {
		const result = evaluate( '23' );

		expect( result ).toEqual( 23 );
	} );

	it( 'should evaluate a signed negative integer literal', () => {
		const result = evaluate( '-1' );

		expect( result ).toEqual( -1 );
	} );

	it( 'should evaluate a signed positive integer literal', () => {
		const result = evaluate( '+1' );

		expect( result ).toEqual( 1 );
	} );

	it( 'should evaluate a numeric floating point literal', () => {
		const result = evaluate( '5.23' );

		expect( result ).toEqual( 5.23 );
	} );

	it( 'should evaluate a signed negative floating point literal', () => {
		const result = evaluate( '-9.95' );

		expect( result ).toEqual( -9.95 );
	} );

	it( 'should evaluate a signed positive floating point literal', () => {
		const result = evaluate( '+9.95' );

		expect( result ).toEqual( 9.95 );
	} );

	it( 'should evaluate a numeric hexadecimal literal', () => {
		const result = evaluate( '0x23' );

		expect( result ).toEqual( 35 );
	} );

	it( 'should evaluate a string literal with double quotes', () => {
		const result = evaluate( '"foo"' );

		expect( result ).toEqual( 'foo' );
	} );

	it( 'should evaluate a string literal with double quotes and single quotes', () => {
		const result = evaluate( '"foo \'bar\'"' );

		expect( result ).toEqual( "foo 'bar'" );
	} );

	it( 'should evaluate a string literal with double quotes and escaped double quotes', () => {
		const result = evaluate( '"foo \\"bar\\""' );

		expect( result ).toEqual( 'foo "bar"' );
	} );

	it( 'should evaluate a string literal with double quotes and escaped backslashes', () => {
		// eslint-disable-next-line prettier/prettier
		const result = evaluate( '"foo \\\\\\"bar\\\\\\""' );

		expect( result ).toEqual( 'foo \\"bar\\"' );
	} );

	it( 'should evaluate a string literal with single quotes', () => {
		const result = evaluate( "'foo'" );

		expect( result ).toEqual( 'foo' );
	} );

	it( 'should evaluate a string literal with single quotes and double quotes', () => {
		// eslint-disable-next-line prettier/prettier
		const result = evaluate( "'foo \"bar\"'" );

		expect( result ).toEqual( 'foo "bar"' );
	} );

	it( 'should evaluate a string literal with single quotes and escaped single quotes', () => {
		const result = evaluate( "'foo \\'bar\\''" );

		expect( result ).toEqual( "foo 'bar'" );
	} );

	it( 'should evaluate a string literal with single quotes and escaped backslashes', () => {
		// eslint-disable-next-line prettier/prettier
		const result = evaluate( "'foo \\\\\\'bar\\\\\\''" );

		expect( result ).toEqual( "foo \\'bar\\'" );
	} );

	it( 'should evaluate a literal with whitespace around it', () => {
		const result = evaluate( ' 23 ' );

		expect( result ).toEqual( 23 );
	} );

	it( 'should evaluate a top-level context property', () => {
		const result = evaluate( 'foo', {
			foo: 'bar',
		} );

		expect( result ).toEqual( 'bar' );
	} );

	it( 'should evaluate a top-level context property with whitespace', () => {
		const result = evaluate( ' foo ', {
			foo: 'bar',
		} );

		expect( result ).toEqual( 'bar' );
	} );

	it( 'should evaluate a nested context property', () => {
		const result = evaluate( 'foo.bar', {
			foo: {
				bar: 'baz',
			},
		} );

		expect( result ).toEqual( 'baz' );
	} );

	it( 'should evaluate a nested context property with whitespace', () => {
		const result = evaluate( 'foo. bar', {
			foo: {
				bar: 'baz',
			},
		} );

		expect( result ).toEqual( 'baz' );
	} );

	it( 'should evaluate a nested context property with multiple lines', () => {
		const result = evaluate(
			`foo.
			bar`,
			{
				foo: {
					bar: 'baz',
				},
			}
		);

		expect( result ).toEqual( 'baz' );
	} );

	it( 'should evaluate a nested context property with a key/value pair array (like meta_data)', () => {
		const result = evaluate( 'foo.bar.baz', {
			foo: {
				bar: [
					{
						key: 'bee',
						value: 'boo',
					},
					{
						key: 'baz',
						value: 'qux',
					},
				],
			},
		} );

		expect( result ).toEqual( 'qux' );
	} );

	it( 'should evaluate a NOT expression', () => {
		const result = evaluate( '!foo', {
			foo: true,
		} );

		expect( result ).toEqual( false );
	} );

	it( 'should evaluate a double NOT expression', () => {
		const result = evaluate( '!!foo', {
			foo: true,
		} );

		expect( result ).toEqual( true );
	} );

	it( 'should evaluate a NOT expression with parentheses', () => {
		const result = evaluate( '!( foo )', {
			foo: true,
		} );

		expect( result ).toEqual( false );
	} );

	it( 'should evaluate a NOT expression with parentheses and spaces', () => {
		const result = evaluate( '! ( foo ) ', {
			foo: true,
		} );

		expect( result ).toEqual( false );
	} );

	it( 'should evaluate a multiplication expression', () => {
		const result = evaluate( 'foo * 2', {
			foo: 2,
		} );

		expect( result ).toEqual( 4 );
	} );

	it( 'should evaluate a division expression', () => {
		const result = evaluate( 'foo / 2', {
			foo: 4,
		} );

		expect( result ).toEqual( 2 );
	} );

	it( 'should evaluate a modulo expression', () => {
		const result = evaluate( 'foo % 2', {
			foo: 5,
		} );

		expect( result ).toEqual( 1 );
	} );

	it( 'should evaluate an addition expression', () => {
		const result = evaluate( 'foo + 2', {
			foo: 3,
		} );

		expect( result ).toEqual( 5 );
	} );

	it( 'should evaluate a subtraction expression', () => {
		const result = evaluate( 'foo - 2', {
			foo: 5,
		} );

		expect( result ).toEqual( 3 );
	} );

	it( 'should evaluate a complex arithmetic expression', () => {
		const result = evaluate( 'foo * 2 + 1', {
			foo: 3,
		} );

		expect( result ).toEqual( 7 );
	} );

	it( 'should evaluate a complex arithmetic expression with parenthesis', () => {
		const result = evaluate( 'foo * (2 + 1)', {
			foo: 3,
		} );

		expect( result ).toEqual( 9 );
	} );

	it( 'should evaluate a less than or equal expression', () => {
		const result = evaluate( 'foo <= 1', {
			foo: 1,
		} );

		expect( result ).toEqual( true );
	} );

	it( 'should evaluate a less than expression', () => {
		const result = evaluate( 'foo < 1', {
			foo: 1,
		} );

		expect( result ).toEqual( false );
	} );

	it( 'should evaluate a greater than or equal expression', () => {
		const result = evaluate( 'foo >= 1', {
			foo: 1,
		} );

		expect( result ).toEqual( true );
	} );

	it( 'should evaluate a greater than expression', () => {
		const result = evaluate( 'foo > 1', {
			foo: 1,
		} );

		expect( result ).toEqual( false );
	} );

	it( 'should evaluate an strict equality expression', () => {
		const result = evaluate( 'foo === "bar"', {
			foo: 'bar',
		} );

		expect( result ).toEqual( true );
	} );

	it( 'should evaluate an strict inequality expression', () => {
		const result = evaluate( 'foo !== "bar"', {
			foo: 'bar',
		} );

		expect( result ).toEqual( false );
	} );

	it( 'should evaluate an equality expression', () => {
		const result = evaluate( 'foo == "bar"', {
			foo: 'bar',
		} );

		expect( result ).toEqual( true );
	} );

	it( 'should evaluate an inequality expression', () => {
		const result = evaluate( 'foo != "bar"', {
			foo: 'bar',
		} );

		expect( result ).toEqual( false );
	} );

	it( 'should evaluate a conditional expression that is true', () => {
		const result = evaluate( 'foo ? "bar" : "baz"', {
			foo: true,
		} );

		expect( result ).toEqual( 'bar' );
	} );

	it( 'should evaluate a conditional expression that is false', () => {
		const result = evaluate( 'foo ? "bar" : "baz"', {
			foo: false,
		} );

		expect( result ).toEqual( 'baz' );
	} );

	it( 'should evaluate a logical OR expression', () => {
		const result = evaluate( 'foo || bar', {
			foo: true,
			bar: false,
		} );

		expect( result ).toEqual( true );
	} );

	it( 'should evaluate a logical AND expression', () => {
		const result = evaluate( 'foo && bar', {
			foo: true,
			bar: false,
		} );

		expect( result ).toEqual( false );
	} );

	it( 'should evaluate a multiline expression', () => {
		const result = evaluate(
			`foo
			|| bar
			|| baz`,
			{
				foo: false,
				bar: false,
				baz: true,
			}
		);

		expect( result ).toEqual( true );
	} );

	it( 'should evaluate a complex expression', () => {
		const result = evaluate(
			`foo.bar
			&& ( foo.baz === "qux" || foo.baz === "quux" )`,
			{
				foo: {
					bar: true,
					baz: 'quux',
				},
			}
		);

		expect( result ).toEqual( true );
	} );

	it( 'should evaluate a complex expression with arithmetic, relational, and logical operators', () => {
		const result = evaluate(
			`foo.bar
			&& ( foo.baz === "qux" || foo.baz === "quux" )
			&& ( foo.quux > 1 && foo.quux <= 5 )`,
			{
				foo: {
					bar: true,
					baz: 'quux',
					quux: 10,
				},
			}
		);

		expect( result ).toEqual( false );
	} );

	it( 'should evaluate a complex expression with conditional, arithmetic, relational, and logical operators', () => {
		const result = evaluate(
			`foo.bar
			&& ( foo.baz === "qux" || foo.baz === "quux" )
			&& ( foo.quux > 1 && foo.quux <= 5 )
			? "boo"
			: "baa"`,
			{
				foo: {
					bar: true,
					baz: 'quux',
					quux: 10,
				},
			}
		);

		expect( result ).toEqual( 'baa' );
	} );

	it( 'should evaluate an expression with needless parentheses', () => {
		const result = evaluate( '(((foo)))', {
			foo: true,
		} );

		expect( result ).toEqual( true );
	} );

	it( 'should evaluate an expression with a multiline comment at the end', () => {
		const result = evaluate( 'foo /* + 23 */', {
			foo: 5,
		} );

		expect( result ).toEqual( 5 );
	} );

	it( 'should evaluate an expression with a multiline comment at the beginning', () => {
		const result = evaluate( '/* 23 + */ foo', {
			foo: 5,
		} );

		expect( result ).toEqual( 5 );
	} );

	it( 'should evaluate an expression with a multiline comment in the middle', () => {
		const result = evaluate( 'foo + /* 23 */ bar', {
			foo: 5,
			bar: 3,
		} );

		expect( result ).toEqual( 8 );
	} );

	it( 'should evaluate a multiline expression with a multiline comment', () => {
		const result = evaluate(
			`foo
			/*
			+ bar
			+ boo
			*/
			+ baz`,
			{
				foo: 5,
				bar: 23,
				boo: 6,
				baz: 3,
			}
		);

		expect( result ).toEqual( 8 );
	} );

	it( 'should throw an error if the expression is invalid', () => {
		expect( () => evaluate( '= 1' ) ).toThrow();
	} );
} );
