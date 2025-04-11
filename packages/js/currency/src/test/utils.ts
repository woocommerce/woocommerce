/**
 * External dependencies
 */
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import {
	localiseMonetaryValue,
	unformatLocalisedMonetaryValue,
} from '../utils';

const config = {
	code: 'USD',
	symbol: '$',
	symbolPosition: 'left',
	decimalSeparator: '.',
	priceFormat: '%1$s%2$s',
	thousandSeparator: ',',
	precision: 2,
};

describe( 'localiseMonetaryValue', () => {
	it( 'should format a number input correctly', () => {
		expect( localiseMonetaryValue( config, 1234 ) ).toBe(
			numberFormat( config, 1234 )
		);
	} );

	it( 'should format string numbers correctly', () => {
		expect( localiseMonetaryValue( config, '123456789' ) ).toBe(
			'123,456,789.00'
		);
	} );

	it( 'should format with swapped decimal and thousand separator', () => {
		const customConfig = {
			...config,
			decimalSeparator: ',',
			thousandSeparator: '.',
		};
		expect( localiseMonetaryValue( customConfig, '123.456.789' ) ).toBe(
			'123.456.789,00'
		);
	} );

	it( 'should not format incorrectly formatted numbers according to current config', () => {
		const customConfig = {
			...config,
			decimalSeparator: ',',
			thousandSeparator: '.',
		};
		expect( localiseMonetaryValue( customConfig, '7.5' ) ).toBe( '7.5' );
	} );

	it( 'should format number according to precision', () => {
		const customConfig = {
			...config,
			precision: 5,
		};
		expect( localiseMonetaryValue( customConfig, '123.4' ) ).toBe(
			'123.40000'
		);
	} );

	it( 'should format number and trim leading and trailing spaces', () => {
		expect( localiseMonetaryValue( config, ' 1234 ' ) ).toBe( '1,234.00' );
	} );

	it( 'should not format numbers when text is included', () => {
		expect( localiseMonetaryValue( config, 'Value 1234' ) ).toBe(
			'Value 1234'
		);
	} );

	it( 'should not format when formula is included', () => {
		expect(
			localiseMonetaryValue( config, '50 + (([qty]*2+1)(5*10))(1)' )
		).toBe( '50 + (([qty]*2+1)(5*10))(1)' );
	} );

	it( 'should return the original input for non-string, non-number inputs', () => {
		const input = { some: 'object' };
		expect( localiseMonetaryValue( config, input ) ).toBe( input );
	} );
} );

