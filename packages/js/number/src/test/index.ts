/**
 * External dependencies
 */
import { partial } from 'lodash';

/**
 * Internal dependencies
 */
import { numberFormat, parseNumber, calculateDelta } from '../index';

const defaultNumberFormat = partial( numberFormat, {} );

describe( 'numberFormat', () => {
	it( 'should default to precision=null decimal=. thousands=,', () => {
		expect( defaultNumberFormat( 1000 ) ).toBe( '1,000' );
	} );

	it( 'should return an empty string if no argument is passed', () => {
		expect( defaultNumberFormat() ).toBe( '' );
	} );

	it( 'should accept a string', () => {
		expect( defaultNumberFormat( '10000' ) ).toBe( '10,000' );
	} );

	it( 'maintains all decimals if no precision specified', () => {
		expect( defaultNumberFormat( '10000.123456' ) ).toBe( '10,000.123456' );
	} );

	it( 'maintains all decimals if invalid precision specified', () => {
		expect(
			numberFormat( { precision: 'not a number' }, '10000.123456' )
		).toBe( '10,000.123456' );
	} );

	it( 'calculates the correct decimals based on precision passed in', () => {
		expect( numberFormat( { precision: 2 }, '1337.4498' ) ).toBe(
			'1,337.45'
		);
	} );

	it( 'uses store currency settings, not locale', () => {
		const config = {
			decimalSeparator: ',',
			thousandSeparator: '.',
			precision: 3,
		};
		expect( numberFormat( config, '12345.6789' ) ).toBe( '12.345,679' );
	} );
} );

describe( 'calculateDelta', () => {
	it( 'returns a positive change when a negative baseline grows to a positive value', () => {
		// From WOOPLUG-2965: $-755.90 → $480 is an increase, not a decrease.
		expect( calculateDelta( 480, -755.9 ) ).toBe( 164 );
	} );

	it( 'calculates the change between two positive values', () => {
		expect( calculateDelta( 1202.6, 685.6 ) ).toBe( 75 );
	} );

	it( 'calculates a decrease between two positive values', () => {
		// A positive baseline is unaffected by the fix; the sign must stay negative.
		expect( calculateDelta( 685.6, 1202.6 ) ).toBe( -43 );
	} );

	it( 'calculates the change between two negative values', () => {
		// -450 → -900 is a further decline of 100%.
		expect( calculateDelta( -900, -450 ) ).toBe( -100 );
	} );

	it( 'returns 0 when the baseline is 0', () => {
		expect( calculateDelta( 480, 0 ) ).toBe( 0 );
	} );

	it( 'reports a full recovery from a negative baseline to break-even', () => {
		// -500 → 0 is a 100% recovery of the loss.
		expect( calculateDelta( 0, -500 ) ).toBe( 100 );
	} );

	it( 'returns null when either value is not finite', () => {
		expect( calculateDelta( NaN, 100 ) ).toBeNull();
		expect( calculateDelta( 100, Infinity ) ).toBeNull();
	} );
} );

describe( 'parseNumber', () => {
	it( 'should remove thousand separator before parsing number', () => {
		const config = {
			decimalSeparator: ',',
			thousandSeparator: '.',
			precision: 3,
		};
		expect( parseNumber( config, '12.345,679' ) ).toBe( '12345.679' );
	} );

	it( 'supports empty string as the thousandSeparator', () => {
		const config = {
			decimalSeparator: ',',
			thousandSeparator: '',
			precision: 3,
		};
		expect( parseNumber( config, '12345,679' ) ).toBe( '12345.679' );
	} );

	it( 'supports empty string as the decimalSeparator', () => {
		const config = {
			decimalSeparator: '',
			thousandSeparator: ',',
			precision: 2,
		};
		expect( parseNumber( config, '1,2345,679' ) ).toBe( '12345679.00' );
	} );
} );

