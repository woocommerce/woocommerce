let config = require( '../../playwright.config.js' );
const { devices } = require( '@playwright/test' );

process.env.USE_WP_ENV = 'true';

config = {
	...config,
	workers: 2,
	projects: [
		{
			name: 'default',
			use: { ...devices[ 'Desktop Chrome' ] },
			grepInvert: /@gutenberg/,
		},
	],
};

module.exports = config;
