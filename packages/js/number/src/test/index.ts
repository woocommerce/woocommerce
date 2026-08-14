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
