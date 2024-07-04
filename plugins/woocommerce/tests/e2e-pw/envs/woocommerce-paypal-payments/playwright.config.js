let config = require( '../../playwright.config.js' );

process.env.USE_WP_ENV = 'true';

config = {
	...config,
	projects: [
		{
			name: 'WooCommerce PayPal Payments',
			grep: /@payments/,
		},
	],
};

module.exports = config;
