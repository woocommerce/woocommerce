/**
 * Internal dependencies
 */
import { editorCurrentPostId } from './constants';
import { State } from './types';
import { getEditorSettings, getEditorTheme, getUrls } from './settings';

export function getInitialState(): State {
	const postId = editorCurrentPostId;
	return {
		postId,
		editorSettings: getEditorSettings(),
		theme: getEditorTheme(),
		styles: {
			globalStylesPostId:
				window.WooCommerceEmailEditor.user_theme_post_id,
		},
		urls: getUrls(),
		preview: {
			toEmail: window.WooCommerceEmailEditor.current_wp_user_email,
			isModalOpened: false,
			isSendingPreviewEmail: false,
			sendingPreviewStatus: null,
		},
		personalizationTags: {
			list: [],
			isFetching: false,
		},
	};
}
