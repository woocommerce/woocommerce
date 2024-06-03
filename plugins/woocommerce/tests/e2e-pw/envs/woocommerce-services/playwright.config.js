let config = require( '../../playwright.config.js' );

process.env.USE_WP_ENV = 'true';

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
