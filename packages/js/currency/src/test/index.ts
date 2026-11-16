/**
 * External dependencies
 */
import { numberFormat } from '@woocommerce/number';

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

	it( 'should round half fractions like numberFormat(), which renders amounts on screen', () => {
		const currency = Currency();
		// These are not exactly representable as doubles, e.g. 1.005 * 100
		// is 100.49999999999999, so rounding the scaled value drops them.
		expect( currency.formatDecimal( 1.005 ) ).toBe( 1.01 );
		expect( currency.formatDecimal( 2.675 ) ).toBe( 2.68 );
		expect( currency.formatDecimal( 9.995 ) ).toBe( 10 );
		expect( currency.formatDecimal( 0.285 ) ).toBe( 0.29 );
		// numberFormat() rounds negative halves toward positive infinity.
		expect( currency.formatDecimal( -2.675 ) ).toBe( -2.67 );
		expect( currency.formatDecimal( -1.005 ) ).toBe( -1 );
	} );
} );

describe( 'currency.formatDecimalString', () => {
	it( 'should round a number to 2 decimal places in USD', () => {
		const currency = Currency();
		expect( currency.formatDecimalString( 9.49258 ) ).toBe( '9.49' );
		expect( currency.formatDecimalString( 30 ) ).toBe( '30.00' );
		expect( currency.formatDecimalString( 3.0002 ) ).toBe( '3.00' );
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

	it( 'should match numberFormat() so exports agree with the on-screen amount', () => {
		const currency = Currency();
		const values = [ 1.005, 2.675, 9.995, 0.285, -1.005, -2.675, -0.285 ];
		values.forEach( ( value ) => {
			expect( currency.formatDecimalString( value ) ).toBe(
				numberFormat(
					{
						precision: 2,
						decimalSeparator: '.',
						thousandSeparator: '',
					},
					value
				)
			);
		} );
		expect( currency.formatDecimalString( 1.005 ) ).toBe( '1.01' );
		expect( currency.formatDecimalString( -2.675 ) ).toBe( '-2.67' );
	} );

	it( 'should keep pre-existing behavior when precision is unset, null, a string, or fractional', () => {
		// toFixed( undefined ), toFixed( null ) and toFixed( NaN ) use zero
		// decimal places. A null precision is an explicitly supported
		// configuration (see the 'Currency precision is null' warning in
		// setCurrency).
		const unsetPrecision = Currency( { precision: undefined } );
		expect( unsetPrecision.formatDecimalString( 1.23 ) ).toBe( '1' );
		const nullPrecision = Currency( { precision: null } );
		expect( nullPrecision.formatDecimalString( 1.23 ) ).toBe( '1' );
		// A string precision is normalized by setCurrency via parseInt.
		const stringPrecision = Currency( { precision: '2' } );
		expect( stringPrecision.formatDecimalString( 1.23 ) ).toBe( '1.23' );
		// A fractional precision keeps its integer part, like toFixed().
		const fractionalPrecision = Currency( { precision: 2.7 } );
		expect( fractionalPrecision.formatDecimalString( 1.23 ) ).toBe(
			'1.23'
		);
		expect( fractionalPrecision.formatDecimalString( 1.005 ) ).toBe(
			'1.00'
		);
	} );

	it( 'should keep returning Infinity and exponent-notation values as before', () => {
		const currency = Currency();
		expect( currency.formatDecimalString( Infinity ) ).toBe( 'Infinity' );
		expect( currency.formatDecimal( Infinity ) ).toBe( Infinity );
		expect( currency.formatDecimalString( 1.5e21 ) ).toBe( '1.5e+21' );
		expect( currency.formatDecimal( 1.5e21 ) ).toBe( 1.5e21 );
	} );
} );

describe( 'decimal formatting compatibility', () => {
	it.each( [ undefined, null, NaN, 'invalid' ] )(
		'keeps numeric NaN with precision %s',
		( precision ) => {
			const currency = Currency( { precision } );
			expect( currency.formatDecimal( 1.23 ) ).toBeNaN();
			expect( currency.formatDecimalString( 1.23 ) ).toBe( '1' );
			expect( currency.formatDecimalString( -0.001 ) ).toBe( '-0' );
		}
	);

	it.each( [ -2, 101, Infinity, -Infinity ] )(
		'keeps string range checks with precision %s',
		( precision ) => {
			const currency = Currency( { precision } );
			expect( () => currency.formatDecimalString( 1.23 ) ).toThrow(
				RangeError
			);
		}
	);

	it.each( [ 1e19, -1e19, 1e20, -1e20, 1.5e21, Infinity, -Infinity ] )(
		'keeps unformattable value %s',
		( value ) => {
			const currency = Currency();
			expect( currency.formatDecimal( value ) ).toBe( value );
			expect( currency.formatDecimalString( value ) ).toBe(
				value.toFixed( 2 )
			);
		}
	);

	it( 'keeps numeric behavior for fractional and negative precision', () => {
		for ( const precision of [ 2.7, -2, -0.5 ] ) {
			const currency = Currency( { precision } );
			const exponent = Math.pow( 10, precision );
			expect( currency.formatDecimal( 1.005 ) ).toBe(
				Math.round( 1.005 * exponent ) / exponent
			);
		}
	} );

	it( 'keeps valid high precision numeric output when the formatter fails', () => {
		const currency = Currency( { precision: 100 } );
		expect( currency.formatDecimal( 1.23 ) ).toBe( 1.23 );
		expect( currency.formatDecimalString( 1.23 ) ).toBe(
			( 1.23 ).toFixed( 100 )
		);
	} );

	it( 'uses display rounding for zero, negative halves and small values', () => {
		const currency = Currency();
		for ( const value of [ -0.001, -0.005, -2.675, 5e-324, 1e-7 ] ) {
			const displayed = numberFormat(
				{ precision: 2, decimalSeparator: '.', thousandSeparator: '' },
				value
			);
			expect( currency.formatDecimalString( value ) ).toBe( displayed );
			expect( currency.formatDecimal( value ) ).toBe(
				Number( displayed )
			);
		}
	} );
} );

describe( 'decimal formatter exponent boundaries', () => {
	it.each( [
		[ 1e18, 3 ],
		[ -1e18, 3 ],
		[ 1.23, 21 ],
		[ 1.23, 100 ],
		[ 1e-7, 100 ],
		[ 5e-324, 100 ],
	] )( 'keeps finite output for %s at precision %s', ( value, precision ) => {
		const currency = Currency( { precision } );
		const exponent = Math.pow( 10, precision );
		expect( currency.formatDecimal( value ) ).toBe(
			Math.round( value * exponent ) / exponent
		);
		expect( currency.formatDecimalString( value ) ).toBe(
			value.toFixed( precision )
		);
	} );

	it.each( [ 101, 309, Infinity, -Infinity ] )(
		'keeps numeric arithmetic at precision %s',
		( precision ) => {
			const currency = Currency( { precision } );
			const exponent = Math.pow( 10, precision );
			expect( currency.formatDecimal( 1.23 ) ).toBe(
				Math.round( 1.23 * exponent ) / exponent
			);
		}
	);

	it( 'keeps no grouping and a decimal point with localized currency settings', () => {
		const currency = Currency( {
			precision: 2,
			decimalSeparator: ',',
			thousandSeparator: '.',
		} );
		expect( currency.formatDecimalString( 1234.005 ) ).toBe( '1234.01' );
		expect( currency.formatDecimal( 1234.005 ) ).toBe( 1234.01 );
	} );
} );

it.each( [ 2.7, -0.5 ] )(
	'keeps string coercion at precision %s',
	( precision ) => {
		const currency = Currency( { precision } );
		expect( currency.formatDecimalString( 1.005 ) ).toBe(
			( 1.005 ).toFixed( precision )
		);
	}
);

it( 'keeps display rounding at valid high precision without an artificial safe-integer cutoff', () => {
	const currency = Currency( { precision: 20 } );
	expect( currency.formatDecimalString( 1.23 ) ).toBe(
		'1.23000000000000000000'
	);
} );

it( 'rounds negative integer halves like the display', () => {
	const currency = Currency( { precision: 0 } );
	expect( currency.formatDecimalString( -939.5 ) ).toBe( '-939' );
	expect( currency.formatDecimal( -939.5 ) ).toBe( -939 );
} );
