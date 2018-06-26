/** @format */
/**
 * Internal dependencies
 */
import { formatCurrency, getCurrencyFormatDecimal, getCurrencyFormatString } from '../index';

describe( 'formatCurrency', () => {
	it( 'should round a number to 2 decimal places in USD', () => {
		expect( formatCurrency( 9.49258, 'USD' ) ).toBe( '$9.49' );
		expect( formatCurrency( 30, 'USD' ) ).toBe( '$30.00' );
		expect( formatCurrency( 3.0002, 'USD' ) ).toBe( '$3.00' );
	} );

	it( 'should round a number to 2 decimal places in GBP', () => {
		expect( formatCurrency( 8.9272, 'GBP' ) ).toBe( '£8.93' );
		expect( formatCurrency( 11, 'GBP' ) ).toBe( '£11.00' );
		expect( formatCurrency( 7.0002, 'GBP' ) ).toBe( '£7.00' );
	} );

	it( 'should round a number to 0 decimal places in JPY', () => {
		expect( formatCurrency( 1239.88, 'JPY' ) ).toBe( '¥1,240' );
		expect( formatCurrency( 1500, 'JPY' ) ).toBe( '¥1,500' );
		expect( formatCurrency( 33715.02, 'JPY' ) ).toBe( '¥33,715' );
	} );

	it( 'should correctly convert and round a string', () => {
		expect( formatCurrency( '19.80', 'USD' ) ).toBe( '$19.80' );
	} );

	it( "should return empty string when given an input that isn't a number", () => {
		expect( formatCurrency( 'abc', 'USD' ) ).toBe( '' );
		expect( formatCurrency( false, 'USD' ) ).toBe( '' );
		expect( formatCurrency( null, 'USD' ) ).toBe( '' );
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
