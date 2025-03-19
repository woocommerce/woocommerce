/**
 * Internal dependencies
 */
import { createTestSetting, createTestSettingGroup } from './utils';
import { SettingsState } from '../types';
import * as selectors from '../selectors';

describe( 'setting-options selectors', () => {
	let state: SettingsState;
	let testSetting: ReturnType< typeof createTestSetting >;
	let testGroup: ReturnType< typeof createTestSettingGroup >;

	beforeEach( () => {
		testSetting = createTestSetting();
		testGroup = createTestSettingGroup();
		state = {
			groups: [],
			settings: {},
			edits: {},
			isSaving: { groups: {}, settings: {} },
			errors: {},
		};
	} );

	describe( 'getGroups', () => {
		it( 'should return all groups', () => {
			const group1 = createTestSettingGroup( { id: 'group1' } );
			const group2 = createTestSettingGroup( { id: 'group2' } );
			state.groups = [ group1, group2 ];

			const groups = selectors.getGroups( state );
			expect( groups ).toEqual( [ group1, group2 ] );
		} );
	} );

	describe( 'getGroup', () => {
		it( 'should return undefined for non-existent group', () => {
			const group = selectors.getGroup( state, 'non-existent' );
			expect( group ).toBeUndefined();
		} );

		it( 'should return the group if it exists', () => {
			state.groups = [ testGroup ];

			const result = selectors.getGroup( state, testGroup.id );
			expect( result ).toEqual( testGroup );
		} );
	} );

	describe( 'getSettings', () => {
		it( 'should return empty object for non-existent group', () => {
			const settings = selectors.getSettings( state, 'non-existent' );
			expect( settings ).toEqual( {} );
		} );

		it( 'should return settings for existing group', () => {
			state.settings = {
				[ testGroup.id ]: { [ testSetting.id ]: testSetting },
			};

			const settings = selectors.getSettings( state, testGroup.id );
			expect( settings ).toEqual( { [ testSetting.id ]: testSetting } );
		} );
	} );

	describe( 'getSetting', () => {
		it( 'should return undefined for non-existent group', () => {
			const setting = selectors.getSetting(
				state,
				'non-existent',
				'setting-id'
			);
			expect( setting ).toBeUndefined();
		} );

		it( 'should return undefined for non-existent setting', () => {
			state.settings = { [ testGroup.id ]: {} };

			const setting = selectors.getSetting(
				state,
				testGroup.id,
				'non-existent'
			);
			expect( setting ).toBeUndefined();
		} );

		it( 'should return setting with edited value if it exists', () => {
			state.settings = {
				[ testGroup.id ]: { [ testSetting.id ]: testSetting },
			};
			state.edits = {
				[ testGroup.id ]: { [ testSetting.id ]: 'edited-value' },
			};

			const result = selectors.getSetting(
				state,
				testGroup.id,
				testSetting.id
			);
			expect( result ).toEqual( {
				...testSetting,
				value: 'edited-value',
			} );
		} );

		it( 'should return original setting if no edits exist', () => {
			state.settings = {
				[ testGroup.id ]: { [ testSetting.id ]: testSetting },
			};

			const result = selectors.getSetting(
				state,
				testGroup.id,
				testSetting.id
			);
			expect( result ).toEqual( testSetting );
		} );
	} );

	describe( 'getSettingValue', () => {
		it( 'should return edited value if it exists', () => {
			state.settings = {
				[ testGroup.id ]: { [ testSetting.id ]: testSetting },
			};
			state.edits = {
				[ testGroup.id ]: { [ testSetting.id ]: 'edited-value' },
			};

			const value = selectors.getSettingValue(
				state,
				testGroup.id,
				testSetting.id
			);
			expect( value ).toBe( 'edited-value' );
		} );

		it( 'should return original value if no edits exist', () => {
			state.settings = {
				[ testGroup.id ]: { [ testSetting.id ]: testSetting },
			};

			const value = selectors.getSettingValue(
				state,
				testGroup.id,
				testSetting.id
			);
			expect( value ).toBe( testSetting.value );
		} );

		it( 'should return undefined for non-existent setting', () => {
			const value = selectors.getSettingValue(
				state,
				testGroup.id,
				'non-existent'
			);
			expect( value ).toBeUndefined();
		} );
	} );

	describe( 'isSettingEdited', () => {
		it( 'should return true if setting has edits', () => {
			state.edits = {
				[ testGroup.id ]: { [ testSetting.id ]: 'edited-value' },
			};

			const isEdited = selectors.isSettingEdited(
				state,
				testGroup.id,
				testSetting.id
			);
			expect( isEdited ).toBe( true );
		} );

		it( 'should return false if setting has no edits', () => {
			const isEdited = selectors.isSettingEdited(
				state,
				testGroup.id,
				testSetting.id
			);
			expect( isEdited ).toBe( false );
		} );
	} );

	describe( 'getEditedSettingIds', () => {
		it( 'should return empty array for non-existent group', () => {
			const editedIds = selectors.getEditedSettingIds(
				state,
				'non-existent'
			);
			expect( editedIds ).toEqual( [] );
		} );

		it( 'should return array of edited setting ids', () => {
			state.edits = {
				[ testGroup.id ]: {
					'setting-1': 'value-1',
					'setting-2': 'value-2',
				},
			};

			const editedIds = selectors.getEditedSettingIds(
				state,
				testGroup.id
			);
			expect( editedIds ).toEqual( [ 'setting-1', 'setting-2' ] );
		} );
	} );

	describe( 'isGroupSaving', () => {
		it( 'should return true if group is saving', () => {
			state.isSaving = {
				groups: { [ testGroup.id ]: true },
				settings: {},
			};

			const isSaving = selectors.isGroupSaving( state, testGroup.id );
			expect( isSaving ).toBe( true );
		} );

		it( 'should return false if group is not saving', () => {
			const isSaving = selectors.isGroupSaving( state, testGroup.id );
			expect( isSaving ).toBe( false );
		} );
	} );

	describe( 'isSettingSaving', () => {
		it( 'should return true if setting is saving', () => {
			state.isSaving = {
				groups: {},
				settings: {
					[ testGroup.id ]: { [ testSetting.id ]: true },
				},
			};

			const isSaving = selectors.isSettingSaving(
				state,
				testGroup.id,
				testSetting.id
			);
			expect( isSaving ).toBe( true );
		} );

		it( 'should return false if setting is not saving', () => {
			const isSaving = selectors.isSettingSaving(
				state,
				testGroup.id,
				testSetting.id
			);
			expect( isSaving ).toBe( false );
		} );
	} );

	describe( 'getGroupError', () => {
		it( 'should return error for group', () => {
			const error = { message: 'Test error' };
			state.errors = { [ testGroup.id ]: error };

			const result = selectors.getGroupError( state, testGroup.id );
			expect( result ).toEqual( error );
		} );

		it( 'should return undefined for non-existent group error', () => {
			const error = selectors.getGroupError( state, 'non-existent' );
			expect( error ).toBeUndefined();
		} );
	} );

	describe( 'getSettingError', () => {
		it( 'should return error for setting', () => {
			const error = { message: 'Test error' };
			state.errors = {
				[ testGroup.id ]: { [ testSetting.id ]: error },
			};

			const result = selectors.getSettingError(
				state,
				testGroup.id,
				testSetting.id
			);
			expect( result ).toEqual( error );
		} );

		it( 'should return undefined for non-existent setting error', () => {
			const error = selectors.getSettingError(
				state,
				testGroup.id,
				testSetting.id
			);
			expect( error ).toBeUndefined();
		} );
	} );

	describe( 'hasEdits', () => {
		it( 'should return true if group has edits', () => {
			state.edits = {
				[ testGroup.id ]: { [ testSetting.id ]: 'edited-value' },
			};

			const result = selectors.hasEdits( state, testGroup.id );
			expect( result ).toBe( true );
		} );

		it( 'should return false if group has no edits', () => {
			const result = selectors.hasEdits( state, testGroup.id );
			expect( result ).toBe( false );
		} );

		it( 'should return false if group has empty edits object', () => {
			state.edits = { [ testGroup.id ]: {} };

			const result = selectors.hasEdits( state, testGroup.id );
			expect( result ).toBe( false );
		} );
	} );
} );
