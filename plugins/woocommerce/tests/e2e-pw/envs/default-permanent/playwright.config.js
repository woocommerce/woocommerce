let config = require( '../../playwright.config.js' );
const { devices } = require( '@playwright/test' );

config = {
	...config,
	retries: 0,
	projects: [
		{
			name: 'default',
			use: { ...devices[ 'Desktop Chrome' ] },
			testMatch: '**order*.spec.js',
		},
	],
};

module.exports = config;
