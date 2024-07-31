let config = require( '../../playwright.config.js' );
const { devices } = require( '@playwright/test' );

config = {
	...config,
	projects: [
		{
			name: '[env: WP.com]',
			use: { ...devices[ 'Desktop Chrome' ] },
			testMatch: '**basic.spec.js',
		},
	],
};

module.exports = config;
