/**
 * Internal dependencies
 */
import { areValuesEqual, isCheckedValue, toStringValue } from '../values';

describe( 'values', () => {
	describe( 'toStringValue', () => {
		it( 'stringifies values and maps empty to the empty string', () => {
			expect( toStringValue( 'abc' ) ).toBe( 'abc' );
			expect( toStringValue( 10 ) ).toBe( '10' );
			expect( toStringValue( null ) ).toBe( '' );
			expect( toStringValue( undefined ) ).toBe( '' );
		} );
	} );

	describe( 'isCheckedValue', () => {
		it.each( [
			[ true, true ],
			[ 1, true ],
			[ '1', true ],
			[ 'yes', true ],
			[ 'YES', true ],
			[ 'true', true ],
			[ false, false ],
			[ 0, false ],
			[ '0', false ],
			[ 'no', false ],
			[ '', false ],
			[ undefined, false ],
		] )( 'mirrors wc_string_to_bool for %p', ( value, expected ) => {
			expect( isCheckedValue( value ) ).toBe( expected );
		} );
	} );

	describe( 'areValuesEqual', () => {
		it( 'compares scalar canonical values strictly', () => {
			expect( areValuesEqual( 10, 10 ) ).toBe( true );
			expect( areValuesEqual( '10', 10 ) ).toBe( false );
			expect( areValuesEqual( null, null ) ).toBe( true );
		} );

		it( 'compares canonical string arrays by value and order', () => {
			expect( areValuesEqual( [ 'GB', 'US' ], [ 'GB', 'US' ] ) ).toBe(
				true
			);
			expect( areValuesEqual( [ 'US', 'GB' ], [ 'GB', 'US' ] ) ).toBe(
				false
			);
			expect( areValuesEqual( [ 'GB' ], 'GB' ) ).toBe( false );
		} );
	} );
} );
