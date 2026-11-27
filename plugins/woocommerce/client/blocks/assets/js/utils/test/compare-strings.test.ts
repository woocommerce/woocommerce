/**
 * Internal dependencies
 */
import { compareStrings } from '../compare-strings';

describe( 'compareStrings', () => {
	it( 'compares strings in code unit order and keeps equal values tied', () => {
		expect( compareStrings( 'Z', 'a' ) ).toBeLessThan( 0 );
		expect( compareStrings( '9', '10' ) ).toBeGreaterThan( 0 );
		expect( compareStrings( 'pa_size', 'pa_size' ) ).toBe( 0 );
	} );

	it( 'sorts null and undefined after strings and treats both as missing', () => {
		expect( compareStrings( 'pa_size', null ) ).toBeLessThan( 0 );
		expect( compareStrings( undefined, 'pa_size' ) ).toBeGreaterThan( 0 );
		expect( compareStrings( null, undefined ) ).toBe( 0 );
	} );
} );
