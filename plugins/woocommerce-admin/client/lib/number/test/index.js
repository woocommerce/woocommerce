/** @format */
/**
 * Internal dependencies
 */
import { numberFormat } from '../index';

describe( 'numberFormat', () => {
	it( 'should default to precision=2 decimal=. thousands=,', () => {
		expect( numberFormat( 1000 ) ).toBe( '1,000.00' );
	} );

	it( 'should return an empty string if no argument is passed', () => {
		expect( numberFormat() ).toBe( '' );
	} );

	it( 'should accept a string', () => {
		expect( numberFormat( '10000' ) ).toBe( '10,000.00' );
	} );

	it( 'uses store currency settings, not locale', () => {
		global.wcSettings.siteLocale = 'en-US';
		global.wcSettings.currency.precision = 3;
		global.wcSettings.currency.decimal_separator = ',';
		global.wcSettings.currency.thousand_separator = '.';

		expect( numberFormat( '12345.6789' ) ).toBe( '12.345,679' );
	} );
} );
