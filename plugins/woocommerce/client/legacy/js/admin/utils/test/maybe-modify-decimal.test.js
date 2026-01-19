/**
 * Test for maybeModifyDecimal method from utils/maybe-modify-decimal.js
 */

// Import the utility function
const { maybeModifyDecimal } = require('../maybe-modify-decimal');

describe( 'Maybe Modify Decimal Utils - maybeModifyDecimal', () => {

	test( 'should import function from utility file', () => {
		expect( typeof maybeModifyDecimal ).toBe( 'function' );
		expect( maybeModifyDecimal.length ).toBe( 2 ); // expects 2 parameters: value and config
	} );

	describe( 'Input validation', () => {
		test( 'should return original value for invalid inputs', () => {
			const config = { decimalSeparator: ',' };

			// Test null and undefined values
			expect( maybeModifyDecimal( null, config ) ).toBe( null );
			expect( maybeModifyDecimal( undefined, config ) ).toBe( undefined );
			expect( maybeModifyDecimal( '', config ) ).toBe( '' );

			// Test non-string values
			expect( maybeModifyDecimal( 123, config ) ).toBe( 123 );
			expect( maybeModifyDecimal( 123.45, config ) ).toBe( 123.45 );
			expect( maybeModifyDecimal( true, config ) ).toBe( true );
			expect( maybeModifyDecimal( false, config ) ).toBe( false );
			expect( maybeModifyDecimal( {}, config ) ).toStrictEqual( {} );
			expect( maybeModifyDecimal( [], config ) ).toStrictEqual( [] );
		} );

		test( 'should return original value when config is missing or invalid', () => {
			const testValue = '123.45';

			// Test missing config
			expect( maybeModifyDecimal( testValue, null ) ).toBe( testValue );
			expect( maybeModifyDecimal( testValue, undefined ) ).toBe( testValue );
			expect( maybeModifyDecimal( testValue, '' ) ).toBe( testValue );

			// Test non-object config
			expect( maybeModifyDecimal( testValue, 'invalid' ) ).toBe( testValue );
			expect( maybeModifyDecimal( testValue, 123 ) ).toBe( testValue );
			expect( maybeModifyDecimal( testValue, true ) ).toBe( testValue );
		} );
	} );

	describe( 'Decimal separator replacement', () => {
		test( 'should replace dot with comma when decimal separator is comma', () => {
			const config = { decimalSeparator: ',' };

			expect( maybeModifyDecimal( '123.45', config ) ).toBe( '123,45' );
			expect( maybeModifyDecimal( '0.99', config ) ).toBe( '0,99' );
			expect( maybeModifyDecimal( '999.00', config ) ).toBe( '999,00' );
			expect( maybeModifyDecimal( '.5', config ) ).toBe( ',5' );
			expect( maybeModifyDecimal( '5.', config ) ).toBe( '5,' );
			expect( maybeModifyDecimal( '1234.56', config ) ).toBe('1234,56');
		} );

		test( 'should replace dot with other decimal separators', () => {
			// Test with space as decimal separator
			const spaceConfig = { decimalSeparator: ' ' };
			expect( maybeModifyDecimal( '123.45', spaceConfig ) ).toBe( '123 45' );

			// Test with custom character
			const customConfig = { decimalSeparator: 'd' };
			expect( maybeModifyDecimal( '123.45', customConfig ) ).toBe( '123d45' );
		} );

		test( 'should not modify when decimal separator is already dot', () => {
			const config = { decimalSeparator: '.' };

			expect( maybeModifyDecimal( '123.45', config ) ).toBe( '123.45' );
			expect( maybeModifyDecimal( '0.99', config ) ).toBe( '0.99' );
			expect( maybeModifyDecimal( '999.00', config ) ).toBe( '999.00' );
			expect( maybeModifyDecimal( '1234.56', config ) ).toBe('1234.56');
		} );

		test( 'should not modify values without decimal points', () => {
			const config = { decimalSeparator: ',' };

			expect( maybeModifyDecimal( '123', config ) ).toBe( '123' );
			expect( maybeModifyDecimal( '0', config ) ).toBe( '0' );
			expect( maybeModifyDecimal( '999', config ) ).toBe( '999' );
			expect( maybeModifyDecimal( 'abc', config ) ).toBe( 'abc' );
		} );
	} );

	describe( 'Formula detection and non-modification', () => {
		test( 'should not modify values containing brackets', () => {
			const config = { decimalSeparator: ',' };

			expect( maybeModifyDecimal( '[qty]', config ) ).toBe( '[qty]' );
			expect( maybeModifyDecimal( '[qty] * 2.5', config ) ).toBe( '[qty] * 2.5' );
			expect( maybeModifyDecimal( '10.99 + [cost]', config ) ).toBe( '10.99 + [cost]' );
			expect( maybeModifyDecimal( '[weight] / 2.5', config ) ).toBe( '[weight] / 2.5' );
		} );

		test( 'should not modify values containing parentheses', () => {
			const config = { decimalSeparator: ',' };

			expect( maybeModifyDecimal( '(123.45)', config ) ).toBe( '(123.45)' );
			expect( maybeModifyDecimal( '((123.45))', config ) ).toBe( '((123.45))' );
			expect( maybeModifyDecimal( '([qty] * 2.5)', config ) ).toBe( '([qty] * 2.5)' );
		} );

		test( 'should not modify values containing mathematical operators', () => {
			const config = { decimalSeparator: ',' };

			expect( maybeModifyDecimal( '123.45 * 2', config ) ).toBe( '123.45 * 2' );
			expect( maybeModifyDecimal( '10.99 + 5.50', config ) ).toBe( '10.99 + 5.50' );
			expect( maybeModifyDecimal( '100.00 - 25.50', config ) ).toBe( '100.00 - 25.50' );
			expect( maybeModifyDecimal( '50.00 / 2.5', config ) ).toBe( '50.00 / 2.5' );
		} );

		test( 'should not modify values containing quotes', () => {
			const config = { decimalSeparator: ',' };

			expect( maybeModifyDecimal( '"123.45"', config ) ).toBe( '"123.45"' );
			expect( maybeModifyDecimal( "'123.45'", config ) ).toBe( "'123.45'" );
		} );

		test( 'should not modify values containing letters', () => {
			const config = { decimalSeparator: ',' };

			expect( maybeModifyDecimal( 'abc123.45', config ) ).toBe( 'abc123.45' );
			expect( maybeModifyDecimal( '123.45def', config ) ).toBe( '123.45def' );
			expect( maybeModifyDecimal( 'price123.45', config ) ).toBe( 'price123.45' );
		} );

		test( 'should not modify complex formulas', () => {
			const config = { decimalSeparator: ',' };

			expect( maybeModifyDecimal( '([qty] * 2.5) + ([weight] * 0.1)', config ) ).toBe( '([qty] * 2.5) + ([weight] * 0.1)' );
			expect( maybeModifyDecimal( '(([qty] * 2.5) + ([weight] * 0.1)) * 1.08', config ) )
				.toBe( '(([qty] * 2.5) + ([weight] * 0.1)) * 1.08' );
			expect( maybeModifyDecimal( '[qty] * 2.5 + [weight] * 0.15 + 5.50', config ) ).toBe( '[qty] * 2.5 + [weight] * 0.15 + 5.50' );
		} );
	} );

	describe( 'Edge cases and special scenarios', () => {
		test( 'should handle empty config object', () => {
			const config = {};

			expect( maybeModifyDecimal( '123.45', config ) ).toBe( '123.45' );
		} );

		test( 'should handle config with undefined decimalSeparator', () => {
			const config = { decimalSeparator: undefined };

			expect( maybeModifyDecimal( '123.45', config ) ).toBe( '123.45' );
		} );

		test( 'should handle whitespace in values', () => {
			const config = { decimalSeparator: ',' };

			expect( maybeModifyDecimal( ' 123.45 ', config ) ).toBe( ' 123,45 ' );
			expect( maybeModifyDecimal( '\t123.45\t', config ) ).toBe( '\t123,45\t' );
		} );

		test( 'should handle special characters in decimal separator', () => {
			const config = { decimalSeparator: '€' };

			expect( maybeModifyDecimal( '123.45', config ) ).toBe( '123€45' );
		} );
	} );
} );
