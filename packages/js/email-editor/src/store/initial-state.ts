/**
 * Internal dependencies
 */
import { State } from './types';
import { getEditorSettings, getEditorTheme, getUrls } from './settings';

export function getInitialState(): State {
	return {
		postId: window.WooCommerceEmailEditor.current_post_id,
		postType: window.WooCommerceEmailEditor.current_post_type,
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
