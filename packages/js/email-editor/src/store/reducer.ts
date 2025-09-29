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
		case 'SET_EDITOR_SETTINGS':
			return {
				...state,
				editorSettings: action.editorSettings,
			};
		case 'SET_EDITOR_THEME':
			return {
				...state,
				theme: action.theme,
			};
		case 'SET_EDITOR_URLS':
			return {
				...state,
				urls: { ...state.urls, ...action.urls },
			};
		case 'SET_EDITOR_CONFIG':
			return {
				...state,
				editorSettings: action.config.editorSettings,
				theme: action.config.theme,
				urls: action.config.urls,
				preview: {
					...state.preview,
					toEmail: action.config.userEmail,
				},
				styles: {
					...state.styles,
					globalStylesPostId:
						action.config.globalStylesPostId ?? null,
				},
			};
		default:
			return state;
	}
}
