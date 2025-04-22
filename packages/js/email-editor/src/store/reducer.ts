/**
 * Internal dependencies
 */
import { State } from './types';

export function reducer( state: State, action ): State {
	switch ( action.type ) {
		case 'CHANGE_PREVIEW_STATE':
			return {
				...state,
				preview: { ...state.preview, ...action.state },
			};
		case 'CHANGE_PERSONALIZATION_TAGS_STATE':
			return {
				...state,
				personalizationTags: {
					...state.personalizationTags,
					...action.state,
				},
			};
		case 'TOGGLE_SETTINGS_SIDEBAR_ACTIVE_TAB':
			return {
				...state,
				settingsSidebar: {
					...state.settingsSidebar,
					activeTab: action.state.activeTab,
				},
			};
		case 'SET_PERSONALIZATION_TAGS':
			return {
				...state,
				personalizationTags: {
					...state.personalizationTags,
					list: action.personalizationTags,
				},
			};
		case 'SET_IS_FETCHING_PERSONALIZATION_TAGS':
			return {
				...state,
				personalizationTags: {
					...state.personalizationTags,
					...action.state,
				},
			};
		case 'SET_PERSONALIZATION_TAGS_LIST':
			return {
				...state,
				personalizationTags: {
					...state.personalizationTags,
					...action.state,
				},
			};
		default:
			return state;
	}
}
