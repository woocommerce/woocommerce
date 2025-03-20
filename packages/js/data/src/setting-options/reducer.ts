/**
 * Internal dependencies
 */
import { TYPES } from './action-types';
import type { Actions } from './actions';
import type { SettingsState, Setting, SettingValue } from './types';

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

const ensureGroupExists = < T >(
	obj: { [ groupId: string ]: T | undefined },
	groupId: string
): T => {
	if ( ! obj[ groupId ] ) {
		obj[ groupId ] = {} as T;
	}
	return obj[ groupId ] as T;
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
			const groupSettings = ensureGroupExists< {
				[ settingId: string ]: Setting;
			} >( settings, action.groupId );

			// Remove edits for successfully updated settings
			action.settings.forEach( ( setting ) => {
				groupSettings[ setting.id ] = setting;

				if ( edits[ action.groupId ] ) {
					const groupEdits = edits[ action.groupId ];
					if ( groupEdits && setting.id in groupEdits ) {
						delete groupEdits[ setting.id ];
					}
				}
			} );

			// Remove edits for the group if it's empty
			if (
				edits[ action.groupId ] &&
				Object.keys( edits[ action.groupId ] || {} ).length === 0
			) {
				delete edits[ action.groupId ];
			}

			return {
				...state,
				settings,
				edits,
			};
		}

		case TYPES.UPDATE_SETTING: {
			const edits = { ...state.edits };
			const groupEdits = ensureGroupExists< {
				[ settingId: string ]: SettingValue;
			} >( edits, action.groupId );
			groupEdits[ action.settingId ] = action.value;

			return {
				...state,
				edits,
			};
		}

		case TYPES.UPDATE_SETTINGS: {
			const edits = { ...state.edits };
			const groupEdits = ensureGroupExists< {
				[ settingId: string ]: SettingValue;
			} >( edits, action.groupId );

			action.updates.forEach( ( update ) => {
				groupEdits[ update.id ] = update.value;
			} );

			return {
				...state,
				edits,
			};
		}

		case TYPES.SET_SAVING: {
			const isSaving = { ...state.isSaving };

			if ( action.settingId === null ) {
				isSaving.groups[ action.groupId ] = action.isSaving;
			} else {
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
				Object.keys( groupErrors ).forEach( ( settingId ) => {
					groupErrors[ settingId ] = action.error;
				} );
			} else {
				groupErrors[ action.settingId ] = action.error;
			}

			return {
				...state,
				errors,
			};
		}

		case TYPES.REVERT_SETTING: {
			const edits = { ...state.edits };
			if ( edits[ action.groupId ] ) {
				const groupEdits = edits[ action.groupId ];
				if ( groupEdits && action.settingId in groupEdits ) {
					delete groupEdits[ action.settingId ];
				}

				if ( Object.keys( groupEdits || {} ).length === 0 ) {
					delete edits[ action.groupId ];
				}
			}

			return {
				...state,
				edits,
			};
		}

		case TYPES.REVERT_GROUP: {
			const edits = { ...state.edits };
			delete edits[ action.groupId ];

			return {
				...state,
				edits,
			};
		}

		default:
			return state;
	}
};

export default reducer;
