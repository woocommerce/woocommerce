/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import type { createRegistry } from '@wordpress/data';
import type { CurriedSelectorsOf } from '@wordpress/data/build-types/types';

/**
 * Internal dependencies
 */
import { TYPES } from './action-types';
import type {
	Setting,
	SettingValue,
	SettingsGroup,
	APIError,
	BatchSettingsError,
	SettingUpdate,
	SettingsUpdateObject,
} from './types';
import { NAMESPACE } from '../constants';
import { store } from './';

type WPDataRegistry = ReturnType< typeof createRegistry >;

export type ThunkArgs = {
	select: CurriedSelectorsOf< typeof store >;
	dispatch: ActionDispatchersForThunk;
	registry: WPDataRegistry;
};

/**
 * Action creator for receiving groups.
 *
 * @param groups - The groups to receive.
 * @return The action object.
 */
export const receiveGroups = ( groups: SettingsGroup[] ) => ( {
	type: TYPES.RECEIVE_GROUPS,
	groups,
} );

/**
 * Action creator for receiving settings.
 *
 * @param groupId  - The group ID.
 * @param settings - The settings to receive.
 * @return The action object.
 */
export const receiveSettings = ( groupId: string, settings: Setting[] ) => ( {
	type: TYPES.RECEIVE_SETTINGS,
	groupId,
	settings,
} );

/**
 * Action creator for updating a setting.
 *
 * @param groupId   - The group ID.
 * @param settingId - The setting ID.
 * @param value     - The value to update the setting to.
 * @return The action object.
 */
const createUpdateSettingAction = (
	groupId: string,
	settingId: string,
	value: SettingValue
) => ( {
	type: TYPES.UPDATE_SETTING,
	groupId,
	settingId,
	value,
} );

/**
 * Updates a single setting value. If save is true, the setting will be immediately saved.
 *
 * @param groupId      - The settings group ID
 * @param settingId    - The setting ID
 * @param value        - The new setting value
 * @param options      - Options object
 * @param options.save - Whether to immediately save the setting (default: false)
 */
export function editSetting(
	groupId: string,
	settingId: string,
	value: SettingValue,
	{ save = false }: { save?: boolean } = {}
) {
	return async ( { dispatch }: ThunkArgs ) => {
		dispatch( createUpdateSettingAction( groupId, settingId, value ) );

		if ( save ) {
			return dispatch.saveEditedSetting( groupId, settingId );
		}
	};
}

/**
 * Internal action creator for array format
 */
const editSettingsArray = ( groupId: string, updates: SettingUpdate[] ) => ( {
	type: TYPES.UPDATE_SETTINGS,
	groupId,
	updates,
} );

/**
 * Updates multiple settings at once. If save is true, the settings will be immediately saved.
 *
 * @param groupId      - The settings group ID
 * @param updates      - Array of setting updates or object with setting IDs as keys and values as values
 * @param options      - Options object
 * @param options.save - Whether to immediately save the settings (default: false)
 */
export function editSettings(
	groupId: string,
	updates: SettingUpdate[] | SettingsUpdateObject,
	{ save = false }: { save?: boolean } = {}
) {
	return async ( { dispatch }: ThunkArgs ) => {
		// Convert object format to array format if needed
		const updatesArray = Array.isArray( updates )
			? updates
			: Object.entries( updates ).map( ( [ id, value ] ) => ( {
					id,
					value,
			  } ) );

		dispatch( editSettingsArray( groupId, updatesArray ) );

		if ( save ) {
			return dispatch.saveEditedSettingsGroup( groupId );
		}
	};
}

/**
 * Action creator for setting the saving state.
 *
 * @param groupId   - The group ID.
 * @param settingId - The setting ID.
 * @param isSaving  - Whether the setting is saving.
 * @return The action object.
 */
export const setSaving = (
	groupId: string,
	settingId: string | null,
	isSaving: boolean
) => ( {
	type: TYPES.SET_SAVING,
	groupId,
	settingId,
	isSaving,
} );

/**
 * Action creator for setting the error state.
 *
 * @param groupId   - The group ID.
 * @param settingId - The setting ID.
 * @param error     - The error to set.
 * @return The action object.
 */
export const setError = (
	groupId: string,
	settingId: string | null,
	error: unknown
) => ( {
	type: TYPES.SET_ERROR,
	groupId,
	settingId,
	error,
} );

/**
 * Action creator for reverting a setting.
 *
 * @param groupId   - The group ID.
 * @param settingId - The setting ID.
 * @return The action object.
 */
