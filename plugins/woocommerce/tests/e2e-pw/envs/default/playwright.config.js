const config = require( '../../playwright.config.js' );

process.env.USE_WP_ENV = 'true';

config = {
	...config,
	projects: [
		{
			name: '[env: Default]',
		},
	],
};

module.exports = config;
