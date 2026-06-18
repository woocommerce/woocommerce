/**
 * Internal dependencies
 */
import ACTION_TYPES from './action-types';

type SettingsData = Record< string, unknown > & {
	enabled_payment_method_ids?: string[];
};

type SettingsState = {
	isDirty: boolean;
	isSaving: boolean;
	savingError: unknown;
	data: SettingsData;
};

const defaultState: SettingsState = {
	isDirty: false,
	isSaving: false,
	savingError: null,
	data: {},
};

export const receiveSettings = (
	state = defaultState,
	action: {
		type?: string;
		data?: SettingsData;
		payload?: SettingsData;
		isSaving?: boolean;
		error?: unknown;
		id?: string;
	}
): SettingsState => {
	switch ( action.type ) {
		case ACTION_TYPES.SET_SETTINGS:
			return {
				...state,
				data: action.data ?? {},
				isDirty: false,
			};

		case ACTION_TYPES.SET_SETTINGS_VALUES:
			return {
				...state,
				savingError: null,
				isDirty: true,
				data: {
					...state.data,
					...( action.payload ?? {} ),
				},
			};

		case ACTION_TYPES.SET_IS_SAVING_SETTINGS:
			return {
				...state,
				isDirty:
					action.isSaving || action.error ? state.isDirty : false,
				isSaving: Boolean( action.isSaving ),
				savingError: action.error ?? null,
			};

		case ACTION_TYPES.SET_SELECTED_PAYMENT_METHOD:
			return {
				...state,
				isDirty: true,
				data: {
					...state.data,
					enabled_payment_method_ids: [
						...( state.data.enabled_payment_method_ids ?? [] ),
						action.id,
					].filter( Boolean ) as string[],
				},
			};

		case ACTION_TYPES.SET_UNSELECTED_PAYMENT_METHOD:
			return {
				...state,
				isDirty: true,
				data: {
					...state.data,
					enabled_payment_method_ids: (
						state.data.enabled_payment_method_ids ?? []
					).filter( ( id ) => id !== action.id ),
				},
			};
	}

	return state;
};

export default receiveSettings;
