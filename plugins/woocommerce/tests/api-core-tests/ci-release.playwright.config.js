const defaultConfig = require( './playwright.config' );
const defaultUse = defaultConfig.use;

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
			use: {
				...defaultUse,
				trace: 'on', // todo remove
			},
		},
		{
			name: 'API tests',
			dependencies: [ 'Setup' ],
		},
	],
};

module.exports = config;
