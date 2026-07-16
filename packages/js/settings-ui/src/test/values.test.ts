/**
 * Internal dependencies
 */
import {
	isCheckedValue,
	preserveInitialRepresentation,
	toStringValue,
} from '../values';

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

	describe( 'preserveInitialRepresentation', () => {
		it( 'restores non-string initials when a control emits their string form', () => {
			expect( preserveInitialRepresentation( '10', 10 ) ).toBe( 10 );
			expect( preserveInitialRepresentation( 'true', true ) ).toBe(
				true
			);
			expect( preserveInitialRepresentation( '', null ) ).toBe( null );
		} );

		it( 'restores empty initials when a control emits an empty array', () => {
			expect( preserveInitialRepresentation( [], '' ) ).toBe( '' );
			expect( preserveInitialRepresentation( [], null ) ).toBe( null );
		} );

		it( 'keeps genuinely changed values as emitted', () => {
			expect( preserveInitialRepresentation( '11', 10 ) ).toBe( '11' );
			expect(
				preserveInitialRepresentation( 'changed', 'initial' )
			).toBe( 'changed' );
			expect( preserveInitialRepresentation( [ 'GB' ], '' ) ).toEqual( [
				'GB',
			] );
			expect( preserveInitialRepresentation( 'kept', undefined ) ).toBe(
				'kept'
			);
		} );
	} );
} );
