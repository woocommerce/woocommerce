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

		it( 'should return edited settings when includeEdits is true', () => {
			const setting1 = createTestSetting( {
				id: 'setting-1',
				value: 'original-value-1',
			} );
			const setting2 = createTestSetting( {
				id: 'setting-2',
				value: 'original-value-2',
			} );

			state.settings = {
				[ testGroup.id ]: {
					'setting-1': setting1,
					'setting-2': setting2,
				},
			};

			state.edits = {
				[ testGroup.id ]: { 'setting-1': 'edited-value-1' },
			};

			const settings = selectors.getSettings( state, testGroup.id, {
				includeEdits: true,
			} );

			expect( settings ).toEqual( {
				'setting-1': {
					...setting1,
					value: 'edited-value-1',
				},
				'setting-2': setting2,
			} );
		} );

		it( 'should not return settings with edits by default', () => {
			const setting1 = createTestSetting( {
				id: 'setting-1',
				value: 'original-value-1',
			} );
			const setting2 = createTestSetting( {
				id: 'setting-2',
				value: 'original-value-2',
			} );

			state.settings = {
				[ testGroup.id ]: {
					'setting-1': setting1,
					'setting-2': setting2,
				},
			};

			state.edits = {
				[ testGroup.id ]: { 'setting-1': 'edited-value-1' },
			};

			const settings = selectors.getSettings( state, testGroup.id );

			expect( settings ).toEqual( {
				'setting-1': setting1,
				'setting-2': setting2,
			} );
		} );

		it( 'should return original settings when no edits exist', () => {
			const setting1 = createTestSetting( {
				id: 'setting-1',
				value: 'original-value-1',
			} );

			state.settings = {
				[ testGroup.id ]: {
					'setting-1': setting1,
				},
			};

			const settings = selectors.getSettings( state, testGroup.id );

			expect( settings ).toEqual( {
				'setting-1': setting1,
			} );
		} );

		it( 'should return memoized value when nothing changes', () => {
			state.settings = {
				[ testGroup.id ]: { [ testSetting.id ]: testSetting },
			};

			const result1 = selectors.getSettings( state, testGroup.id );
			const result2 = selectors.getSettings( state, testGroup.id );
			expect( result1 ).toBe( result2 );
		} );

		it( 'should return new value when settings change', () => {
			state.settings = {
				[ testGroup.id ]: { [ testSetting.id ]: testSetting },
			};

			const result1 = selectors.getSettings( state, testGroup.id );

			// Update a setting
			state = {
				...state,
				settings: {
					...state.settings,
					[ testGroup.id ]: {
						[ testSetting.id ]: {
							...testSetting,
							value: 'new-value',
						},
					},
				},
			};

			const result2 = selectors.getSettings( state, testGroup.id );
			expect( result1 ).not.toBe( result2 );
			expect( result2[ testSetting.id ].value ).toBe( 'new-value' );
		} );

		it( 'should return new value when includeEdits option changes', () => {
			state.settings = {
				[ testGroup.id ]: { [ testSetting.id ]: testSetting },
			};
			state.edits = {
				[ testGroup.id ]: {
					[ testSetting.id ]: 'edited-value',
				},
			};

			const result1 = selectors.getSettings( state, testGroup.id, {
				includeEdits: false,
			} );
			const result2 = selectors.getSettings( state, testGroup.id, {
				includeEdits: true,
			} );

			expect( result1 ).not.toBe( result2 );
			expect( result1[ testSetting.id ].value ).toBe( testSetting.value );
			expect( result2[ testSetting.id ].value ).toBe( 'edited-value' );
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

		it( 'should return edited setting when includeEdits is true', () => {
			state.settings = {
				[ testGroup.id ]: { [ testSetting.id ]: testSetting },
			};
			state.edits = {
				[ testGroup.id ]: { [ testSetting.id ]: 'edited-value' },
			};

			const result = selectors.getSetting(
				state,
				testGroup.id,
				testSetting.id,
				{ includeEdits: true }
			);
			expect( result ).toEqual( {
				...testSetting,
				value: 'edited-value',
			} );
		} );

		it( 'should not return setting with edits by default', () => {
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
			} );
		} );

		it( 'should return original setting when no edits exist', () => {
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

		it( 'should return memoized value when nothing changes', () => {
			state.settings = {
				[ testGroup.id ]: { [ testSetting.id ]: testSetting },
			};

			const result1 = selectors.getSetting(
				state,
				testGroup.id,
				testSetting.id
			);
			const result2 = selectors.getSetting(
				state,
				testGroup.id,
				testSetting.id
			);
			expect( result1 ).toBe( result2 );
		} );

		it( 'should return new value when setting changes', () => {
			state.settings = {
				[ testGroup.id ]: { [ testSetting.id ]: testSetting },
			};

			const result1 = selectors.getSetting(
				state,
				testGroup.id,
				testSetting.id
			);

			// Update the setting
			state = {
				...state,
				settings: {
					...state.settings,
					[ testGroup.id ]: {
						[ testSetting.id ]: {
							...testSetting,
							value: 'new-value',
						},
					},
				},
			};

			const result2 = selectors.getSetting(
				state,
				testGroup.id,
				testSetting.id
			);
			expect( result1 ).not.toBe( result2 );
			expect( result2?.value ).toBe( 'new-value' );
		} );

		it( 'should return new value when includeEdits option changes', () => {
			state.settings = {
				[ testGroup.id ]: { [ testSetting.id ]: testSetting },
			};
			state.edits = {
				[ testGroup.id ]: {
					[ testSetting.id ]: 'edited-value',
				},
			};

			const result1 = selectors.getSetting(
				state,
				testGroup.id,
				testSetting.id,
				{ includeEdits: false }
			);
			const result2 = selectors.getSetting(
				state,
				testGroup.id,
				testSetting.id,
				{ includeEdits: true }
			);

			expect( result1 ).not.toBe( result2 );
			expect( result1?.value ).toBe( testSetting.value );
			expect( result2?.value ).toBe( 'edited-value' );
		} );
	} );

	describe( 'getSettingValue', () => {
		it( 'should return edited value when includeEdits is true', () => {
			state.settings = {
				[ testGroup.id ]: { [ testSetting.id ]: testSetting },
			};
			state.edits = {
				[ testGroup.id ]: { [ testSetting.id ]: 'edited-value' },
			};

			const value = selectors.getSettingValue(
				state,
				testGroup.id,
				testSetting.id,
				{ includeEdits: true }
			);
			expect( value ).toBe( 'edited-value' );
		} );

		it( 'should not return edited value by default', () => {
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
			expect( value ).toBe( testSetting.value );
		} );

		it( 'should return original value when no edits exist', () => {
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

	describe( 'hasEditsForGroup', () => {
		it( 'should return true if group has edits', () => {
			state.edits = {
				[ testGroup.id ]: { [ testSetting.id ]: 'edited-value' },
			};

			const result = selectors.hasEditsForGroup( state, testGroup.id );
			expect( result ).toBe( true );
		} );

		it( 'should return false if group has no edits', () => {
			const result = selectors.hasEditsForGroup( state, testGroup.id );
			expect( result ).toBe( false );
		} );

		it( 'should return false if group has empty edits object', () => {
			state.edits = { [ testGroup.id ]: {} };

			const result = selectors.hasEditsForGroup( state, testGroup.id );
			expect( result ).toBe( false );
		} );
	} );
} );
