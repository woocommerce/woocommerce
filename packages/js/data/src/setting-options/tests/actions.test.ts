/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	createTestRegistryAndStore,
	createTestSetting,
	createTestError,
} from './utils';
import { APIError } from '../types';
import { STORE_NAME } from '../';

jest.mock( '@wordpress/api-fetch' );

describe( 'setting-options actions', () => {
	let registry: ReturnType< typeof createTestRegistryAndStore >[ 'registry' ];
	let store: ReturnType< typeof createTestRegistryAndStore >[ 'store' ];

	beforeEach( () => {
		const registryAndStore = createTestRegistryAndStore();
		registry = registryAndStore.registry;
		store = registryAndStore.store;
		( apiFetch as unknown as jest.Mock ).mockReset();
	} );

	describe( 'updateSetting', () => {
		it( 'should update a single setting value in edits state', () => {
			const groupId = 'test-group';
			const settingId = 'test-setting';
			const value = 'new-value';

			registry
				.dispatch( STORE_NAME )
				.updateSetting( groupId, settingId, value );

			expect( store.getState().edits[ groupId ]?.[ settingId ] ).toBe(
				value
			);
		} );

		it( 'should not affect other settings in the group', () => {
			const groupId = 'test-group';
			const setting1Id = 'test-setting-1';
			const setting2Id = 'test-setting-2';
			const value1 = 'new-value-1';
			const value2 = 'new-value-2';

			registry
				.dispatch( STORE_NAME )
				.updateSetting( groupId, setting1Id, value1 );
			registry
				.dispatch( STORE_NAME )
				.updateSetting( groupId, setting2Id, value2 );

			expect( store.getState().edits[ groupId ] ).toEqual( {
				[ setting1Id ]: value1,
				[ setting2Id ]: value2,
			} );
		} );
	} );

	describe( 'updateSettings', () => {
		it( 'should update multiple settings in edits state', () => {
			const groupId = 'test-group';
			const updates = [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			];

			registry.dispatch( STORE_NAME ).updateSettings( groupId, updates );

			expect( store.getState().edits[ groupId ] ).toEqual( {
				setting1: 'value1',
				setting2: 'value2',
			} );
		} );

		it( 'should merge with existing edits', () => {
			const groupId = 'test-group';

			// First update
			registry
				.dispatch( STORE_NAME )
				.updateSettings( groupId, [
					{ id: 'setting1', value: 'value1' },
				] );

			// Second update
			registry
				.dispatch( STORE_NAME )
				.updateSettings( groupId, [
					{ id: 'setting2', value: 'value2' },
				] );

			expect( store.getState().edits[ groupId ] ).toEqual( {
				setting1: 'value1',
				setting2: 'value2',
			} );
		} );

		it( 'should override existing edits for the same setting', () => {
			const groupId = 'test-group';

			registry
				.dispatch( STORE_NAME )
				.updateSettings( groupId, [
					{ id: 'setting1', value: 'old-value' },
				] );

			registry
				.dispatch( STORE_NAME )
				.updateSettings( groupId, [
					{ id: 'setting1', value: 'new-value' },
				] );

			expect( store.getState().edits[ groupId ] ).toEqual( {
				setting1: 'new-value',
			} );
		} );
	} );

	describe( 'revertSetting', () => {
		it( 'should remove setting from edits state', () => {
			const groupId = 'test-group';
			const settingId = 'test-setting';

			// First make an edit
			registry
				.dispatch( STORE_NAME )
				.updateSetting( groupId, settingId, 'new-value' );

			// Then revert it
			registry.dispatch( STORE_NAME ).revertSetting( groupId, settingId );

			expect(
				store.getState().edits[ groupId ]?.[ settingId ]
			).toBeUndefined();
		} );

		it( 'should remove group from edits if last setting is reverted', () => {
			const groupId = 'test-group';
			const settingId = 'test-setting';

			registry
				.dispatch( STORE_NAME )
				.updateSetting( groupId, settingId, 'new-value' );

			registry.dispatch( STORE_NAME ).revertSetting( groupId, settingId );

			expect( store.getState().edits[ groupId ] ).toBeUndefined();
		} );

		it( 'should not affect other settings when reverting', () => {
			const groupId = 'test-group';

			registry.dispatch( STORE_NAME ).updateSettings( groupId, [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			] );

			registry
				.dispatch( STORE_NAME )
				.revertSetting( groupId, 'setting1' );

			expect( store.getState().edits[ groupId ] ).toEqual( {
				setting2: 'value2',
			} );
		} );
	} );

	describe( 'revertGroup', () => {
		it( 'should remove all edits for a group', () => {
			const groupId = 'test-group';

			registry.dispatch( STORE_NAME ).updateSettings( groupId, [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			] );

			registry.dispatch( STORE_NAME ).revertGroup( groupId );

			expect( store.getState().edits[ groupId ] ).toBeUndefined();
		} );

		it( 'should not affect other groups', () => {
			const group1Id = 'test-group-1';
			const group2Id = 'test-group-2';

			registry
				.dispatch( STORE_NAME )
				.updateSettings( group1Id, [
					{ id: 'setting1', value: 'value1' },
				] );
			registry
				.dispatch( STORE_NAME )
				.updateSettings( group2Id, [
					{ id: 'setting2', value: 'value2' },
				] );

			registry.dispatch( STORE_NAME ).revertGroup( group1Id );

			expect( store.getState().edits[ group1Id ] ).toBeUndefined();
			expect( store.getState().edits[ group2Id ] ).toEqual( {
				setting2: 'value2',
			} );
		} );
	} );

	describe( 'saveSetting', () => {
		it( 'should not make API call if setting has no edits', async () => {
			const groupId = 'test-group';
			const settingId = 'test-setting';

			await registry
				.dispatch( STORE_NAME )
				.saveSetting( groupId, settingId );

			expect( apiFetch ).not.toHaveBeenCalled();
		} );

		it( 'should save setting and update state on success', async () => {
			const groupId = 'test-group';
			const settingId = 'test-setting';
			const value = 'new-value';
			const mockResult = createTestSetting( {
				id: settingId,
				value,
			} );

			registry
				.dispatch( STORE_NAME )
				.updateSetting( groupId, settingId, value );

			// Mock API response
			( apiFetch as unknown as jest.Mock ).mockResolvedValue(
				mockResult
			);

			await registry
				.dispatch( STORE_NAME )
				.saveSetting( groupId, settingId );

			expect( apiFetch ).toHaveBeenCalledWith( {
				path: expect.stringContaining( `${ groupId }/${ settingId }` ),
				method: 'PUT',
				data: { value },
			} );

			// Verify state updates
			expect(
				store.getState().settings[ groupId ]?.[ settingId ]
			).toEqual( mockResult );
			expect(
				store.getState().edits[ groupId ]?.[ settingId ]
			).toBeUndefined();
			expect(
				store.getState().isSaving.settings[ groupId ]?.[ settingId ]
			).toBe( false );
		} );

		it( 'should handle errors correctly', async () => {
			const groupId = 'test-group';
			const settingId = 'test-setting';
			const error = createTestError( 'API Error' );

			// Mock API error
			( apiFetch as unknown as jest.Mock ).mockRejectedValue( error );

			registry
				.dispatch( STORE_NAME )
				.updateSetting( groupId, settingId, '' );

			await expect(
				registry
					.dispatch( STORE_NAME )
					.saveSetting( groupId, settingId )
			).rejects.toThrow( error );

			// Verify error state
			expect( store.getState().errors[ groupId ]?.[ settingId ] ).toBe(
				error
			);
			expect(
				store.getState().isSaving.settings[ groupId ]?.[ settingId ]
			).toBe( false );
		} );
	} );

	describe( 'saveSettingsGroup', () => {
		it( 'should handle successful batch update', async () => {
			const groupId = 'test-group';
			const mockResults = {
				update: [
					createTestSetting( {
						id: 'setting1',
						value: 'value1',
						label: 'Setting 1',
					} ),
					createTestSetting( {
						id: 'setting2',
						value: 'value2',
						label: 'Setting 2',
					} ),
				],
			};

			// Mock API response
			( apiFetch as unknown as jest.Mock ).mockResolvedValue(
				mockResults
			);

			registry.dispatch( STORE_NAME ).updateSettings( groupId, [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			] );

			await registry.dispatch( STORE_NAME ).saveSettingsGroup( groupId );

			expect( apiFetch ).toHaveBeenCalledWith( {
				path: expect.stringContaining( groupId ),
				method: 'POST',
				data: {
					update: [
						{ id: 'setting1', value: 'value1' },
						{ id: 'setting2', value: 'value2' },
					],
				},
			} );

			// Verify state updates
			expect( store.getState().settings[ groupId ] ).toEqual( {
				setting1: mockResults.update[ 0 ],
				setting2: mockResults.update[ 1 ],
			} );
			expect( store.getState().isSaving.groups[ groupId ] ).toBe( false );
		} );

		it( 'should handle partial success in batch update', async () => {
			const groupId = 'test-group';
			const mockResults = {
				update: [
					createTestSetting( {
						id: 'setting1',
						value: 'value1',
						label: 'Setting 1',
					} ),
					{
						id: 'setting2',
						error: {
							code: 'invalid_value',
							message: 'Invalid value',
						},
					},
				],
			};

			registry.dispatch( STORE_NAME ).updateSettings( groupId, [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			] );

			// Mock API response
			( apiFetch as unknown as jest.Mock ).mockResolvedValue(
				mockResults
			);

			await expect(
				registry.dispatch( STORE_NAME ).saveSettingsGroup( groupId )
			).rejects.toThrow( 'Failed to update some settings' );

			// Verify successful update
			expect( store.getState().settings[ groupId ]?.setting1 ).toEqual(
				mockResults.update[ 0 ]
			);

			// Verify error state
			expect( store.getState().errors[ groupId ]?.setting2 ).toEqual(
				( mockResults.update[ 1 ] as { error: APIError } ).error
			);
			expect( store.getState().isSaving.groups[ groupId ] ).toBe( false );
		} );

		it( 'should handle complete failure in batch update', async () => {
			const groupId = 'test-group';
			const error = createTestError( 'Network Error' );

			registry.dispatch( STORE_NAME ).updateSettings( groupId, [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			] );

			// Mock API error
			( apiFetch as unknown as jest.Mock ).mockRejectedValue( error );

			await expect(
				registry.dispatch( STORE_NAME ).saveSettingsGroup( groupId )
			).rejects.toThrow( error );

			// Verify error state
			expect( store.getState().errors[ groupId ] ).toBeTruthy();
			expect( store.getState().isSaving.groups[ groupId ] ).toBe( false );
		} );

		it( 'should not make API call if group has no edits', async () => {
			const groupId = 'test-group';

			await registry.dispatch( STORE_NAME ).saveSettingsGroup( groupId );

			expect( apiFetch ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'updateSetting with save', () => {
		it( 'should update and save setting in one call', async () => {
			const groupId = 'test-group';
			const settingId = 'test-setting';
			const value = 'new-value';
			const mockResult = createTestSetting( {
				id: settingId,
				value,
			} );

			// Mock API response
			( apiFetch as unknown as jest.Mock ).mockResolvedValue(
				mockResult
			);

			await registry
				.dispatch( STORE_NAME )
				.updateSetting( groupId, settingId, value, { save: true } );

			// Verify API call was made
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: expect.stringContaining( `${ groupId }/${ settingId }` ),
				method: 'PUT',
				data: { value },
			} );

			// Verify state updates after save
			expect(
				store.getState().settings[ groupId ]?.[ settingId ]
			).toEqual( mockResult );
			expect(
				store.getState().edits[ groupId ]?.[ settingId ]
			).toBeUndefined();
			expect(
				store.getState().isSaving.settings[ groupId ]?.[ settingId ]
			).toBe( false );
		} );

		it( 'should handle errors when updating and saving', async () => {
			const groupId = 'test-group';
			const settingId = 'test-setting';
			const value = 'new-value';
			const error = createTestError( 'API Error' );

			// Mock API error
			( apiFetch as unknown as jest.Mock ).mockRejectedValue( error );

			await expect(
				registry
					.dispatch( STORE_NAME )
					.updateSetting( groupId, settingId, value, { save: true } )
			).rejects.toThrow( error );

			// Verify the setting was updated in edits state despite the error
			expect( store.getState().edits[ groupId ]?.[ settingId ] ).toBe(
				value
			);

			// Verify error state
			expect( store.getState().errors[ groupId ]?.[ settingId ] ).toBe(
				error
			);
			expect(
				store.getState().isSaving.settings[ groupId ]?.[ settingId ]
			).toBe( false );
		} );
	} );

	describe( 'updateSettings with save', () => {
		it( 'should update and save multiple settings in one call', async () => {
			const groupId = 'test-group';
			const mockResults = {
				update: [
					createTestSetting( {
						id: 'setting1',
						value: 'value1',
						label: 'Setting 1',
					} ),
					createTestSetting( {
						id: 'setting2',
						value: 'value2',
						label: 'Setting 2',
					} ),
				],
			};

			const updates = [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			];

			// Mock API response
			( apiFetch as unknown as jest.Mock ).mockResolvedValue(
				mockResults
			);

			await registry
				.dispatch( STORE_NAME )
				.updateSettings( groupId, updates, { save: true } );

			// Verify API call was made
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: expect.stringContaining( groupId ),
				method: 'POST',
				data: {
					update: updates,
				},
			} );

			// Verify state updates after save
			expect( store.getState().settings[ groupId ] ).toEqual( {
				setting1: mockResults.update[ 0 ],
				setting2: mockResults.update[ 1 ],
			} );
			expect( store.getState().edits[ groupId ] ).toBeUndefined();
			expect( store.getState().isSaving.groups[ groupId ] ).toBe( false );
		} );

		it( 'should handle partial success when updating and saving multiple settings', async () => {
			const groupId = 'test-group';
			const mockResults = {
				update: [
					createTestSetting( {
						id: 'setting1',
						value: 'value1',
						label: 'Setting 1',
					} ),
					{
						id: 'setting2',
						error: {
							code: 'invalid_value',
							message: 'Invalid value',
						},
					},
				],
			};

			const updates = [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'invalid' },
			];

			// Mock API response
			( apiFetch as unknown as jest.Mock ).mockResolvedValue(
				mockResults
			);

			await expect(
				registry
					.dispatch( STORE_NAME )
					.updateSettings( groupId, updates, { save: true } )
			).rejects.toThrow( 'Failed to update some settings' );

			// Verify settings were updated in edits state
			expect( store.getState().edits[ groupId ] ).toEqual( {
				setting2: 'invalid',
			} );

			// Verify successful update
			expect( store.getState().settings[ groupId ]?.setting1 ).toEqual(
				mockResults.update[ 0 ]
			);

			// Verify error state
			expect( store.getState().errors[ groupId ]?.setting2 ).toEqual(
				( mockResults.update[ 1 ] as { error: APIError } ).error
			);
			expect( store.getState().isSaving.groups[ groupId ] ).toBe( false );
		} );

		it( 'should handle complete failure when updating and saving multiple settings', async () => {
			const groupId = 'test-group';
			const error = createTestError( 'Network Error' );
			const updates = [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			];

			// Mock API error
			( apiFetch as unknown as jest.Mock ).mockRejectedValue( error );

			await expect(
				registry
					.dispatch( STORE_NAME )
					.updateSettings( groupId, updates, { save: true } )
			).rejects.toThrow( error );

			// Verify settings were updated in edits state despite the error
			expect( store.getState().edits[ groupId ] ).toEqual( {
				setting1: 'value1',
				setting2: 'value2',
			} );

			// Verify error state
			expect( store.getState().errors[ groupId ] ).toBeTruthy();
			expect( store.getState().isSaving.groups[ groupId ] ).toBe( false );
		} );
	} );
} );
