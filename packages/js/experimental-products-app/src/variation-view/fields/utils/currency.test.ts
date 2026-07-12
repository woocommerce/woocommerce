/**
 * Internal dependencies
 */
import { getCurrencyObject, formatCurrency } from './currency';

jest.mock( '@woocommerce/settings', () => ( {
	CURRENCY: {
		code: 'USD',
		symbol: '$',
		symbolPosition: 'left',
		precision: '2',
		decimalSeparator: '.',
		thousandSeparator: ',',
	},
} ) );

const getCurrencyMock = () =>
	jest.requireMock( '@woocommerce/settings' ).CURRENCY;

describe( 'getCurrencyObject', () => {
	afterEach( () => {
		const mock = getCurrencyMock();
		mock.code = 'USD';
		mock.symbol = '$';
		mock.symbolPosition = 'left';
		mock.precision = '2';
	} );

	it( 'returns currency settings from CURRENCY global', () => {
		const result = getCurrencyObject();
		expect( result.code ).toBe( 'USD' );
		expect( result.symbol ).toBe( '$' );
		expect( result.precision ).toBe( 2 );
	} );

	it( 'falls back to USD when code is empty', () => {
		getCurrencyMock().code = '';
		expect( getCurrencyObject().code ).toBe( 'USD' );
	} );

	it( 'falls back to $ when symbol is empty', () => {
		getCurrencyMock().symbol = '';
		expect( getCurrencyObject().symbol ).toBe( '$' );
	} );

	it( 'falls back to precision 2 when precision is invalid', () => {
		getCurrencyMock().precision = 'invalid';
		expect( getCurrencyObject().precision ).toBe( 2 );
	} );

	it( 'falls back to precision 2 when precision is negative', () => {
		getCurrencyMock().precision = '-1';
		expect( getCurrencyObject().precision ).toBe( 2 );
	} );
} );

describe( 'formatCurrency', () => {
	it( 'formats a number as currency', () => {
		const result = formatCurrency( 9.99 );
		expect( result ).toContain( '9.99' );
	} );

	it( 'formats a string number as currency', () => {
		const result = formatCurrency( '19.99' );
		expect( result ).toContain( '19.99' );
	} );

	it( 'returns empty string for NaN input', () => {
		expect( formatCurrency( 'not-a-number' ) ).toBe( '' );
	} );

	it( 'returns empty string for Infinity input', () => {
		expect( formatCurrency( Infinity ) ).toBe( '' );
	} );

	it( 'handles zero correctly', () => {
		const result = formatCurrency( 0 );
		expect( result ).toContain( '0.00' );
	} );

	it( 'accepts a custom currency code', () => {
		const result = formatCurrency( 10, 'EUR' );
		expect( result ).toContain( '10.00' );
	} );

	it( 'falls back to USD formatting when currency code is invalid', () => {
		const result = formatCurrency( 10, 'INVALID' );
		expect( result ).toContain( '10.00' );
	} );
} );
