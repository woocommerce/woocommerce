interface Window {
	_wpCollaborationEnabled?: boolean;
	WooCommerceEmailEditor: {
		current_wp_user_email: string;
		user_theme_post_id: number;
		urls: {
			listings: string;
			send: string;
		};
		editor_settings: unknown; // Can't import type in global.d.ts. Typed in getEditorSettings() in store/settings.ts
		editor_theme: unknown; // Can't import type in global.d.ts. Typed in getEditorTheme() in store/settings.ts
		current_post_type: string;
		current_post_id: string;
	};
}

// Text domain used by `__()`, `_x()`, `_n()`, `_nx()` calls inside this package.
// Consumers must replace this identifier with their own text domain string at
// bundle time via `webpack.DefinePlugin` (or an equivalent). See
// `development.md` for a build configuration example.
declare const __i18n_text_domain__: string;
