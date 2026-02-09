/**
 * External dependencies
 */
import { partial } from 'lodash';

/**
 * Internal dependencies
 */
import { numberFormat, parseNumber } from '../index';

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
