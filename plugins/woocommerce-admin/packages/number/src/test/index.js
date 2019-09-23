/** @format */
/**
 * Internal dependencies
 */
import { numberFormat } from '../index';

/**
 * WooCommerce dependencies
 * Note: setCurrencyProp doesn't exist on the module alias, it's used for mocking
 * values.
 */
import { setCurrencyProp, resetMock } from '@woocommerce/wc-admin-settings';

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

describe( 'numberFormat', () => {
	beforeEach( () => {
		resetMock();
	} );
	it( 'should default to precision=null decimal=. thousands=,', () => {
		expect( numberFormat( 1000 ) ).toBe( '1,000' );
	} );

	it( 'should return an empty string if no argument is passed', () => {
		expect( numberFormat() ).toBe( '' );
	} );

	it( 'should accept a string', () => {
		expect( numberFormat( '10000' ) ).toBe( '10,000' );
	} );

	it( 'maintains all decimals if no precision specified', () => {
		expect( numberFormat( '10000.123456' ) ).toBe( '10,000.123456' );
	} );

	it( 'maintains all decimals if invalid precision specified', () => {
		expect( numberFormat( '10000.123456', 'not a number' ) ).toBe( '10,000.123456' );
	} );

	it( 'calculates the correct decimals based on precision passed in', () => {
		expect( numberFormat( '1337.4498', 2 ) ).toBe( '1,337.45' );
	} );

	it( 'uses store currency settings, not locale', () => {
		setCurrencyProp( 'decimalSeparator', ',' );
		setCurrencyProp( 'thousandSeparator', '.' );
		expect( numberFormat( '12345.6789', 3 ) ).toBe( '12.345,679' );
	} );
} );
