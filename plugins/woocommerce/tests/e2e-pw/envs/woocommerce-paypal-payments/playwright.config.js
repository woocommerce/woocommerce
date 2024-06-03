let config = require( '../../playwright.config.js' );

config = {
	...config,
	projects: [
		{
			name: 'WooCommerce PayPal Payments',
			testMatch: /.*basic\/*/,
		},
	],
};

module.exports = config;
