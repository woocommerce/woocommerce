const defaultConfig = require( './playwright.config' );

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
		},
		{
			name: 'API tests',
			dependencies: [ 'Setup' ],
		},
	],
};

module.exports = config;
