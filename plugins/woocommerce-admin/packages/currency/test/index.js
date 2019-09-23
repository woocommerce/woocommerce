/** @format */
/**
 * Internal dependencies
 */
import {
	formatCurrency,
	getCurrencyFormatDecimal,
	getCurrencyFormatString,
} from '../src';

/**
 * WooCommerce dependencies
 * Note: setCurrencyProp doesn't exist on the module alias, it's used for mocking
 * values.
 */
import { setCurrencyProp, resetMock } from '@woocommerce/wc-admin-settings';

beforeEach( () => {
	resetMock();
} );

jest.mock( '@woocommerce/wc-admin-settings', () => {
	let mockedCurrency = jest.requireActual( '@woocommerce/wc-admin-settings' ).CURRENCY;
	const originalCurrency = {
		...mockedCurrency,
	};
	const reset = () => {
		mockedCurrency = Object.assign( mockedCurrency, originalCurrency );
	};
	return {
		setCurrencyProp: ( prop, value ) => {
			mockedCurrency[ prop ] = value;
		},
		resetMock: reset,
		CURRENCY: mockedCurrency,
	};
} );

describe( 'formatCurrency', () => {
	it( 'should use defaults (USD) when currency not passed in', () => {
		expect( formatCurrency( 9.99 ) ).toBe( '$9.99' );
		expect( formatCurrency( 30 ) ).toBe( '$30.00' );
	} );

	it( 'should uses store currency settings, not locale-based', () => {
		setCurrencyProp( 'code', 'JPY' );
		setCurrencyProp( 'precision', 3 );
		setCurrencyProp( 'priceFormat', '%2$s%1$s' );
		setCurrencyProp( 'thousandSeparator', '.' );
		setCurrencyProp( 'decimalSeparator', ',' );
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
		expect( getCurrencyFormatDecimal( 9.49258 ) ).toBe( 9.49 );
		expect( getCurrencyFormatDecimal( 30 ) ).toBe( 30 );
		expect( getCurrencyFormatDecimal( 3.0002 ) ).toBe( 3 );
	} );

	it( 'should round a number to 0 decimal places in JPY', () => {
		setCurrencyProp( 'precision', 0 );
		expect( getCurrencyFormatDecimal( 1239.88 ) ).toBe( 1240 );
		expect( getCurrencyFormatDecimal( 1500 ) ).toBe( 1500 );
		expect( getCurrencyFormatDecimal( 33715.02 ) ).toBe( 33715 );
	} );

	it( 'should correctly convert and round a string', () => {
		expect( getCurrencyFormatDecimal( '19.80' ) ).toBe( 19.8 );
	} );

	it( "should return 0 when given an input that isn't a number", () => {
		expect( getCurrencyFormatDecimal( 'abc' ) ).toBe( 0 );
		expect( getCurrencyFormatDecimal( false ) ).toBe( 0 );
		expect( getCurrencyFormatDecimal( null ) ).toBe( 0 );
	} );
} );

describe( 'getCurrencyFormatString', () => {
	it( 'should round a number to 2 decimal places in USD', () => {
		expect( getCurrencyFormatString( 9.49258 ) ).toBe( '9.49' );
		expect( getCurrencyFormatString( 30 ) ).toBe( '30.00' );
		expect( getCurrencyFormatString( 3.0002 ) ).toBe( '3.00' );
	} );

	it( 'should round a number to 0 decimal places in JPY', () => {
		setCurrencyProp( 'precision', 0 );
		expect( getCurrencyFormatString( 1239.88 ) ).toBe( '1240' );
		expect( getCurrencyFormatString( 1500 ) ).toBe( '1500' );
		expect( getCurrencyFormatString( 33715.02 ) ).toBe( '33715' );
	} );

	it( 'should correctly convert and round a string', () => {
		expect( getCurrencyFormatString( '19.80' ) ).toBe( '19.80' );
	} );

	it( "should return empty string when given an input that isn't a number", () => {
		expect( getCurrencyFormatString( 'abc' ) ).toBe( '' );
		expect( getCurrencyFormatString( false ) ).toBe( '' );
		expect( getCurrencyFormatString( null ) ).toBe( '' );
	} );
} );
