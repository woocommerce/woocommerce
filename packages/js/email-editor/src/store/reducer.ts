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
		case 'SET_EMAIL_POST':
			return {
				...state,
				...action.state,
			};
		case 'CHANGE_PERSONALIZATION_TAGS_STATE':
			return {
				...state,
				personalizationTags: {
					...state.personalizationTags,
					...action.state,
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
		case 'SET_CONTENT_VALIDATION':
			return {
				...state,
				contentValidation: action.validation,
			};
		default:
			return state;
	}
}
