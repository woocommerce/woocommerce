let config = require( '../../playwright.config.js' );

process.env.USE_WP_ENV = 'true';

config = {
	...config,
	projects: [
		{
			name: 'WooPayments',
			testMatch: /.*basic\/*/,
		},
	],
};

module.exports = config;
