let config = require( '../../playwright.config.js' );
const { devices } = require( '@playwright/test' );

config = {
	...config,
	retries: 0,
	projects: [
		{
			name: 'default wpcom',
			use: { ...devices[ 'Desktop Chrome' ] },
			testMatch: '**basic.spec.js',
		},
	],
};

module.exports = config;
