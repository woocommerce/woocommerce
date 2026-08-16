/**
 * Internal dependencies
 */
import reducer, { DEFAULT_STATE } from '../reducer';
import { TYPES } from '../action-types';
import { createTestSetting, createTestSettingGroup } from './utils';
import type { SettingsState } from '../types';
import type { Actions } from '../actions';

describe( 'setting-options reducer', () => {
	let state: SettingsState;
	let testSetting: ReturnType< typeof createTestSetting >;
	let testGroup: ReturnType< typeof createTestSettingGroup >;

	beforeEach( () => {
		testSetting = createTestSetting();
		testGroup = createTestSettingGroup();
		state = { ...DEFAULT_STATE };
	} );

	it( 'should return default state when no state is provided', () => {
		// @ts-expect-error action does not matter in tests
		const newState = reducer( undefined, { type: 'UNKNOWN' } as Actions );
		expect( newState ).toEqual( DEFAULT_STATE );
	} );

	describe( 'RECEIVE_GROUPS', () => {
		it( 'should update groups', () => {
			const groups = [ testGroup ];
			const newState = reducer( state, {
				type: TYPES.RECEIVE_GROUPS,
				groups,
			} );

			expect( newState.groups ).toEqual( groups );
		} );
	} );

	describe( 'RECEIVE_SETTINGS', () => {
		it( 'should update settings for a group', () => {
			const settings = [ testSetting ];
			const newState = reducer( state, {
				type: TYPES.RECEIVE_SETTINGS,
				groupId: testGroup.id,
				settings,
			} );

			expect( newState.settings[ testGroup.id ] ).toEqual( {
				[ testSetting.id ]: testSetting,
			} );
		} );

		it( 'should clear edits for received settings', () => {
			state.edits = {
				[ testGroup.id ]: {
					[ testSetting.id ]: 'edited-value',
					'other-setting': 'other-value',
				},
			};

			const newState = reducer( state, {
				type: TYPES.RECEIVE_SETTINGS,
				groupId: testGroup.id,
				settings: [ testSetting ],
			} );

			expect( newState.edits[ testGroup.id ] ).toEqual( {
				'other-setting': 'other-value',
			} );
		} );

		it( 'should remove group from edits if all edits are cleared', () => {
			state.edits = {
				[ testGroup.id ]: {
					[ testSetting.id ]: 'edited-value',
				},
			};

			const newState = reducer( state, {
				type: TYPES.RECEIVE_SETTINGS,
				groupId: testGroup.id,
				settings: [ testSetting ],
			} );

			expect( newState.edits[ testGroup.id ] ).toBeUndefined();
		} );

		it( 'should clear errors for received settings', () => {
			state.errors = {
				[ testGroup.id ]: {
					[ testSetting.id ]: { message: 'Error' },
					'other-setting': { message: 'Other error' },
				},
			};

			const newState = reducer( state, {
				type: TYPES.RECEIVE_SETTINGS,
				groupId: testGroup.id,
				settings: [ testSetting ],
			} );

			expect( newState.errors[ testGroup.id ] ).toEqual( {
				'other-setting': { message: 'Other error' },
			} );
		} );
	} );

	describe( 'EDIT_SETTING', () => {
		beforeEach( () => {
			// Set up initial settings
			state.settings = {
				[ testGroup.id ]: {
					[ testSetting.id ]: testSetting,
				},
			};
		} );

		it( 'should not store edit if value is unchanged', () => {
			const newState = reducer( state, {
				type: TYPES.EDIT_SETTING,
				groupId: testGroup.id,
				settingId: testSetting.id,
				value: testSetting.value,
			} );

			expect( newState.edits[ testGroup.id ] ).toBeUndefined();
		} );

		it( 'should store edit if value is different', () => {
			const newValue = 'new-value';
			const newState = reducer( state, {
				type: TYPES.EDIT_SETTING,
				groupId: testGroup.id,
				settingId: testSetting.id,
				value: newValue,
			} );

			expect( newState.edits[ testGroup.id ]?.[ testSetting.id ] ).toBe(
				newValue
			);
		} );

		it( 'should remove edit when value matches original', () => {
			// First set an edit
			state = reducer( state, {
				type: TYPES.EDIT_SETTING,
				groupId: testGroup.id,
				settingId: testSetting.id,
				value: 'edited-value',
			} );

			// Then change it back to original
			const newState = reducer( state, {
				type: TYPES.EDIT_SETTING,
				groupId: testGroup.id,
				settingId: testSetting.id,
				value: testSetting.value,
			} );

			expect(
				newState.edits[ testGroup.id ]?.[ testSetting.id ]
			).toBeUndefined();
		} );
	} );

	describe( 'EDIT_SETTINGS', () => {
		beforeEach( () => {
			// Set up initial settings
			state.settings = {
				[ testGroup.id ]: {
					setting1: createTestSetting( {
						id: 'setting1',
						value: 'value1',
					} ),
					setting2: createTestSetting( {
						id: 'setting2',
						value: 'value2',
					} ),
				},
			};
		} );

		it( 'should only store edits for changed values', () => {
			const updates = [
				{ id: 'setting1', value: 'value1' }, // unchanged
				{ id: 'setting2', value: 'new-value2' }, // changed
			];

			const newState = reducer( state, {
				type: TYPES.EDIT_SETTINGS,
				groupId: testGroup.id,
				updates,
			} );

			expect( newState.edits[ testGroup.id ] ).toEqual( {
				setting2: 'new-value2',
			} );
		} );

		it( 'should remove edits when values match original', () => {
			// First set some edits
			state = reducer( state, {
				type: TYPES.EDIT_SETTINGS,
				groupId: testGroup.id,
				updates: [
					{ id: 'setting1', value: 'edited1' },
					{ id: 'setting2', value: 'edited2' },
				],
			} );

			// Then change them back to original
			const newState = reducer( state, {
				type: TYPES.EDIT_SETTINGS,
				groupId: testGroup.id,
				updates: [
					{ id: 'setting1', value: 'value1' },
					{ id: 'setting2', value: 'value2' },
				],
			} );

			expect( newState.edits[ testGroup.id ] ).toBeUndefined();
		} );

		it( 'should handle mixed changes correctly', () => {
			const updates = [
				{ id: 'setting1', value: 'new-value1' }, // changed
				{ id: 'setting2', value: 'value2' }, // unchanged
			];

			const newState = reducer( state, {
				type: TYPES.EDIT_SETTINGS,
				groupId: testGroup.id,
				updates,
			} );

			expect( newState.edits[ testGroup.id ] ).toEqual( {
				setting1: 'new-value1',
			} );
		} );
	} );

	describe( 'SET_SAVING', () => {
		it( 'should set saving state for a group', () => {
			const newState = reducer( state, {
				type: TYPES.SET_SAVING,
				groupId: testGroup.id,
				settingId: null,
				isSaving: true,
			} );

			expect( newState.isSaving.groups[ testGroup.id ] ).toBe( true );
		} );

		it( 'should set saving state for a setting', () => {
			const newState = reducer( state, {
				type: TYPES.SET_SAVING,
				groupId: testGroup.id,
				settingId: testSetting.id,
				isSaving: true,
			} );

			expect(
				newState.isSaving.settings[ testGroup.id ]?.[ testSetting.id ]
			).toBe( true );
		} );

		it( 'should clear saving state when done', () => {
			// Set saving state first
			state = reducer( state, {
				type: TYPES.SET_SAVING,
				groupId: testGroup.id,
				settingId: testSetting.id,
				isSaving: true,
			} );

			const newState = reducer( state, {
				type: TYPES.SET_SAVING,
				groupId: testGroup.id,
				settingId: testSetting.id,
				isSaving: false,
			} );

			expect(
				newState.isSaving.settings[ testGroup.id ]?.[ testSetting.id ]
			).toBe( false );
		} );
	} );

	describe( 'SET_ERROR', () => {
		const error = { message: 'Test error' };

		it( 'should set error for a group', () => {
			state.errors = {
				[ testGroup.id ]: {
					all: { message: 'Old error' },
				},
			};

			const newState = reducer( state, {
				type: TYPES.SET_ERROR,
				groupId: testGroup.id,
				settingId: null,
				error,
			} );

			expect( newState.errors[ testGroup.id ] ).toEqual( {
				all: error,
			} );
		} );

		it( 'should set error for a setting', () => {
			const newState = reducer( state, {
				type: TYPES.SET_ERROR,
				groupId: testGroup.id,
				settingId: testSetting.id,
				error,
			} );

			expect(
				newState.errors[ testGroup.id ]?.[ testSetting.id ]
			).toEqual( error );
		} );

		it( 'should clear error when set to null', () => {
			// Set an error first
			state = reducer( state, {
				type: TYPES.SET_ERROR,
				groupId: testGroup.id,
				settingId: testSetting.id,
				error,
			} );

			const newState = reducer( state, {
				type: TYPES.SET_ERROR,
				groupId: testGroup.id,
				settingId: testSetting.id,
				error: null,
			} );

			expect(
				newState.errors[ testGroup.id ]?.[ testSetting.id ]
			).toBeUndefined();
		} );

		it( 'should remove group from errors if last error is cleared', () => {
			// Set an error first
			state = reducer( state, {
				type: TYPES.SET_ERROR,
				groupId: testGroup.id,
				settingId: testSetting.id,
				error,
			} );

			const newState = reducer( state, {
				type: TYPES.SET_ERROR,
				groupId: testGroup.id,
				settingId: testSetting.id,
				error: null,
			} );

			expect( newState.errors[ testGroup.id ] ).toBeUndefined();
		} );
	} );

	describe( 'REVERT_EDITED_SETTING', () => {
		it( 'should remove edit for a setting', () => {
			state.edits = {
				[ testGroup.id ]: {
					[ testSetting.id ]: 'edited-value',
					'other-setting': 'other-value',
				},
			};

			const newState = reducer( state, {
				type: TYPES.REVERT_EDITED_SETTING,
				groupId: testGroup.id,
				settingId: testSetting.id,
			} );

			expect( newState.edits[ testGroup.id ] ).toEqual( {
				'other-setting': 'other-value',
			} );
		} );

		it( 'should remove group from edits if last edit is reverted', () => {
			state.edits = {
				[ testGroup.id ]: {
					[ testSetting.id ]: 'edited-value',
				},
			};

			const newState = reducer( state, {
				type: TYPES.REVERT_EDITED_SETTING,
				groupId: testGroup.id,
				settingId: testSetting.id,
			} );

			expect( newState.edits[ testGroup.id ] ).toBeUndefined();
		} );

		it( 'should clear error when reverting', () => {
			state.edits = {
				[ testGroup.id ]: {
					[ testSetting.id ]: 'edited-value',
				},
			};
			state.errors = {
				[ testGroup.id ]: {
					[ testSetting.id ]: { message: 'Error' },
				},
			};

			const newState = reducer( state, {
				type: TYPES.REVERT_EDITED_SETTING,
				groupId: testGroup.id,
				settingId: testSetting.id,
			} );

			expect(
				newState.errors[ testGroup.id ]?.[ testSetting.id ]
			).toBeUndefined();
		} );
	} );

	describe( 'REVERT_EDITED_SETTINGS_GROUP', () => {
		it( 'should remove all edits for a group', () => {
			state.edits = {
				[ testGroup.id ]: {
					[ testSetting.id ]: 'edited-value',
					'other-setting': 'other-value',
				},
				'other-group': {
					'some-setting': 'some-value',
				},
			};

			const newState = reducer( state, {
				type: TYPES.REVERT_EDITED_SETTINGS_GROUP,
				groupId: testGroup.id,
			} );

			expect( newState.edits[ testGroup.id ] ).toBeUndefined();
			expect( newState.edits[ 'other-group' ] ).toBeDefined();
		} );

		it( 'should clear all errors for the group', () => {
			state.edits = {
				[ testGroup.id ]: {
					[ testSetting.id ]: 'edited-value',
				},
			};
			state.errors = {
				[ testGroup.id ]: {
					[ testSetting.id ]: { message: 'Error' },
					'other-setting': { message: 'Other error' },
				},
				'other-group': {
					'some-setting': { message: 'Some error' },
				},
			};

			const newState = reducer( state, {
				type: TYPES.REVERT_EDITED_SETTINGS_GROUP,
				groupId: testGroup.id,
			} );

			expect( newState.errors[ testGroup.id ] ).toBeUndefined();
			expect( newState.errors[ 'other-group' ] ).toBeDefined();
		} );
	} );
} );
