/**
 * Internal dependencies
 */
import { EmailEditorSettings, EmailTheme, EmailEditorUrls } from './types';

export function getEditorSettings(): EmailEditorSettings {
	return window.WooCommerceEmailEditor.editor_settings as EmailEditorSettings;
}

export function getEditorTheme(): EmailTheme {
	return window.WooCommerceEmailEditor.editor_theme as EmailTheme;
}

export function getUrls(): EmailEditorUrls {
	return window.WooCommerceEmailEditor.urls as EmailEditorUrls;
}
