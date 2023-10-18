const defaultConfig = require( './playwright.config' );
const { devices } = require( '@playwright/test' );

// Global setup will be done through the 'Setup' project, not through the `globalSetup` property
delete defaultConfig[ 'globalSetup' ];

/**
 * @type {import('@playwright/test').PlaywrightTestConfig}
 */
const config = {
	...defaultConfig,
	projects: [
		{
			name: 'Setup',
			testDir: './',
			testMatch: 'ci-release.global-setup.js',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
		{
			name: 'API tests',
			dependencies: [ 'Setup' ],
		},
	],
};

module.exports = config;
