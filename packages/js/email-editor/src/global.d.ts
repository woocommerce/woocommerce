interface Window {
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