export const revertSetting = ( groupId: string, settingId: string ) => ( {
	type: TYPES.REVERT_SETTING,
	groupId,
	settingId,
} );

/**
 * Action creator for reverting a group.
 *
 * @param groupId - The group ID.
 * @return The action object.
 */
export const revertGroup = ( groupId: string ) => ( {
	type: TYPES.REVERT_GROUP,
	groupId,
} );

/**
 * Action creator for saving a settings group.
 *
 * @param groupId - The group ID.
 * @return The action object.
 */
export const saveEditedSettingsGroup =
	( groupId: string ) =>
	async ( { select, dispatch }: ThunkArgs ) => {
		dispatch( setSaving( groupId, null, true ) );

		const editedSettings = select
			.getEditedSettingIds( groupId )
			.map( ( settingId: string ) => ( {
				id: settingId,
				value: select.getSettingValue( groupId, settingId ),
			} ) );

		if ( editedSettings.length === 0 ) {
			// If there are no edited settings, don't make an API call.
			return;
		}

		try {
			const results = await apiFetch< {
				update: ( Setting | { id: string; error: APIError } )[];
			} >( {
				path: `${ NAMESPACE }/settings/${ groupId }/batch`,
				method: 'POST',
				data: { update: editedSettings },
			} );

			// Handle individual setting errors in a 200 response
			const successfulUpdates: Setting[] = [];
			const errors: Array< { id: string; error: APIError } > = [];

			results.update.forEach( ( result ) => {
				if (
					'error' in result &&
					result.error &&
					typeof result.error === 'object' &&
					'code' in result.error
				) {
					// If the result has an error, collect it
					errors.push( {
						id: result.id,
						error: result.error,
					} );
					dispatch( setError( groupId, result.id, result.error ) );
				} else {
					// If no error, add to successful updates
					successfulUpdates.push( result as Setting );
				}
			} );

			// Only update settings that were successfully changed
			if ( successfulUpdates.length > 0 ) {
				dispatch( receiveSettings( groupId, successfulUpdates ) );
			}

			// If there were any errors, throw an error with details
			if ( errors.length > 0 ) {
				const error = new Error(
					'Failed to update some settings'
				) as BatchSettingsError;
				error.settingErrors = errors;
				throw error;
			}

			return results;
		} catch ( error ) {
			const partialError =
				error instanceof Error && 'settingErrors' in error;

			// Only set the error for the entire group if all settings failed to update
			if ( ! partialError ) {
				dispatch( setError( groupId, null, error ) );
			}

			throw error;
		} finally {
			dispatch( setSaving( groupId, null, false ) );
		}
	};

/**
 * Action creator for saving a setting.
 *
 * @param groupId   - The group ID.
 * @param settingId - The setting ID.
 * @return The action object.
 */
export const saveEditedSetting =
	( groupId: string, settingId: string ) =>
	async ( { select, dispatch }: ThunkArgs ) => {
		// Check if this setting has any edits
		const editedSettingIds = select.getEditedSettingIds( groupId );
		if ( ! editedSettingIds.includes( settingId ) ) {
			return;
		}

		const value = select.getSettingValue( groupId, settingId );
		dispatch( setSaving( groupId, settingId, true ) );

		try {
			const result = await apiFetch< Setting >( {
				path: `${ NAMESPACE }/settings/${ groupId }/${ settingId }`,
				method: 'PUT',
				data: { value },
			} );

			dispatch( receiveSettings( groupId, [ result ] ) );
			return result;
		} catch ( error ) {
			dispatch( setError( groupId, settingId, error ) );
			throw error;
		} finally {
			dispatch( setSaving( groupId, settingId, false ) );
		}
	};

// Return type of all action creators
export type Actions = ReturnType<
	| typeof receiveGroups
	| typeof receiveSettings
	| typeof setSaving
	| typeof setError
	| typeof revertSetting
	| typeof revertGroup
	| typeof createUpdateSettingAction
	| typeof editSettingsArray
>;

export type ActionDispatchersForThunk = {
	receiveGroups: typeof receiveGroups;
	receiveSettings: typeof receiveSettings;
	setSaving: typeof setSaving;
	setError: typeof setError;
	revertSetting: typeof revertSetting;
	revertGroup: typeof revertGroup;
	editSetting: typeof editSetting;
	editSettings: typeof editSettings;
	saveEditedSetting: typeof saveEditedSetting;
	saveEditedSettingsGroup: typeof saveEditedSettingsGroup;
	< T = Record< string, unknown > >( args: T ): void;
};
