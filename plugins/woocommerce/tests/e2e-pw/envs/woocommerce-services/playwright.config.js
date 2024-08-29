let config = require( '../../playwright.config.js' );

process.env.USE_WP_ENV = 'true';

config = {
	...config,
	projects: [
		{
			name: 'WooCommerce Shipping & Tax',
			grep: /@services/,
		},
	],
};

module.exports = config;
