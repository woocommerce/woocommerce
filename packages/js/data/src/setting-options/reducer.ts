/**
 * Internal dependencies
 */
import { TYPES } from './action-types';
import type { Actions } from './actions';
import type { SettingsState } from './types';

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
			settings[ action.groupId ] = settings[ action.groupId ] || {};

			// Remove edits for successfully updated settings
			action.settings.forEach( ( setting ) => {
				settings[ action.groupId ][ setting.id ] = setting;
				if ( edits[ action.groupId ] ) {
					delete edits[ action.groupId ][ setting.id ];
				}
			} );

			// Remove edits for the group if it's empty
			if (
				edits[ action.groupId ] &&
				Object.keys( edits[ action.groupId ] ).length === 0
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
			edits[ action.groupId ] = edits[ action.groupId ] || {};
			edits[ action.groupId ][ action.settingId ] = action.value;

			return {
				...state,
				edits,
			};
		}

		case TYPES.UPDATE_SETTINGS: {
			const edits = { ...state.edits };
			edits[ action.groupId ] = edits[ action.groupId ] || {};

			action.updates.forEach( ( update ) => {
				edits[ action.groupId ][ update.id ] = update.value;
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
				isSaving.settings[ action.groupId ] =
					isSaving.settings[ action.groupId ] || {};
				isSaving.settings[ action.groupId ][ action.settingId ] =
					action.isSaving;
			}

			return {
				...state,
				isSaving,
			};
		}

		case TYPES.SET_ERROR: {
			const errors = { ...state.errors };
			errors[ action.groupId ] = errors[ action.groupId ] || {};

			if ( action.settingId === null ) {
				Object.keys( errors[ action.groupId ] ).forEach(
					( settingId ) => {
						errors[ action.groupId ][ settingId ] = action.error;
					}
				);
			} else {
				errors[ action.groupId ][ action.settingId ] = action.error;
			}

			return {
				...state,
				errors,
			};
		}

		case TYPES.REVERT_SETTING: {
			const edits = { ...state.edits };
			edits[ action.groupId ] = edits[ action.groupId ] || {};
			delete edits[ action.groupId ][ action.settingId ];

			if ( Object.keys( edits[ action.groupId ] ).length === 0 ) {
				delete edits[ action.groupId ];
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
