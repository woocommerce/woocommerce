/**
 * Internal dependencies
 */
import { mainSidebarDocumentTab, editorCurrentPostId } from './constants';
import { State } from './types';
import { getEditorSettings, getEditorTheme, getUrls } from './settings';

export function getInitialState(): State {
	const postId = editorCurrentPostId;
	return {
		inserterSidebar: {
			isOpened: false,
		},
		listviewSidebar: {
			isOpened: false,
		},
		settingsSidebar: {
			activeTab: mainSidebarDocumentTab,
		},
		postId,
		editorSettings: getEditorSettings(),
		theme: getEditorTheme(),
		styles: {
			globalStylesPostId:
				window.WooCommerceEmailEditor.user_theme_post_id,
		},
		autosaveInterval: 60,
		urls: getUrls(),
		preview: {
			deviceType: 'Desktop',
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
