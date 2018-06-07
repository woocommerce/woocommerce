/** @format */
/**
 * Internal dependencies
 */
import { getCurrencyFormatDecimal, getCurrencyFormatString } from '../index';

describe( 'getCurrencyFormatDecimal', () => {
	it( 'should round a number to 2 decimal places in USD', () => {
		expect( getCurrencyFormatDecimal( 9.49258, 'USD' ) ).toBe( 9.49 );
		expect( getCurrencyFormatDecimal( 30, 'USD' ) ).toBe( 30 );
		expect( getCurrencyFormatDecimal( 3.0002, 'USD' ) ).toBe( 3 );
	} );

	// @TODO: Add these tests back once we support multiple currencies
	// it( 'should round a number to 2 decimal places in GBP', () => {
	// 	expect( getCurrencyFormatDecimal( 8.9272, 'GBP' ) ).toBe( 8.93 );
	// 	expect( getCurrencyFormatDecimal( 11, 'GBP' ) ).toBe( 11 );
	// 	expect( getCurrencyFormatDecimal( 7.0002, 'GBP' ) ).toBe( 7 );
	// } );

	// it( 'should round a number to 0 decimal places in JPY', () => {
	// 	expect( getCurrencyFormatDecimal( 1239.88, 'JPY' ) ).toBe( 1240 );
	// 	expect( getCurrencyFormatDecimal( 1500, 'JPY' ) ).toBe( 1500 );
	// 	expect( getCurrencyFormatDecimal( 33715.02, 'JPY' ) ).toBe( 33715 );
	// } );

	it( 'should correctly convert and round a string', () => {
		expect( getCurrencyFormatDecimal( '19.80', 'USD' ) ).toBe( 19.8 );
	} );

	it( "should return 0 when given an input that isn't a number", () => {
		expect( getCurrencyFormatDecimal( 'abc', 'USD' ) ).toBe( 0 );
		expect( getCurrencyFormatDecimal( false, 'USD' ) ).toBe( 0 );
		expect( getCurrencyFormatDecimal( null, 'USD' ) ).toBe( 0 );
	} );
} );

describe( 'getCurrencyFormatString', () => {
	it( 'should round a number to 2 decimal places in USD', () => {
		expect( getCurrencyFormatString( 9.49258, 'USD' ) ).toBe( '9.49' );
		expect( getCurrencyFormatString( 30, 'USD' ) ).toBe( '30.00' );
		expect( getCurrencyFormatString( 3.0002, 'USD' ) ).toBe( '3.00' );
	} );

	// @TODO: Add these tests back once we support multiple currencies
	// it( 'should round a number to 2 decimal places in GBP', () => {
	// 	expect( getCurrencyFormatString( 8.9272, 'GBP' ) ).toBe( '8.93' );
	// 	expect( getCurrencyFormatString( 11, 'GBP' ) ).toBe( '11.00' );
	// 	expect( getCurrencyFormatString( 7.0002, 'GBP' ) ).toBe( '7.00' );
	// } );

	// it( 'should round a number to 0 decimal places in JPY', () => {
	// 	expect( getCurrencyFormatString( 1239.88, 'JPY' ) ).toBe( '1240' );
	// 	expect( getCurrencyFormatString( 1500, 'JPY' ) ).toBe( '1500' );
	// 	expect( getCurrencyFormatString( 33715.02, 'JPY' ) ).toBe( '33715' );
	// } );

	it( 'should correctly convert and round a string', () => {
		expect( getCurrencyFormatString( '19.80', 'USD' ) ).toBe( '19.80' );
	} );

	it( "should return empty string when given an input that isn't a number", () => {
		expect( getCurrencyFormatString( 'abc', 'USD' ) ).toBe( '' );
		expect( getCurrencyFormatString( false, 'USD' ) ).toBe( '' );
		expect( getCurrencyFormatString( null, 'USD' ) ).toBe( '' );
	} );
} );
