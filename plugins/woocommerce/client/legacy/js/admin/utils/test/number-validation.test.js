/**
 * Test for isValidFormattedNumber method from utils/number-validation.js
 */

// Import the utility function
const { isValidFormattedNumber } = require('../number-validation');

describe( 'Number Validation Utils - isValidFormattedNumber', () => {

	test( 'should import function from utility file', () => {
		expect( typeof isValidFormattedNumber ).toBe( 'function' );
		expect( isValidFormattedNumber.length ).toBe( 2 ); // expects 2 parameters: value and config
	} );

	describe( 'Basic number validation', () => {
		const config = {
			decimalSeparator: '.',
			thousandSeparator: ','
		};

		test( 'should return false for empty or invalid input', () => {
			expect( isValidFormattedNumber( '', config ) ).toBe( false );
			expect( isValidFormattedNumber( null, config ) ).toBe( false );
			expect( isValidFormattedNumber( undefined, config ) ).toBe( false );
			expect( isValidFormattedNumber( 123, config ) ).toBe( false ); // not a string
			expect( isValidFormattedNumber( '123', null ) ).toBe( false ); // no config
		} );

		test( 'should validate simple integers', () => {
			expect( isValidFormattedNumber( '123', config ) ).toBe( true );
			expect( isValidFormattedNumber( '0', config ) ).toBe( true );
			expect( isValidFormattedNumber( '999', config ) ).toBe( true );
		} );

		test( 'should validate decimal numbers', () => {
			expect( isValidFormattedNumber( '123.45', config ) ).toBe( true );
			expect( isValidFormattedNumber( '0.99', config ) ).toBe( true );
			expect( isValidFormattedNumber( '999.00', config ) ).toBe( true );
		} );

		test( 'should validate numbers with thousand separators', () => {
			expect( isValidFormattedNumber( '1,234', config ) ).toBe( true );
			expect( isValidFormattedNumber( '1,234,567', config ) ).toBe( true );
			expect( isValidFormattedNumber( '1,234.56', config ) ).toBe( true );
		} );

		test( 'should reject invalid formats', () => {
			expect( isValidFormattedNumber( '123.45.67', config ) ).toBe( false ); // multiple decimal points
			expect( isValidFormattedNumber( '1,23', config ) ).toBe( false ); // incorrect thousand grouping
			expect( isValidFormattedNumber( ',123', config ) ).toBe( false ); // starts with separator
			expect( isValidFormattedNumber( '123,', config ) ).toBe( false ); // ends with separator
			expect( isValidFormattedNumber( 'abc', config ) ).toBe( false ); // letters
		} );
	} );

	describe( 'Formula validation - US format', () => {
		const config = {
			decimalSeparator: '.',
			thousandSeparator: ','
		};

		test( 'should accept formulas without decimals', () => {
			expect( isValidFormattedNumber( '[qty] * 2', config ) ).toBe( true );
			expect( isValidFormattedNumber( '10 + [cost]', config ) ).toBe( true );
			expect( isValidFormattedNumber( '[weight] / 2', config ) ).toBe( true );
			expect( isValidFormattedNumber( '([qty] * 5)', config ) ).toBe( true );
		} );

		test( 'should validate formulas with correct decimal separators', () => {
			expect( isValidFormattedNumber( '[qty] * 2.5', config ) ).toBe( true );
			expect( isValidFormattedNumber( '10.99 + [cost]', config ) ).toBe( true );
			expect( isValidFormattedNumber( '[weight] / 2.5', config ) ).toBe( true );
		} );

		test( 'should reject formulas with incorrect decimal separators', () => {
			expect( isValidFormattedNumber( '[qty] * 2,5', config ) ).toBe( false );
			expect( isValidFormattedNumber( '10,99 + [cost]', config ) ).toBe( false );
		} );

		test( 'should validate formulas with different variable names', () => {
			expect( isValidFormattedNumber( '[quantity] * 2.5', config ) ).toBe( true );
			expect( isValidFormattedNumber( '[price] + [shipping_cost]', config ) ).toBe( true );
			expect( isValidFormattedNumber( '[base_rate] * [distance]', config ) ).toBe( true );
			expect( isValidFormattedNumber( '([item_weight] / 1000) * 5.50', config ) ).toBe( true );
		} );


		test( 'should validate nested parentheses in formulas', () => {
			expect( isValidFormattedNumber( '(([qty] * 2.5) + ([weight] * 0.1)) * 1.08', config ) ).toBe( true );
			expect( isValidFormattedNumber( '((10.00 + [base]) * [multiplier]) - [discount]', config ) ).toBe( true );
		} );

		test( 'should validate formulas with multiple decimal numbers', () => {
			expect( isValidFormattedNumber( '[qty] * 2.5 + [weight] * 0.15 + 5.50', config ) ).toBe( true );
			expect( isValidFormattedNumber( '([price] * 1.08) + ([shipping] * 1.15)', config ) ).toBe( true );
		} );
	} );

	describe( 'Formula validation with different locale configurations', () => {
		describe( 'European format (comma as decimal, space as thousand)', () => {
			const euroConfig = {
				decimalSeparator: ',',
				thousandSeparator: ' '
			};

			test( 'should validate basic formulas with European decimal format', () => {
				expect( isValidFormattedNumber( '[qty] * 2,5', euroConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '10,99 + [cost]', euroConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '[weight] / 2,75', euroConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '([qty] * 15,5)', euroConfig ) ).toBe( true );
			} );

			test( 'should reject formulas with incorrect decimal separator', () => {
				expect( isValidFormattedNumber( '[qty] * 2.5', euroConfig ) ).toBe( false );
				expect( isValidFormattedNumber( '10.99 + [cost]', euroConfig ) ).toBe( false );
				expect( isValidFormattedNumber( '[weight] / 2.75', euroConfig ) ).toBe( false );
			} );


			test( 'should validate mixed formula operations', () => {
				expect( isValidFormattedNumber( '[qty] * 2,5 + 10,75', euroConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '([weight] / 2,5) + ([qty] * 1,99)', euroConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '100,00 - [discount] + ([qty] * 5,50)', euroConfig ) ).toBe( true );
			} );

			test( 'should reject mixed decimal separators in formulas', () => {
				expect( isValidFormattedNumber( '[qty] * 2,5 + 10.75', euroConfig ) ).toBe( false );
				expect( isValidFormattedNumber( '([weight] / 2.5) + ([qty] * 1,99)', euroConfig ) ).toBe( false );
			} );
		} );

		describe( 'German format (comma as decimal, dot as thousand)', () => {
			const germanConfig = {
				decimalSeparator: ',',
				thousandSeparator: '.'
			};

			test( 'should validate basic formulas with German decimal format', () => {
				expect( isValidFormattedNumber( '[qty] * 2,5', germanConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '10,99 + [cost]', germanConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '[weight] / 2,75', germanConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '([qty] * 15,5)', germanConfig ) ).toBe( true );
			} );

			test( 'should validate complex German format formulas', () => {
				expect( isValidFormattedNumber( '[qty] * 2,5 + 10,75', germanConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '([weight] / 2,5) + ([qty] * 1,99)', germanConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '([qty] * 5,50)', germanConfig ) ).toBe( true );
			} );

			test( 'should reject US decimal format in German locale formulas', () => {
				expect( isValidFormattedNumber( '[qty] * 2.5', germanConfig ) ).toBe( false );
				expect( isValidFormattedNumber( '1,000.50 + [cost]', germanConfig, true ) ).toBe( false );
			} );
		} );

		describe( 'Swiss format (comma as decimal, apostrophe as thousand)', () => {
			const swissConfig = {
				decimalSeparator: ',',
				thousandSeparator: '\''
			};

			test( 'should validate Swiss format formulas', () => {
				expect( isValidFormattedNumber( '[qty] * 2,5', swissConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '10,99 + [cost]', swissConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '[weight] / 2,75', swissConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '([qty] * 1,5)', swissConfig ) ).toBe( true );
			} );

			test( 'should validate complex Swiss format formulas', () => {
				expect( isValidFormattedNumber( '[qty] * 2,5 + 1,75', swissConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '([weight] / 2,5) + ([qty] * 1,99)', swissConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '100,00 - [discount]', swissConfig ) ).toBe( true );
			} );
		} );

		describe( 'US/UK format (dot as decimal, comma as thousand)', () => {
			const usConfig = {
				decimalSeparator: '.',
				thousandSeparator: ','
			};

			test( 'should validate US format operations with decimals', () => {
				expect( isValidFormattedNumber( '[qty] * 2.5 + 10.75', usConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '([weight] / 2.5) + ([qty] * 1.99)', usConfig ) ).toBe( true );
				expect( isValidFormattedNumber( '([cost] * 1.08) + 15.50', usConfig ) ).toBe( true );
			} );

			test( 'should reject European decimal format in US locale formulas', () => {
				expect( isValidFormattedNumber( '[qty] * 2,5', usConfig ) ).toBe( false );
			} );
		} );

	} );

	describe( 'Different locale configurations - Basic number validation', () => {
		test( 'should work with European format (comma as decimal, space as thousand)', () => {
			const euroConfig = {
				decimalSeparator: ',',
				thousandSeparator: ' '
			};

			expect( isValidFormattedNumber( '123,45', euroConfig ) ).toBe( true );
			expect( isValidFormattedNumber( '1 234', euroConfig ) ).toBe( true );
			expect( isValidFormattedNumber( '1 234,56', euroConfig ) ).toBe( true );

			expect( isValidFormattedNumber( '123.45', euroConfig ) ).toBe( false );
		} );

		test( 'should work with German format (comma as decimal, dot as thousand)', () => {
			const germanConfig = {
				decimalSeparator: ',',
				thousandSeparator: '.'
			};

			expect( isValidFormattedNumber( '123,45', germanConfig ) ).toBe( true );
			expect( isValidFormattedNumber( '1.234', germanConfig ) ).toBe( true );
			expect( isValidFormattedNumber( '1.234,56', germanConfig ) ).toBe( true );
		} );
	} );

	describe( 'Edge cases and whitespace', () => {
		const config = {
			decimalSeparator: '.',
			thousandSeparator: ','
		};

		test( 'should handle whitespace', () => {
			expect( isValidFormattedNumber( ' 123 ', config ) ).toBe( true );
			expect( isValidFormattedNumber( ' 123.45 ', config ) ).toBe( true );
			expect( isValidFormattedNumber( ' 1,234.56 ', config ) ).toBe( true );
		} );

		test( 'should handle special characters in separators', () => {
			const specialConfig = {
				decimalSeparator: '.',
				thousandSeparator: '\''  // Swiss format uses apostrophe
			};

			expect( isValidFormattedNumber( '1\'234.56', specialConfig ) ).toBe( true );
			expect( isValidFormattedNumber( '1\'234\'567.89', specialConfig ) ).toBe( true );
		} );

		test( 'should handle shortcodes with spaces', () => {
			expect( isValidFormattedNumber( '[ qty ]', config ) ).toBe( true );
			expect( isValidFormattedNumber( '[ cost ] + 10', config ) ).toBe( true );
			expect( isValidFormattedNumber( '([ weight ] / 2)', config ) ).toBe( true );
		} );

		test( 'should handle characters used as separators', () => {
			const customConfig = {
				decimalSeparator: 'd',
				thousandSeparator: 't'
			};

			expect( isValidFormattedNumber( '1t234d56', customConfig ) ).toBe( true );
			expect( isValidFormattedNumber( '1t234t56', customConfig ) ).toBe( false ); // incorrect format
			expect( isValidFormattedNumber( '1t234d567d89', customConfig ) ).toBe( false ); // incorrect format
		} );

		test( 'should handle numbers with leading zeros', () => {
			expect( isValidFormattedNumber( '00123', config ) ).toBe( true );
			expect( isValidFormattedNumber( '0001.23', config ) ).toBe( true );
			expect( isValidFormattedNumber( '1,234.00', config ) ).toBe( true );
			expect( isValidFormattedNumber( '01,234.56', config ) ).toBe( true );
		} );

		test( 'should handle invalid decimal separators in formulas', () => {
			expect( isValidFormattedNumber( '[qty] * 2,5', config ) ).toBe( false );
			expect( isValidFormattedNumber( '10,99 + [cost]', config ) ).toBe( false );
			expect( isValidFormattedNumber( '[weight] / 2\'75', config ) ).toBe( false );
			expect( isValidFormattedNumber( '([qty] * 15 5)', config ) ).toBe( false );
		} );
	} );
} );
