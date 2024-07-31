let config = require( '../gutenberg-stable/playwright.config.js' );

process.env.USE_WP_ENV = 'true';

config = {
	...config,
	projects: [
		{
			name: '[env: Gutenberg Nightly]',
		},
	],
};

module.exports = config;
