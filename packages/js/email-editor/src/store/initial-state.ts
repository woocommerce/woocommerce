/**
 * Internal dependencies
 */
import { State } from './types';
import { getEditorSettings, getEditorTheme, getUrls } from './settings';

export function getInitialState(postId?: string, postType?: string): State {
	if ( ! window.WooCommerceEmailEditor ) {
		throw new Error(
			'WooCommerceEmailEditor global object is not available. This is required for the email editor to work.'
		);
	}

	const current_post_id = postId || window.WooCommerceEmailEditor.current_post_id;
	const current_post_type = postType || window.WooCommerceEmailEditor.current_post_type;

	if ( current_post_id === undefined || current_post_id === null ) {
		throw new Error( 'current_post_id is required but not provided.' );
	}

	if ( ! current_post_type ) {
		throw new Error( 'current_post_type is required but not provided.' );
	}

	return {
		postId: current_post_id,
		postType: current_post_type,
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
