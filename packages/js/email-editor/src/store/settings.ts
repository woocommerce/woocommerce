/**
 * Internal dependencies
 */
import { EmailEditorSettings, EmailTheme, EmailEditorUrls } from './types';

function getEditorSettings(): EmailEditorSettings {
	return window.WooCommerceEmailEditor.editor_settings as EmailEditorSettings;
}

function getEditorTheme(): EmailTheme {
	return window.WooCommerceEmailEditor.editor_theme as EmailTheme;
}

function getUrls(): EmailEditorUrls {
	return window.WooCommerceEmailEditor.urls as EmailEditorUrls;
}

/**
 * Extract editor configuration from the global window object for backward compatibility.
 * This function is used by the initializeEditor function to maintain backward compatibility.
 */
export function getEditorConfigFromWindow() {
	if ( ! window.WooCommerceEmailEditor ) {
		throw new Error(
			'WooCommerceEmailEditor global object is not available. This is required for the email editor to work.'
		);
	}

	return {
		editorSettings: getEditorSettings(),
		theme: getEditorTheme(),
		urls: getUrls(),
		userEmail: window.WooCommerceEmailEditor.current_wp_user_email,
		globalStylesPostId: window.WooCommerceEmailEditor.user_theme_post_id,
	};
}
