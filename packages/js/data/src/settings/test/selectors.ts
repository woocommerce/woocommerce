/**
 * Internal dependencies
 */
import { getLastSettingsErrorForGroup } from '../selectors';

describe( 'getLastSettingsErrorForGroup()', () => {
	it( 'returns false when the group does not exist', () => {
		expect( getLastSettingsErrorForGroup( {}, 'wc_admin' ) ).toBe( false );
	} );

	it( 'returns the latest error for the group', () => {
		const error = new Error( 'Could not save settings.' );
		const state = {
			wc_admin: { data: [ 'first', 'last' ], error },
		};

		expect( getLastSettingsErrorForGroup( state, 'wc_admin' ) ).toBe(
			error
		);
	} );
} );
