/**
 * Internal dependencies
 */
import reducer from '../reducer';
import {
	clearErrorForGroup,
	clearIsDirty,
	setIsRequesting,
	updateErrorForGroup,
	updateSettingsForGroup,
} from '../actions';
import {
	getDirtyKeys,
	getLastSettingsErrorForGroup,
	isUpdateSettingsRequesting,
} from '../selectors';
import { SettingsState } from '../types';

const GROUP = 'wc_admin';

const edit = ( state: SettingsState, value: unknown ) =>
	reducer(
		state,
		updateSettingsForGroup( GROUP, {
			wcAdminSettings: { woocommerce_default_date_range: value },
		} )
	);

describe( 'settings reducer', () => {
	describe( 'UPDATE_ERROR_FOR_GROUP', () => {
		it( 'keeps the dirty keys so a failed save can be retried', () => {
			let state = edit( {}, 'period=month' );
			expect( getDirtyKeys( state, GROUP ) ).toEqual( [
				'wcAdminSettings',
			] );

			state = reducer(
				state,
				updateErrorForGroup( GROUP, null, new Error( 'Nope.' ) )
			);

			expect( getDirtyKeys( state, GROUP ) ).toEqual( [
				'wcAdminSettings',
			] );
		} );

		it( 'does not leave the group stuck in a requesting state', () => {
			// Mirrors the getSettings resolver, which flags the group as
			// requesting and never resets it when the fetch fails.
			let state = reducer( {}, setIsRequesting( GROUP, true ) );
			state = reducer(
				state,
				updateErrorForGroup( GROUP, null, new Error( 'Nope.' ) )
			);

			expect( getLastSettingsErrorForGroup( state, GROUP ) ).toBeTruthy();
			expect( isUpdateSettingsRequesting( state, GROUP ) ).toBe( false );
		} );
	} );

	describe( 'CLEAR_ERROR_FOR_GROUP', () => {
		it( 'clears an error left over from a previous failed save', () => {
			let state = edit( {}, 'period=month' );
			state = reducer(
				state,
				updateErrorForGroup( GROUP, null, new Error( 'Nope.' ) )
			);
			expect( getLastSettingsErrorForGroup( state, GROUP ) ).toBeTruthy();

			state = reducer( state, clearErrorForGroup( GROUP ) );

			expect( getLastSettingsErrorForGroup( state, GROUP ) ).toBe(
				false
			);
		} );

		it( 'leaves the dirty keys alone', () => {
			let state = edit( {}, 'period=month' );
			state = reducer( state, clearErrorForGroup( GROUP ) );

			expect( getDirtyKeys( state, GROUP ) ).toEqual( [
				'wcAdminSettings',
			] );
		} );

		it( 'does not create a group that was never loaded', () => {
			const state = reducer( {}, clearErrorForGroup( GROUP ) );

			expect( state ).toEqual( {} );
			expect( getLastSettingsErrorForGroup( state, GROUP ) ).toBe(
				false
			);
		} );
	} );

	it( 'reports no error once a retried save succeeds', () => {
		// Edit a setting, fail the save, then retry it successfully.
		let state = edit( {}, 'period=month' );
		state = reducer(
			state,
			updateErrorForGroup( GROUP, null, new Error( 'Nope.' ) )
		);
		state = reducer( state, setIsRequesting( GROUP, false ) );

		// The retry still has something to send.
		expect( getDirtyKeys( state, GROUP ) ).toEqual( [ 'wcAdminSettings' ] );

		state = reducer( state, clearErrorForGroup( GROUP ) );
		state = reducer( state, clearIsDirty( GROUP ) );
		state = reducer( state, setIsRequesting( GROUP, false ) );

		expect( getLastSettingsErrorForGroup( state, GROUP ) ).toBe( false );
		expect( getDirtyKeys( state, GROUP ) ).toEqual( [] );
		expect( isUpdateSettingsRequesting( state, GROUP ) ).toBe( false );
	} );
} );
