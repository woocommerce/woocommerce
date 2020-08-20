/**
 * Internal dependencies
 */
import { setSetting } from '../set-setting';
import { getSetting } from '../get-setting';

describe( 'setSetting', () => {
	it( 'should add a new value to the settings state for value not present', () => {
		setSetting( 'aSetting', 42 );
		expect( getSetting( 'aSetting' ) ).toBe( 42 );
	} );
	it( 'should replace existing value', () => {
		setSetting( 'adminUrl', 'not original' );
		expect( getSetting( 'adminUrl' ) ).toBe( 'not original' );
	} );
	it( 'should save the value run through the provided filter', () => {
		setSetting( 'aSetting', 'who', () => 42 );
		expect( getSetting( 'aSetting' ) ).toBe( 42 );
	} );
} );
