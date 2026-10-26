/**
 * Internal dependencies
 */
import Currency from '..';

describe( 'formatAmount', () => {
	it( 'should use defaults (USD) when currency not passed in', () => {
		const currency = Currency();
		expect( currency.formatAmount( 9.99 ) ).toBe( '$9.99' );
		expect( currency.formatAmount( 30 ) ).toBe( '$30.00' );
	} );

	it( 'should return country code instead of symbol, when `useCode` is set to `true`', () => {
		const currency = Currency();
		expect( currency.formatAmount( 9.99, true ) ).toBe( 'USD9.99' );
		const currency2 = Currency( {
			priceFormat: '%2$s %1$s',
			symbol: 'EUR',
		} );
		expect( currency2.formatAmount( 30 ) ).toBe( '30.00 EUR' );
	} );

	it( 'should uses store currency settings, not locale-based', () => {
		const currency = Currency( {
			code: 'JPY',
			symbol: '¥',
			precision: 3,
			priceFormat: '%2$s%1$s',
			thousandSeparator: '.',
			decimalSeparator: ',',
		} );
		expect( currency.formatAmount( 9.49258 ) ).toBe( '9,493¥' );
		expect( currency.formatAmount( 3000 ) ).toBe( '3.000,000¥' );
		expect( currency.formatAmount( 3.0002 ) ).toBe( '3,000¥' );
	} );

	it( "should return empty string when given an input that isn't a number", () => {
		const currency = Currency();
		expect( currency.formatAmount( 'abc' ) ).toBe( '' );
		// @ts-expect-error formatAccount expects a number or string;
		expect( currency.formatAmount( false ) ).toBe( '' );
		// @ts-expect-error formatAccount expects a number or string;
		expect( currency.formatAmount( null ) ).toBe( '' );
	} );
} );

describe( 'currency.formatDecimal', () => {
	it( 'should round a number to 2 decimal places in USD', () => {
		const currency = Currency();
		expect( currency.formatDecimal( 9.49258 ) ).toBe( 9.49 );
		expect( currency.formatDecimal( 30 ) ).toBe( 30 );
		expect( currency.formatDecimal( 3.0002 ) ).toBe( 3 );
	} );

	it( 'should round a number to 0 decimal places in JPY', () => {
		const currency = Currency( { precision: 0 } );
		expect( currency.formatDecimal( 1239.88 ) ).toBe( 1240 );
		expect( currency.formatDecimal( 1500 ) ).toBe( 1500 );
		expect( currency.formatDecimal( 33715.02 ) ).toBe( 33715 );
	} );

	it( 'should correctly convert and round a string', () => {
		const currency = Currency();
		expect( currency.formatDecimal( '19.80' ) ).toBe( 19.8 );
	} );

	it( "should return 0 when given an input that isn't a number", () => {
		const currency = Currency();
		expect( currency.formatDecimal( 'abc' ) ).toBe( 0 );
		// @ts-expect-error formatAccount expects a number or string;
		expect( currency.formatDecimal( false ) ).toBe( 0 );
		// @ts-expect-error formatAccount expects a number or string;
		expect( currency.formatDecimal( null ) ).toBe( 0 );
	} );

	it( 'should round exact half fractions away from zero, like PHP wc_format_decimal()', () => {
		const currency = Currency();
		// These values are not exactly representable as doubles (e.g.
		// 1.005 * 100 evaluates to 100.49999999999999), and this function
		// is documented to be used like PHP's wc_format_decimal(), which
		// rounds them half away from zero.
		expect( currency.formatDecimal( 1.005 ) ).toBe( 1.01 );
		expect( currency.formatDecimal( 2.675 ) ).toBe( 2.68 );
		expect( currency.formatDecimal( 9.995 ) ).toBe( 10 );
		expect( currency.formatDecimal( 0.285 ) ).toBe( 0.29 );
		expect( currency.formatDecimal( 4.015 ) ).toBe( 4.02 );
		expect( currency.formatDecimal( 0.005 ) ).toBe( 0.01 );
	} );

	it( 'should round negative exact half fractions away from zero', () => {
		const currency = Currency();
		expect( currency.formatDecimal( -1.005 ) ).toBe( -1.01 );
		expect( currency.formatDecimal( -0.285 ) ).toBe( -0.29 );
	} );
} );

describe( 'currency.formatDecimalString', () => {
	it( 'should round a number to 2 decimal places in USD', () => {
		const currency = Currency();
		expect( currency.formatDecimalString( 9.49258 ) ).toBe( '9.49' );
		expect( currency.formatDecimalString( 30 ) ).toBe( '30.00' );
		expect( currency.formatDecimalString( 3.0002 ) ).toBe( '3.00' );
	} );

	it( 'should round exact half fractions away from zero, like PHP', () => {
		const currency = Currency();
		expect( currency.formatDecimalString( 1.005 ) ).toBe( '1.01' );
		expect( currency.formatDecimalString( 2.675 ) ).toBe( '2.68' );
		expect( currency.formatDecimalString( -1.005 ) ).toBe( '-1.01' );
	} );

	it( 'should round a number to 0 decimal places in JPY', () => {
		const currency = Currency( { precision: 0 } );
		expect( currency.formatDecimalString( 1239.88 ) ).toBe( '1240' );
		expect( currency.formatDecimalString( 1500 ) ).toBe( '1500' );
		expect( currency.formatDecimalString( 33715.02 ) ).toBe( '33715' );
	} );

	it( 'should correctly convert and round a string', () => {
		const currency = Currency();
		expect( currency.formatDecimalString( '19.80' ) ).toBe( '19.80' );
	} );

	it( "should return empty string when given an input that isn't a number", () => {
		const currency = Currency();
		expect( currency.formatDecimalString( 'abc' ) ).toBe( '' );
		// @ts-expect-error formatAccount expects a number or string;
		expect( currency.formatDecimalString( false ) ).toBe( '' );
		// @ts-expect-error formatAccount expects a number or string;
		expect( currency.formatDecimalString( null ) ).toBe( '' );
	} );

	it( 'should strip tags in getPriceFormat', () => {
		const currency = Currency();

		expect(
			currency.getPriceFormat( {
				priceFormat: '<b>tag</b>format',
			} )
		).toBe( 'tagformat' );

		expect(
			currency.getPriceFormat( {
				priceFormat: '<script>tag</script>format',
			} )
		).toBe( 'format' );
	} );
} );
