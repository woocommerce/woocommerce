/**
 * External dependencies
 */
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import { safeNumberFormat } from '../currency-context';

const config = {
	code: 'USD',
	symbol: '$',
	symbolPosition: 'left',
	decimalSeparator: '.',
	priceFormat: '%1$s%2$s',
	thousandSeparator: ',',
	precision: 2,
};

describe( 'CurrencyContext', () => {
	it( 'should format a number input correctly', () => {
		expect( safeNumberFormat( config, 1234 ) ).toBe(
			numberFormat( config, 1234 )
		);
	} );

	it( 'should format string numbers correctly', () => {
		expect( safeNumberFormat( config, '123456789' ) ).toBe(
			'123,456,789.00'
		);
	} );

	it( 'should format with arbitrary thousands separator placements', () => {
		expect( safeNumberFormat( config, '12,34,56,789' ) ).toBe(
			'123,456,789.00'
		);
	} );

	it( 'should format with swapped decimal and thousand separator', () => {
		const customConfig = {
			...config,
			decimalSeparator: ',',
			thousandSeparator: '.',
		};
		expect( safeNumberFormat( customConfig, '123.456.789' ) ).toBe(
			'123.456.789,00'
		);
	} );

	it( 'should not format numbers when text is included', () => {
		expect( safeNumberFormat( config, 'Value 1234' ) ).toBe( 'Value 1234' );
	} );

	it( 'should not format when formula is included', () => {
		expect(
			safeNumberFormat( config, '50 + (([qty]*2+1)(5*10))(1)' )
		).toBe( '50 + (([qty]*2+1)(5*10))(1)' );
	} );

	it( 'should return the original string for non-numeric inputs', () => {
		expect( safeNumberFormat( config, 'Hello World' ) ).toBe(
			'Hello World'
		);
	} );

	it( 'should return the original input for non-string, non-number inputs', () => {
		const input = { some: 'object' };
		expect( safeNumberFormat( config, input ) ).toBe( input );
	} );
} );
