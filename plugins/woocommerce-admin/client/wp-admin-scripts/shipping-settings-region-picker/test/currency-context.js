/**
 * External dependencies
 */
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import { numberFormatWithShippingFormula } from '../currency-context';

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
		expect( numberFormatWithShippingFormula( config, 1234 ) ).toBe(
			numberFormat( config, 1234 )
		);
	} );

	it( 'should format numbers within a string correctly', () => {
		expect(
			numberFormatWithShippingFormula( config, 'Value is 123456789' )
		).toBe( `Value is 123,456,789.00` );
	} );

	it( 'should not format numbers within square brackets', () => {
		expect(
			numberFormatWithShippingFormula( config, 'Value [1234] is inside' )
		).toBe( 'Value [1234] is inside' );
	} );

	it( 'should format all numbers inside parenthesis', () => {
		expect(
			numberFormatWithShippingFormula(
				config,
				'50 + (([qty]*2+1)(5*10))(1)'
			)
		).toBe( '50.00 + (([qty]*2.00+1.00)(5.00*10.00))(1.00)' );
	} );

	it( 'should format numbers and ignore shortcodes', () => {
		expect(
			numberFormatWithShippingFormula(
				config,
				'10*[qty]+(1+(2/[cost]))+([fee percent="10" max_fee="8.33"])'
			)
		).toBe(
			'10.00*[qty]+(1.00+(2.00/[cost]))+([fee percent="10" max_fee="8.33"])'
		);
	} );

	it( 'should return the original string for non-numeric inputs', () => {
		expect( numberFormatWithShippingFormula( config, 'Hello World' ) ).toBe(
			'Hello World'
		);
	} );

	it( 'should return the original input for non-string, non-number inputs', () => {
		const input = { some: 'object' };
		expect( numberFormatWithShippingFormula( config, input ) ).toBe(
			input
		);
	} );
} );
