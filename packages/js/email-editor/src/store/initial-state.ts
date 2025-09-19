/**
 * Internal dependencies
 */
import { State } from './types';

export function getInitialState(): State {
	return {
		editorSettings: undefined,
		theme: undefined,
		styles: {
			globalStylesPostId: null,
		},
		urls: {
			back: '',
			listings: '',
		},
		preview: {
			toEmail: '',
			isModalOpened: false,
			isSendingPreviewEmail: false,
			sendingPreviewStatus: null,
		},
		personalizationTags: {
			list: [],
			isFetching: false,
		},
		contentValidation: undefined,
	};
}
