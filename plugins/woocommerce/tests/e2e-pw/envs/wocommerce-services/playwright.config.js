let config = require( '../../playwright.config.js' );

config = {
	...config,
	projects: [
		{
			name: 'WooCommerce Shipping & Tax',
			testMatch: /.*basic\/*/,
		},
	],
};

module.exports = config;
