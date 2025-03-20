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
	} );

	describe( 'UPDATE_SETTING', () => {
		it( 'should update a single setting edit', () => {
			const newState = reducer( state, {
				type: TYPES.UPDATE_SETTING,
				groupId: testGroup.id,
				settingId: testSetting.id,
				value: 'new-value',
			} );

			expect( newState.edits[ testGroup.id ]?.[ testSetting.id ] ).toBe(
				'new-value'
			);
		} );
	} );

	describe( 'UPDATE_SETTINGS', () => {
		it( 'should update multiple setting edits', () => {
			const updates = [
				{ id: 'setting-1', value: 'value-1' },
				{ id: 'setting-2', value: 'value-2' },
			];

			const newState = reducer( state, {
				type: TYPES.UPDATE_SETTINGS,
				groupId: testGroup.id,
				updates,
			} );

			expect( newState.edits[ testGroup.id ] ).toEqual( {
				'setting-1': 'value-1',
				'setting-2': 'value-2',
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
	} );

	describe( 'SET_ERROR', () => {
		const error = { message: 'Test error' };

		it( 'should set error for a group', () => {
			state.errors = {
				[ testGroup.id ]: {
					'setting-1': { message: 'Old error' },
					'setting-2': { message: 'Old error' },
				},
			};

			const newState = reducer( state, {
				type: TYPES.SET_ERROR,
				groupId: testGroup.id,
				settingId: null,
				error,
			} );

			expect( newState.errors[ testGroup.id ] ).toEqual( {
				'setting-1': error,
				'setting-2': error,
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
	} );

	describe( 'REVERT_SETTING', () => {
		it( 'should remove edit for a setting', () => {
			state.edits = {
				[ testGroup.id ]: {
					[ testSetting.id ]: 'edited-value',
					'other-setting': 'other-value',
				},
			};

			const newState = reducer( state, {
				type: TYPES.REVERT_SETTING,
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
				type: TYPES.REVERT_SETTING,
				groupId: testGroup.id,
				settingId: testSetting.id,
			} );

			expect( newState.edits[ testGroup.id ] ).toBeUndefined();
		} );
	} );

	describe( 'REVERT_GROUP', () => {
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
				type: TYPES.REVERT_GROUP,
				groupId: testGroup.id,
			} );

			expect( newState.edits[ testGroup.id ] ).toBeUndefined();
			expect( newState.edits[ 'other-group' ] ).toBeDefined();
		} );
	} );
} );
