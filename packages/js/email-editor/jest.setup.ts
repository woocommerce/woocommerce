// Consumers of this package replace `__i18n_text_domain__` at build time via
// `webpack.DefinePlugin`. Provide a default for the test runner so the
// identifier is resolved when components under test call `__()` / `_x()` etc.
globalThis.__i18n_text_domain__ = 'woocommerce';

window.WooCommerceEmailEditor = {
	current_post_type: 'email',
	current_post_id: '123',
	current_wp_user_email: 'test@example.com',
	user_theme_post_id: 1,
	urls: {
		listings: 'https://example.com/listings',
		send: 'https://example.com/send',
	},
	editor_settings: {},
	editor_theme: {},
};