describe( 'numberFormat PHP number_format() parity', () => {
	// PHP's number_format() rounds half away from zero; locutus (the
	// underlying port) rounds half up via Math.round, which sends negative
	// exact halves one unit toward zero. These expectations were verified
	// against real PHP 8.4.23/8.5.8 number_format() output.
	it.each( [
		[ -1.005, 2, '-1.01' ],
		[ -2.675, 2, '-2.68' ],
		[ -0.125, 2, '-0.13' ],
		[ -1234.565, 2, '-1,234.57' ],
		[ -0.5, 0, '-1' ],
		[ -2.5, 0, '-3' ],
		[ -0.005, 2, '-0.01' ],
	] )(
		'rounds negative exact halves away from zero: %d @ precision %d',
		( value, precision, expected ) => {
			expect( numberFormat( { precision }, value ) ).toBe( expected );
		}
	);

	it( 'parses numeric strings before rounding', () => {
		expect( numberFormat( { precision: 2 }, '-1.005' ) ).toBe( '-1.01' );
	} );

	it( 'keeps the sign off results that round to zero, like PHP', () => {
		expect( numberFormat( { precision: 2 }, -0.001 ) ).toBe( '0.00' );
		expect( numberFormat( { precision: 2 }, -0 ) ).toBe( '0.00' );
		expect( numberFormat( { precision: 0 }, -0.4 ) ).toBe( '0' );
	} );

	it( 'honours configured separators for negative halves', () => {
		expect(
			numberFormat(
				{ precision: 2, decimalSeparator: ',', thousandSeparator: '.' },
				-1234.565
			)
		).toBe( '-1.234,57' );
	} );

	it( 'does not misread digit-containing separators as a nonzero result', () => {
		expect(
			numberFormat( { precision: 2, decimalSeparator: '5' }, -0.001 )
		).toBe( '0500' );
		expect(
			numberFormat( { precision: 2, decimalSeparator: '5' }, -0.005 )
		).toBe( '-0501' );
	} );

	it( 'preserves the pre-existing locutus overflow artifact on huge values', () => {
		// 1e20 at precision 2 overflows locutus's string-concat scaling to
		// 'NaN.00' on trunk; PHP formats these fine, but that separate
		// defect is out of scope here - this fix must not change it.
		expect( numberFormat( { precision: 2 }, -1e21 ) ).toBe( 'NaN.00' );
		expect( numberFormat( { precision: 2 }, -Number.MAX_VALUE ) ).toBe(
			'NaN.00'
		);
		expect( numberFormat( { precision: 2 }, 1e21 ) ).toBe( 'NaN.00' );
		expect(
			numberFormat( { precision: 2, decimalSeparator: '5' }, -1e21 )
		).toBe( 'NaN500' );
	} );

	it( 'restores the sign with adversarial separator alphabets', () => {
		// All expectations verified against real PHP number_format()
		// (8.4.23 and 8.5.8 agree).
		// Digit as the thousand separator.
		expect(
			numberFormat( { precision: 2, thousandSeparator: '5' }, -5 )
		).toBe( '-5.00' );
		// Overlapping separators ('.b' contains '.').
		expect(
			numberFormat(
				{
					precision: 2,
					decimalSeparator: '.b',
					thousandSeparator: '.',
				},
				-1234.5
			)
		).toBe( '-1.234.b50' );
		// The literal string 'NaN' as the decimal separator.
		expect(
			numberFormat( { precision: 2, decimalSeparator: 'NaN' }, -1.005 )
		).toBe( '-1NaN01' );
		// Tiny value that keeps a nonzero result after rounding.
		expect( numberFormat( { precision: 8 }, -1.5e-8 ) ).toBe(
			'-0.00000002'
		);
	} );

	it( 'is unchanged for ordinary negative values', () => {
		// Verified against real PHP 8.4.23/8.5.8 number_format().
		expect( numberFormat( { precision: 4 }, -1.234567 ) ).toBe( '-1.2346' );
		expect( numberFormat( { precision: 2 }, -1234567.891 ) ).toBe(
			'-1,234,567.89'
		);
		expect( numberFormat( { precision: 0 }, -5.1 ) ).toBe( '-5' );
		expect( numberFormat( { precision: 0 }, -0.4 ) ).toBe( '0' );
	} );

	it( 'is unchanged for non-negative values', () => {
		expect( numberFormat( { precision: 2 }, 1.005 ) ).toBe( '1.01' );
		expect( numberFormat( { precision: 2 }, 4492.605 ) ).toBe( '4,492.61' );
		expect( numberFormat( { precision: 0 }, 2.5 ) ).toBe( '3' );
	} );
} );
