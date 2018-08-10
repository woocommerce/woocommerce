/** @format */
/**
 * Internal dependencies
 */
import { numberFormat } from '../index';

describe( 'numberFormat', () => {
	it( 'should default to en-US formatting', () => {
		expect( numberFormat( 1000 ) ).toBe( '1,000' );
	} );

	it( 'should return an empty string if no argument is passed', () => {
		expect( numberFormat() ).toBe( '' );
	} );

	it( 'should accept a string', () => {
		expect( numberFormat( '10000' ) ).toBe( '10,000' );
	} );
} );
