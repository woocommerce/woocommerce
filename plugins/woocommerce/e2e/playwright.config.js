const { devices } = require( '@playwright/test' );

const config = {
	timeout: 60 * 1000,
	outputDir: './report',
	globalSetup: require.resolve( './global-setup' ),
	globalTeardown: require.resolve( './global-teardown' ),
	testDir: 'tests',
	retries: 2,
	workers: 4,
	reporter: [
		[ 'list' ],
		[ 'html', { outputFolder: 'output' } ],
		[ 'allure-playwright', { outputFolder: 'e2e/allure-results' } ],
	],
	use: {
		screenshot: 'only-on-failure',
		video: 'on-first-retry',
		trace: 'retain-on-failure',
		viewport: { width: 1280, height: 720 },
		baseURL: 'http://localhost:8086',
	},
	projects: [
		{
			name: 'Chrome',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
};

module.exports = config;
