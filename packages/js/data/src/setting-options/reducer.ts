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
			const errors = { ...state.errors };
			const groupSettings = ensureGroupExists< {
				[ settingId: string ]: Setting;
			} >( settings, action.groupId );

			action.settings.forEach( ( setting ) => {
				// Update settings
				groupSettings[ setting.id ] = setting;
				// Clean up edits and errors
				[ edits, errors ].forEach( ( obj ) => {
					if ( obj[ action.groupId ] ) {
						const groupObj = obj[ action.groupId ];
						if ( groupObj && setting.id in groupObj ) {
							delete groupObj[ setting.id ];
						}
					}
				} );
			} );

			// Reset empty groups
			[ edits, errors ].forEach( ( obj ) => {
				const group = obj[ action.groupId ];
				if ( group && Object.keys( group || {} ).length === 0 ) {
					delete obj[ action.groupId ];
				}
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
			const groupEdits = ensureGroupExists< {
				[ settingId: string ]: SettingValue;
			} >( edits, action.groupId );
			groupEdits[ action.settingId ] = action.value;

			return {
				...state,
				edits,
			};
		}

		case TYPES.EDIT_SETTINGS: {
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
				groupErrors.all = action.error;
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

		case TYPES.REVERT_EDITED_SETTINGS_GROUP: {
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
