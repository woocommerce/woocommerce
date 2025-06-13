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

	describe( 'editSetting', () => {
		it( 'should update a single setting value in edits state', async () => {
			const groupId = 'test-group';
			const settingId = 'test-setting';
			const value = 'new-value';

			await registry
				.dispatch( STORE_NAME )
				.editSetting( groupId, settingId, value );

			expect( store.getState().edits[ groupId ]?.[ settingId ] ).toBe(
				value
			);
		} );

		it( 'should not affect other settings in the group', async () => {
			const groupId = 'test-group';
			const setting1Id = 'test-setting-1';
			const setting2Id = 'test-setting-2';
			const value1 = 'new-value-1';
			const value2 = 'new-value-2';

			await registry
				.dispatch( STORE_NAME )
				.editSetting( groupId, setting1Id, value1 );
			await registry
				.dispatch( STORE_NAME )
				.editSetting( groupId, setting2Id, value2 );

			expect( store.getState().edits[ groupId ] ).toEqual( {
				[ setting1Id ]: value1,
				[ setting2Id ]: value2,
			} );
		} );
	} );

	describe( 'editSettings', () => {
		it( 'should update multiple settings in edits state', async () => {
			const groupId = 'test-group';
			const updates = [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			];

			await registry
				.dispatch( STORE_NAME )
				.editSettings( groupId, updates );

			expect( store.getState().edits[ groupId ] ).toEqual( {
				setting1: 'value1',
				setting2: 'value2',
			} );
		} );

		it( 'should merge with existing edits', async () => {
			const groupId = 'test-group';

			// First update
			await registry
				.dispatch( STORE_NAME )
				.editSettings( groupId, [
					{ id: 'setting1', value: 'value1' },
				] );

			// Second update
			await registry
				.dispatch( STORE_NAME )
				.editSettings( groupId, [
					{ id: 'setting2', value: 'value2' },
				] );

			expect( store.getState().edits[ groupId ] ).toEqual( {
				setting1: 'value1',
				setting2: 'value2',
			} );
		} );

		it( 'should override existing edits for the same setting', async () => {
			const groupId = 'test-group';

			await registry
				.dispatch( STORE_NAME )
				.editSettings( groupId, [
					{ id: 'setting1', value: 'old-value' },
				] );

			await registry
				.dispatch( STORE_NAME )
				.editSettings( groupId, [
					{ id: 'setting1', value: 'new-value' },
				] );

			expect( store.getState().edits[ groupId ] ).toEqual( {
				setting1: 'new-value',
			} );
		} );
	} );

	describe( 'revertEditedSetting', () => {
		it( 'should remove setting from edits state', async () => {
			const groupId = 'test-group';
			const settingId = 'test-setting';

			// First make an edit
			await registry
				.dispatch( STORE_NAME )
				.editSetting( groupId, settingId, 'new-value' );

			// Then revert it
			registry
				.dispatch( STORE_NAME )
				.revertEditedSetting( groupId, settingId );

			expect(
				store.getState().edits[ groupId ]?.[ settingId ]
			).toBeUndefined();
		} );

		it( 'should remove group from edits if last setting is reverted', () => {
			const groupId = 'test-group';
			const settingId = 'test-setting';

			registry
				.dispatch( STORE_NAME )
				.editSetting( groupId, settingId, 'new-value' );

			registry
				.dispatch( STORE_NAME )
				.revertEditedSetting( groupId, settingId );

			expect( store.getState().edits[ groupId ] ).toBeUndefined();
		} );

		it( 'should not affect other settings when reverting', async () => {
			const groupId = 'test-group';

			await registry.dispatch( STORE_NAME ).editSettings( groupId, [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			] );

			registry
				.dispatch( STORE_NAME )
				.revertEditedSetting( groupId, 'setting1' );

			expect( store.getState().edits[ groupId ] ).toEqual( {
				setting2: 'value2',
			} );
		} );
	} );

	describe( 'revertEditedSettingsGroup', () => {
		it( 'should remove all edits for a group', () => {
			const groupId = 'test-group';

			registry.dispatch( STORE_NAME ).editSettings( groupId, [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			] );

			registry
				.dispatch( STORE_NAME )
				.revertEditedSettingsGroup( groupId );

			expect( store.getState().edits[ groupId ] ).toBeUndefined();
		} );

		it( 'should not affect other groups', async () => {
			const group1Id = 'test-group-1';
			const group2Id = 'test-group-2';

			await registry
				.dispatch( STORE_NAME )
				.editSettings( group1Id, [
					{ id: 'setting1', value: 'value1' },
				] );
			await registry
				.dispatch( STORE_NAME )
				.editSettings( group2Id, [
					{ id: 'setting2', value: 'value2' },
				] );

			registry
				.dispatch( STORE_NAME )
				.revertEditedSettingsGroup( group1Id );

			expect( store.getState().edits[ group1Id ] ).toBeUndefined();
			expect( store.getState().edits[ group2Id ] ).toEqual( {
				setting2: 'value2',
			} );
		} );
	} );

	describe( 'saveEditedSetting', () => {
		it( 'should not make API call if setting has no edits', async () => {
			const groupId = 'test-group';
			const settingId = 'test-setting';

			await registry
				.dispatch( STORE_NAME )
				.saveEditedSetting( groupId, settingId );

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

			await registry
				.dispatch( STORE_NAME )
				.editSetting( groupId, settingId, value );

			// Mock API response
			( apiFetch as unknown as jest.Mock ).mockResolvedValue(
				mockResult
			);

			await registry
				.dispatch( STORE_NAME )
				.saveEditedSetting( groupId, settingId );

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

			await registry
				.dispatch( STORE_NAME )
				.editSetting( groupId, settingId, '' );

			await expect(
				registry
					.dispatch( STORE_NAME )
					.saveEditedSetting( groupId, settingId )
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

	describe( 'saveEditedSettingsGroup', () => {
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

			await registry.dispatch( STORE_NAME ).editSettings( groupId, [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			] );

			await registry
				.dispatch( STORE_NAME )
				.saveEditedSettingsGroup( groupId );

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

			await registry.dispatch( STORE_NAME ).editSettings( groupId, [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			] );

			// Mock API response
			( apiFetch as unknown as jest.Mock ).mockResolvedValue(
				mockResults
			);

			expect( store.getState().edits[ groupId ] ).toEqual( {
				setting1: 'value1',
				setting2: 'value2',
			} );

			await expect(
				registry
					.dispatch( STORE_NAME )
					.saveEditedSettingsGroup( groupId )
			).rejects.toThrow( 'Failed to update some settings' );

			// Verify successful update
			expect( store.getState().settings[ groupId ]?.setting1 ).toEqual(
				mockResults.update[ 0 ]
			);

			// Verify only failed settings are in the edits state
			expect( store.getState().edits[ groupId ] ).toEqual( {
				setting2: 'value2',
			} );

			// Verify error state
			expect( store.getState().errors[ groupId ]?.setting2 ).toEqual(
				( mockResults.update[ 1 ] as { error: APIError } ).error
			);
			expect( store.getState().isSaving.groups[ groupId ] ).toBe( false );
		} );

		it( 'should handle complete failure in batch update', async () => {
			const groupId = 'test-group';
			const error = createTestError( 'Network Error' );

			await registry.dispatch( STORE_NAME ).editSettings( groupId, [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			] );

			// Mock API error
			( apiFetch as unknown as jest.Mock ).mockRejectedValue( error );

			await expect(
				registry
					.dispatch( STORE_NAME )
					.saveEditedSettingsGroup( groupId )
			).rejects.toThrow( error );

			// Verify error state
			expect( store.getState().errors[ groupId ] ).toBeTruthy();
			expect( store.getState().isSaving.groups[ groupId ] ).toBe( false );
		} );

		it( 'should not make API call if group has no edits', async () => {
			const groupId = 'test-group';

			await registry
				.dispatch( STORE_NAME )
				.saveEditedSettingsGroup( groupId );

			expect( apiFetch ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'saveSetting', () => {
		it( 'should save setting and update state on success', async () => {
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
				.saveSetting( groupId, settingId, value );

			// Verify API call
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
				store.getState().isSaving.settings[ groupId ]?.[ settingId ]
			).toBe( false );
			expect(
				store.getState().errors[ groupId ]?.[ settingId ]
			).toBeUndefined();
		} );

		it( 'should handle errors when saving setting', async () => {
			const groupId = 'test-group';
			const settingId = 'test-setting';
			const value = 'new-value';
			const mockError = createTestError();

			// Mock API error
			( apiFetch as unknown as jest.Mock ).mockRejectedValue( mockError );

			await expect(
				registry
					.dispatch( STORE_NAME )
					.saveSetting( groupId, settingId, value )
			).rejects.toEqual( mockError );

			// Verify state updates
			expect(
				store.getState().isSaving.settings[ groupId ]?.[ settingId ]
			).toBe( false );
			expect( store.getState().errors[ groupId ]?.[ settingId ] ).toBe(
				mockError
			);
		} );
	} );

	describe( 'saveSettingsGroup', () => {
		it( 'should save multiple settings and update state on success', async () => {
			const groupId = 'test-group';
			const updates = [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			];
			const mockResults = {
				update: updates.map( ( update ) =>
					createTestSetting( {
						id: update.id,
						value: update.value,
					} )
				),
			};

			// Mock API response
			( apiFetch as unknown as jest.Mock ).mockResolvedValue(
				mockResults
			);

			await registry
				.dispatch( STORE_NAME )
				.saveSettingsGroup( groupId, updates );

			// Verify API call
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: expect.stringContaining( `${ groupId }/batch` ),
				method: 'POST',
				data: { update: updates },
			} );

			// Verify state updates
			updates.forEach( ( update ) => {
				expect(
					store.getState().settings[ groupId ]?.[ update.id ]
				).toEqual(
					expect.objectContaining( {
						id: update.id,
						value: update.value,
					} )
				);
			} );
			expect( store.getState().isSaving.groups[ groupId ] ).toBe( false );
			expect( store.getState().errors[ groupId ]?.null ).toBeUndefined();
		} );

		it( 'should handle object format for updates', async () => {
			const groupId = 'test-group';
			const updates = {
				setting1: 'value1',
				setting2: 'value2',
			};
			const expectedUpdates = [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			];
			const mockResults = {
				update: expectedUpdates.map( ( update ) =>
					createTestSetting( {
						id: update.id,
						value: update.value,
					} )
				),
			};

			// Mock API response
			( apiFetch as unknown as jest.Mock ).mockResolvedValue(
				mockResults
			);

			await registry
				.dispatch( STORE_NAME )
				.saveSettingsGroup( groupId, updates );

			// Verify API call used correct format
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: expect.stringContaining( `${ groupId }/batch` ),
				method: 'POST',
				data: { update: expectedUpdates },
			} );
		} );

		it( 'should handle partial failures in batch update', async () => {
			const groupId = 'test-group';
			const updates = [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			];
			const mockError: APIError = {
				code: 'invalid_value',
				message: 'Invalid value',
			};
			const mockResults = {
				update: [
					createTestSetting( {
						id: 'setting1',
						value: 'value1',
					} ),
					{ id: 'setting2', error: mockError },
				],
			};

			// Mock API response with partial success
			( apiFetch as unknown as jest.Mock ).mockResolvedValue(
				mockResults
			);

			await expect(
				registry
					.dispatch( STORE_NAME )
					.saveSettingsGroup( groupId, updates )
			).rejects.toThrow( 'Failed to update some settings' );

			// Verify successful update was processed
			expect( store.getState().settings[ groupId ]?.setting1 ).toEqual(
				expect.objectContaining( {
					id: 'setting1',
					value: 'value1',
				} )
			);

			// Verify error was set for failed setting
			expect( store.getState().errors[ groupId ]?.setting2 ).toEqual(
				mockError
			);
		} );

		it( 'should handle complete failure in batch update', async () => {
			const groupId = 'test-group';
			const updates = [
				{ id: 'setting1', value: 'value1' },
				{ id: 'setting2', value: 'value2' },
			];
			const mockError = createTestError();

			// Mock API error
			( apiFetch as unknown as jest.Mock ).mockRejectedValue( mockError );

			await expect(
				registry
					.dispatch( STORE_NAME )
					.saveSettingsGroup( groupId, updates )
			).rejects.toEqual( mockError );

			// Verify error state
			expect( store.getState().errors[ groupId ].all ).toBe( mockError );
			expect( store.getState().isSaving.groups[ groupId ] ).toBe( false );
		} );
	} );
} );
