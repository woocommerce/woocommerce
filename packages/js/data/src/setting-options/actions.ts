/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import type { createRegistry } from '@wordpress/data';
import type { CurriedSelectorsOf } from '@wordpress/data/build-types/types';
import type CreateLocksActions from '@wordpress/core-data/build-types/locks/actions';
// @ts-expect-error WP core data doesn't explicitly export the actions
// eslint-disable-next-line @woocommerce/dependency-group
import createLocksActions from '@wordpress/core-data/build/locks/actions';

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
	SettingEdit,
	SettingsEditObject,
	SettingsState,
} from './types';
import { NAMESPACE } from '../constants';
import { store, STORE_NAME } from './';

type WPDataRegistry = ReturnType< typeof createRegistry >;

type CurriedState< F > = F extends (
	state: SettingsState,
	...args: infer P
) => infer R
	? ( ...args: P ) => R
	: F;

type Resolvers = typeof import('./resolvers');

export type ThunkArgs = {
	select: CurriedSelectorsOf< typeof store >;
	resolveSelect: CurriedState< Resolvers >;
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
 * Action creator for editing a setting.
 *
 * @param groupId   - The settings group ID
 * @param settingId - The setting ID
 * @param value     - The new setting value
 */
const createEditSettingAction = (
	groupId: string,
	settingId: string,
	value: SettingValue
) => ( {
	type: TYPES.EDIT_SETTING,
	groupId,
	settingId,
	value,
} );

/**
 * Updates a single setting value.
 *
 * @param groupId   - The settings group ID
 * @param settingId - The setting ID
 * @param value     - The new setting value
 */
export const editSetting =
	( groupId: string, settingId: string, value: SettingValue ) =>
	async ( { resolveSelect, dispatch }: ThunkArgs ) => {
		// Ensure setting is fetched before editing
		await resolveSelect.getSettingValue( groupId, settingId );
		dispatch( createEditSettingAction( groupId, settingId, value ) );
	};

/**
 * Action creator for editing multiple settings.
 *
 * @param groupId - The settings group ID
 * @param updates - Array of setting updates or object with setting IDs as keys and values as values
 */
const createEditSettingsAction = (
	groupId: string,
	updates: SettingEdit[]
) => ( {
	type: TYPES.EDIT_SETTINGS,
	groupId,
	updates,
} );

/**
 * Validates a setting edit.
 *
 * @param edit - The setting edit to validate.
 * @return Whether the setting edit is valid.
 */
const validateSettingEdit = ( edit: SettingEdit ): boolean => {
	return (
		typeof edit.id === 'string' &&
		edit.id.length > 0 &&
		edit.value !== undefined
	);
};

/**
 * Updates multiple settings at once.
 *
 * @param groupId - The settings group ID
 * @param updates - Array of setting updates or object with setting IDs as keys and values as values
 */
export const editSettings =
	( groupId: string, updates: SettingEdit[] | SettingsEditObject ) =>
	async ( { resolveSelect, dispatch }: ThunkArgs ) => {
		// Ensure settings are fetched before editing
		await resolveSelect.getSettings( groupId );

		// Convert object format to array format if needed
		const updatesArray = Array.isArray( updates )
			? updates
			: Object.entries( updates ).map( ( [ id, value ] ) => ( {
					id,
					value,
			  } ) );

		if ( ! updatesArray.every( validateSettingEdit ) ) {
			throw new Error( 'Invalid setting edit payload' );
		}

		dispatch( createEditSettingsAction( groupId, updatesArray ) );
	};

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
export const revertEditedSetting = ( groupId: string, settingId: string ) => ( {
	type: TYPES.REVERT_EDITED_SETTING,
	groupId,
	settingId,
} );

/**
 * Action creator for reverting a group.
 *
 * @param groupId - The group ID.
 * @return The action object.
 */
export const revertEditedSettingsGroup = ( groupId: string ) => ( {
	type: TYPES.REVERT_EDITED_SETTINGS_GROUP,
	groupId,
} );

/**
 * Internal helper to save a single setting.
 */
const saveSettingRequest = async (
	groupId: string,
	settingId: string,
	value: SettingValue,
	dispatch: ActionDispatchersForThunk
): Promise< Setting > => {
	const lock = await dispatch.__unstableAcquireStoreLock(
		STORE_NAME,
		[ 'settings', groupId, settingId ],
		{ exclusive: true }
	);

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
		dispatch.__unstableReleaseStoreLock( lock );
	}
};

/**
 * Internal helper to save multiple settings.
 */
