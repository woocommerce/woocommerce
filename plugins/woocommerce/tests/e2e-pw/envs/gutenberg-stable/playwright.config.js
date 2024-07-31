let config = require( '../../playwright.config.js' );

process.env.USE_WP_ENV = 'true';

config = {
	...config,
	projects: [
		{
			name: '[env: Gutenberg]',
			grep: /@gutenberg/,
		},
	],
};

module.exports = config;
