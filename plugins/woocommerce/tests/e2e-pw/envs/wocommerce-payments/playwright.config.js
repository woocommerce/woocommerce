let config = require( '../../playwright.config.js' );

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