describe( 'unformatLocalisedMonetaryValue', () => {
	// Basic cases
	it( 'should handle simple integer', () => {
		expect( unformatLocalisedMonetaryValue( config, '1234' ) ).toBe( 1234 );
	} );

	it( 'should handle decimal number', () => {
		expect( unformatLocalisedMonetaryValue( config, '12.34' ) ).toBe(
			12.34
		);
	} );

	it( 'should handle zero value', () => {
		expect( unformatLocalisedMonetaryValue( config, '0' ) ).toBe( 0 );
	} );

	it( 'should handle zero with decimal', () => {
		expect( unformatLocalisedMonetaryValue( config, '0.00' ) ).toBe( 0 );
	} );

	// Thousand separators
	it( 'should handle number with thousand separator', () => {
		expect( unformatLocalisedMonetaryValue( config, '1,234.56' ) ).toBe(
			1234.56
		);
	} );

	it( 'should handle multiple thousand separators', () => {
		expect( unformatLocalisedMonetaryValue( config, '1,234,567.89' ) ).toBe(
			1234567.89
		);
	} );

	// European formats
	it( 'should handle european format', () => {
		const eurConfig = {
			...config,
			decimalSeparator: ',',
			thousandSeparator: '.',
		};
		expect( unformatLocalisedMonetaryValue( eurConfig, '1.234,56' ) ).toBe(
			1234.56
		);
	} );

	// Special thousand separators
	it( 'should handle space as thousand separator', () => {
		const frConfig = {
			...config,
			thousandSeparator: ' ',
			decimalSeparator: ',',
		};
		expect( unformatLocalisedMonetaryValue( frConfig, '1 234,56' ) ).toBe(
			1234.56
		);
	} );

	it( 'should handle multiple spaces as thousand separators', () => {
		const frConfig = {
			...config,
			thousandSeparator: ' ',
			decimalSeparator: ',',
		};
		expect(
			unformatLocalisedMonetaryValue( frConfig, '1 234 567,89' )
		).toBe( 1234567.89 );
	} );

	it( 'should handle apostrophe as thousand separator', () => {
		const chConfig = {
			...config,
			thousandSeparator: "'",
		};
		expect( unformatLocalisedMonetaryValue( chConfig, "1'234.56" ) ).toBe(
			1234.56
		);
	} );

	it( 'should handle multiple apostrophes as thousand separators', () => {
		const chConfig = {
			...config,
			thousandSeparator: "'",
		};
		expect(
			unformatLocalisedMonetaryValue( chConfig, "1'234'567.89" )
		).toBe( 1234567.89 );
	} );

	// Invalid separator placements
	it( 'should handle invalid multiple comma placements', () => {
		expect( unformatLocalisedMonetaryValue( config, '1,23,4.56' ) ).toBe(
			1234.56
		);
	} );

	it( 'should handle invalid multiple space placements', () => {
		const frConfig = {
			...config,
			thousandSeparator: ' ',
			decimalSeparator: ',',
		};
		expect( unformatLocalisedMonetaryValue( frConfig, '1 23 4,56' ) ).toBe(
			1234.56
		);
	} );

	it( 'should handle invalid multiple apostrophe placements', () => {
		const chConfig = {
			...config,
			thousandSeparator: "'",
		};
		expect( unformatLocalisedMonetaryValue( chConfig, "1'23'4.56" ) ).toBe(
			1234.56
		);
	} );

	// Error cases
	it( 'should throw error for undefined input', () => {
		expect( () =>
			unformatLocalisedMonetaryValue( config, undefined )
		).toThrow( 'Input value is undefined' );
	} );

	it( 'should throw error for null input', () => {
		expect( () => unformatLocalisedMonetaryValue( config, null ) ).toThrow(
			'Input value is undefined'
		);
	} );

	it( 'should throw error for empty string', () => {
		expect( () => unformatLocalisedMonetaryValue( config, '' ) ).toThrow(
			'Input value is undefined'
		);
	} );

	it( 'should throw error for non-numeric strings', () => {
		expect( () => unformatLocalisedMonetaryValue( config, 'abc' ) ).toThrow(
			'Input value contains non-numeric characters and is not a formula'
		);
	} );

	it( 'should throw error for formula inputs', () => {
		expect( () =>
			unformatLocalisedMonetaryValue( config, '[qty] * 2' )
		).toThrow( 'Input value contains formula' );
	} );

	// Mixed separator errors
	it( 'should throw error when using wrong thousand separator for locale', () => {
		const chConfig = {
			...config,
			thousandSeparator: "'",
		};
		expect( () =>
			unformatLocalisedMonetaryValue( chConfig, '1,234.56' )
		).toThrow(
			'Input value contains non-numeric characters and is not a formula'
		);
	} );

	// Decimal separator validation
	it( 'should throw error for multiple decimal separators', () => {
		expect( () =>
			unformatLocalisedMonetaryValue( config, '1,234.56.78' )
		).toThrow( 'Invalid decimal separator' );
	} );

	it( 'should throw error for decimal separator before thousand separator', () => {
		expect( () =>
			unformatLocalisedMonetaryValue( config, '1.234,56' )
		).toThrow( 'Invalid decimal separator' );
	} );

	// Whitespace handling
	it( 'should handle whitespace around the number', () => {
		expect( unformatLocalisedMonetaryValue( config, ' 1,234.56 ' ) ).toBe(
			1234.56
		);
	} );

	// HTML and special characters
	it( 'should throw error for HTML in input', () => {
		expect( () =>
			unformatLocalisedMonetaryValue( config, '<b>1,234.56</b>' )
		).toThrow(
			'Input value contains non-numeric characters and is not a formula'
		);
	} );

	it( 'should throw error for currency symbols in input', () => {
		expect( () =>
			unformatLocalisedMonetaryValue( config, '$1,234.56' )
		).toThrow(
			'Input value contains non-numeric characters and is not a formula'
		);
	} );
} );