const saveSettingsGroupRequest = async (
	groupId: string,
	updates: SettingEdit[],
	dispatch: ActionDispatchersForThunk
) => {
	const lock = await dispatch.__unstableAcquireStoreLock(
		STORE_NAME,
		[ 'settings', groupId ],
		{ exclusive: true }
	);

	dispatch( setSaving( groupId, null, true ) );

	try {
		const results = await apiFetch< {
			update: ( Setting | { id: string; error: APIError } )[];
		} >( {
			path: `${ NAMESPACE }/settings/${ groupId }/batch`,
			method: 'POST',
			data: { update: updates },
		} );

		// Handle individual setting errors in a 200 response
		const successfulUpdates: Setting[] = [];
		const errors: Array< { id: string; error: APIError } > = [];

		results.update.forEach( ( result ) => {
			if (
				'error' in result &&
				result.error &&
				typeof result.error === 'object'
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
		const partialError = error instanceof Error && 'settingErrors' in error;

		// All settings failed to update, set the error for the entire group
		if ( ! partialError ) {
			dispatch( setError( groupId, null, error ) );
		}

		throw error;
	} finally {
		dispatch( setSaving( groupId, null, false ) );
		dispatch.__unstableReleaseStoreLock( lock );
	}
};

/**
 * Saves multiple settings at once for a group.
 *
 * @param groupId - The settings group ID
 * @param updates - Array of setting updates or object with setting IDs as keys and values as values
 * @return The action object.
 */
export const saveSettingsGroup =
	( groupId: string, updates: SettingEdit[] | SettingsEditObject ) =>
	async ( { dispatch }: ThunkArgs ) => {
		const updatesArray = Array.isArray( updates )
			? updates
			: Object.entries( updates ).map( ( [ id, value ] ) => ( {
					id,
					value,
			  } ) );

		return saveSettingsGroupRequest( groupId, updatesArray, dispatch );
	};

/**
 * Saves a single setting.
 *
 * @param groupId   - The group ID
 * @param settingId - The setting ID
 * @param value     - The value to save
 * @return The action object
 */
export const saveSetting =
	( groupId: string, settingId: string, value: SettingValue ) =>
	async ( { dispatch }: ThunkArgs ) => {
		return saveSettingRequest( groupId, settingId, value, dispatch );
	};

/**
 * Action creator for saving edited settings in a group.
 *
 * @param groupId - The group ID.
 * @return The action object.
 */
export const saveEditedSettingsGroup =
	( groupId: string ) =>
	async ( { select, dispatch }: ThunkArgs ) => {
		const editedSettings = select
			.getEditedSettingIds( groupId )
			.map( ( settingId: string ) => ( {
				id: settingId,
				value: select.getSettingValue( groupId, settingId, {
					includeEdits: true,
				} ),
			} ) );

		if ( editedSettings.length === 0 ) {
			// If there are no edited settings, don't make an API call.
			return;
		}

		return saveSettingsGroupRequest( groupId, editedSettings, dispatch );
	};

/**
 * Action creator for saving an edited setting.
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

		const value = select.getSettingValue( groupId, settingId, {
			includeEdits: true,
		} );
		return saveSettingRequest( groupId, settingId, value, dispatch );
	};

const lockActions = createLocksActions() as ReturnType<
	typeof CreateLocksActions
>;
export const __unstableAcquireStoreLock =
	lockActions.__unstableAcquireStoreLock;
export const __unstableReleaseStoreLock =
	lockActions.__unstableReleaseStoreLock;

// Return type of all action creators
export type Actions = ReturnType<
	| typeof receiveGroups
	| typeof receiveSettings
	| typeof setSaving
	| typeof setError
	| typeof revertEditedSetting
	| typeof revertEditedSettingsGroup
	| typeof createEditSettingAction
	| typeof createEditSettingsAction
>;

export type ActionDispatchersForThunk = {
	receiveGroups: typeof receiveGroups;
	receiveSettings: typeof receiveSettings;
	setSaving: typeof setSaving;
	setError: typeof setError;
	revertEditedSetting: typeof revertEditedSetting;
	revertEditedSettingsGroup: typeof revertEditedSettingsGroup;
	editSetting: typeof editSetting;
	editSettings: typeof editSettings;
	saveEditedSetting: typeof saveEditedSetting;
	saveEditedSettingsGroup: typeof saveEditedSettingsGroup;
	saveSetting: typeof saveSetting;
	saveSettingsGroup: typeof saveSettingsGroup;
	< T = Record< string, unknown > >( args: T ): void;
} & ReturnType< typeof CreateLocksActions >;
