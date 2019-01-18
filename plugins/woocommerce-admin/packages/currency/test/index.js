/** @format */
/**
 * Internal dependencies
 */
import { formatCurrency, getCurrencyFormatDecimal, getCurrencyFormatString } from '../src';

describe( 'formatCurrency', () => {
	it( 'should default to wcSettings or USD when currency not passed in', () => {
		expect( formatCurrency( 9.99 ) ).toBe( '$9.99' );
		expect( formatCurrency( 30 ) ).toBe( '$30.00' );
	} );

	it( 'should uses store currency settings, not locale-based', () => {
		global.wcSettings.currency.code = 'JPY';
		global.wcSettings.currency.precision = 3;
		global.wcSettings.currency.decimal_separator = ',';
		global.wcSettings.currency.thousand_separator = '.';
		global.wcSettings.currency.price_format = '%2$s%1$s';

		expect( formatCurrency( 9.49258, '¥' ) ).toBe( '9,493¥' );
		expect( formatCurrency( 3000, '¥' ) ).toBe( '3.000,000¥' );
		expect( formatCurrency( 3.0002, '¥' ) ).toBe( '3,000¥' );
	} );

	it( "should return empty string when given an input that isn't a number", () => {
		expect( formatCurrency( 'abc' ) ).toBe( '' );
		expect( formatCurrency( false ) ).toBe( '' );
		expect( formatCurrency( null ) ).toBe( '' );
	} );
} );

describe( 'getCurrencyFormatDecimal', () => {
	it( 'should round a number to 2 decimal places in USD', () => {
		global.wcSettings.currency.precision = 2;
		expect( getCurrencyFormatDecimal( 9.49258 ) ).toBe( 9.49 );
		expect( getCurrencyFormatDecimal( 30 ) ).toBe( 30 );
		expect( getCurrencyFormatDecimal( 3.0002 ) ).toBe( 3 );
	} );

	it( 'should round a number to 0 decimal places in JPY', () => {
		global.wcSettings.currency.precision = 0;
		expect( getCurrencyFormatDecimal( 1239.88 ) ).toBe( 1240 );
		expect( getCurrencyFormatDecimal( 1500 ) ).toBe( 1500 );
		expect( getCurrencyFormatDecimal( 33715.02 ) ).toBe( 33715 );
	} );

	it( 'should correctly convert and round a string', () => {
		global.wcSettings.currency.precision = 2;
		expect( getCurrencyFormatDecimal( '19.80' ) ).toBe( 19.8 );
	} );

	it( 'should default to a precision of 2 if none set', () => {
		delete global.wcSettings.currency.precision;
		expect( getCurrencyFormatDecimal( 59.282 ) ).toBe( 59.28 );
	} );

	it( "should return 0 when given an input that isn't a number", () => {
		global.wcSettings.currency.precision = 2;
		expect( getCurrencyFormatDecimal( 'abc' ) ).toBe( 0 );
		expect( getCurrencyFormatDecimal( false ) ).toBe( 0 );
		expect( getCurrencyFormatDecimal( null ) ).toBe( 0 );
	} );
} );

describe( 'getCurrencyFormatString', () => {
	it( 'should round a number to 2 decimal places in USD', () => {
		global.wcSettings.currency.precision = 2;
		expect( getCurrencyFormatString( 9.49258 ) ).toBe( '9.49' );
		expect( getCurrencyFormatString( 30 ) ).toBe( '30.00' );
		expect( getCurrencyFormatString( 3.0002 ) ).toBe( '3.00' );
	} );

	it( 'should round a number to 0 decimal places in JPY', () => {
		global.wcSettings.currency.precision = 0;
		expect( getCurrencyFormatString( 1239.88 ) ).toBe( '1240' );
		expect( getCurrencyFormatString( 1500 ) ).toBe( '1500' );
		expect( getCurrencyFormatString( 33715.02 ) ).toBe( '33715' );
	} );

	it( 'should correctly convert and round a string', () => {
		global.wcSettings.currency.precision = 2;
		expect( getCurrencyFormatString( '19.80' ) ).toBe( '19.80' );
	} );

	it( 'should default to a precision of 2 if none set', () => {
		delete global.wcSettings.currency.precision;
		expect( getCurrencyFormatString( '59.282' ) ).toBe( '59.28' );
	} );

	it( "should return empty string when given an input that isn't a number", () => {
		global.wcSettings.currency.precision = 2;
		expect( getCurrencyFormatString( 'abc' ) ).toBe( '' );
		expect( getCurrencyFormatString( false ) ).toBe( '' );
		expect( getCurrencyFormatString( null ) ).toBe( '' );
	} );
} );
