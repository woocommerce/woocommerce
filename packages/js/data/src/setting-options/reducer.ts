/**
 * Internal dependencies
 */
import { TYPES } from './action-types';
import type { Actions } from './actions';
import type { SettingsState, Setting, SettingValue } from './types';

/**
 * The default state for the settings options store.
 */
export const DEFAULT_STATE: SettingsState = {
	groups: [],
	settings: {},
	edits: {},
	isSaving: {
		groups: {},
		settings: {},
	},
	errors: {},
};

/**
 * Ensures a group exists in the given object. If the group doesn't exist,
 * it creates an empty object for that group.
 *
 * @param obj     - The object to ensure the group exists in (e.g., settings, edits, errors)
 * @param groupId - The group ID to ensure exists
 * @return The group object, either existing or newly created
 */
const ensureGroupExists = < T >(
	obj: { [ groupId: string ]: T | undefined },
	groupId: string
): T => {
	if ( ! obj[ groupId ] ) {
		obj[ groupId ] = {} as T;
	}
	return obj[ groupId ] as T;
};

/**
 * Removes empty group objects to keep the state clean.
 * A group is considered empty when it has no properties.
 *
 * @param obj     - The object to clean up (e.g., edits, errors)
 * @param groupId - The group ID to check and potentially remove
 */
const cleanupEmptyGroups = (
	obj: { [ groupId: string ]: { [ key: string ]: unknown } | undefined },
	groupId: string
) => {
	const group = obj[ groupId ];
	if ( group && Object.keys( group ).length === 0 ) {
		delete obj[ groupId ];
	}
};

const reducer = (
	state: SettingsState = DEFAULT_STATE,
	action: Actions
): SettingsState => {
	switch ( action.type ) {
		case TYPES.RECEIVE_GROUPS:
			return {
				...state,
				groups: action.groups,
			};

		case TYPES.RECEIVE_SETTINGS: {
			const settings = { ...state.settings };
			const edits = { ...state.edits };
			const errors = { ...state.errors };
			const groupSettings = ensureGroupExists< {
				[ settingId: string ]: Setting;
			} >( settings, action.groupId );

			action.settings.forEach( ( setting ) => {
				// Store the setting in its group
				groupSettings[ setting.id ] = setting;
				// Clean up any pending edits or errors for this setting
				[ edits, errors ].forEach( ( obj ) => {
					if ( obj[ action.groupId ] ) {
						const groupObj = obj[ action.groupId ];
						if ( groupObj && setting.id in groupObj ) {
							delete groupObj[ setting.id ];
						}
					}
				} );
			} );

			// Remove empty groups from edits and errors
			[ edits, errors ].forEach( ( obj ) => {
				cleanupEmptyGroups( obj, action.groupId );
			} );

			return {
				...state,
				settings,
				edits,
				errors,
			};
		}

		case TYPES.EDIT_SETTING: {
			const edits = { ...state.edits };
			const groupSettings = state.settings[ action.groupId ] || {};
			const currentValue = groupSettings[ action.settingId ]?.value;

			// Only store edit if the new value is different from the current value
			if ( action.value !== currentValue ) {
				// Create or update the edit
				const groupEdits = ensureGroupExists< {
					[ settingId: string ]: SettingValue;
				} >( edits, action.groupId );
				groupEdits[ action.settingId ] = action.value;
			} else if (
				edits[ action.groupId ]?.[ action.settingId ] !== undefined
			) {
				// Remove the edit if the value matches the original
				const groupEdits = edits[ action.groupId ];
				if ( groupEdits ) {
					delete groupEdits[ action.settingId ];
					cleanupEmptyGroups( edits, action.groupId );
				}
			}

			return {
				...state,
				edits,
			};
		}

		case TYPES.EDIT_SETTINGS: {
			const edits = { ...state.edits };
			const groupSettings = state.settings[ action.groupId ] || {};
			const groupEdits = ensureGroupExists< {
				[ settingId: string ]: SettingValue;
			} >( edits, action.groupId );

			// Process each setting update in the batch
			action.updates.forEach( ( update ) => {
				const currentValue = groupSettings[ update.id ]?.value;
				if ( update.value !== currentValue ) {
					// Store edit if value changed
					groupEdits[ update.id ] = update.value;
				} else {
					// Remove edit if value matches original
					delete groupEdits[ update.id ];
				}
			} );

			cleanupEmptyGroups( edits, action.groupId );

			return {
				...state,
				edits,
			};
		}

		case TYPES.SET_SAVING: {
			const isSaving = { ...state.isSaving };

			if ( action.settingId === null ) {
				// Set saving state for an entire group
				isSaving.groups[ action.groupId ] = action.isSaving;
			} else {
				// Set saving state for a specific setting
				const groupSavingState = ensureGroupExists< {
					[ settingId: string ]: boolean;
				} >( isSaving.settings, action.groupId );
				groupSavingState[ action.settingId ] = action.isSaving;
			}

			return {
				...state,
				isSaving,
			};
		}

		case TYPES.SET_ERROR: {
			const errors = { ...state.errors };
			const groupErrors = ensureGroupExists< {
				[ settingId: string ]: unknown;
			} >( errors, action.groupId );

			if ( action.settingId === null ) {
				// Handle group-level errors
				if ( action.error === null ) {
					delete errors[ action.groupId ];
				} else {
					groupErrors.all = action.error;
				}
			} else if ( action.error === null ) {
				// Handle setting-level errors
				delete groupErrors[ action.settingId ];
				cleanupEmptyGroups( errors, action.groupId );
			} else {
				groupErrors[ action.settingId ] = action.error;
			}

			return {
				...state,
				errors,
			};
		}

		case TYPES.REVERT_EDITED_SETTING: {
			const edits = { ...state.edits };
			const errors = { ...state.errors };

			// Remove the edit for the specified setting
			if ( edits[ action.groupId ] ) {
				const groupEdits = edits[ action.groupId ];
				if ( groupEdits ) {
					delete groupEdits[ action.settingId ];
					cleanupEmptyGroups( edits, action.groupId );
				}
			}

			// Clear any errors for the setting
			if ( errors[ action.groupId ] ) {
				const groupErrors = errors[ action.groupId ];
				if ( groupErrors ) {
					delete groupErrors[ action.settingId ];
					cleanupEmptyGroups( errors, action.groupId );
				}
			}

			return {
				...state,
				edits,
				errors,
			};
		}

		case TYPES.REVERT_EDITED_SETTINGS_GROUP: {
			const edits = { ...state.edits };
			const errors = { ...state.errors };

			// Remove all edits and errors for the entire group
			delete edits[ action.groupId ];
			delete errors[ action.groupId ];

			return {
				...state,
				edits,
				errors,
			};
		}

		default:
			return state;
	}
};

export default reducer;
