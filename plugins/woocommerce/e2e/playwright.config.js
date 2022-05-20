const { devices } = require( '@playwright/test' );

const config = {
	timeout: 20000,
	outputDir: './report',
	globalSetup: require.resolve( './global-setup' ),
	globalTeardown: require.resolve( './global-teardown' ),
	testDir: 'tests',
	retries: 1,
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
		// {
		//  name: 'Firefox',
		//  use: { ...devices['Desktop Firefox'] },
		// },
		// {
		//  name: 'Webkit',
		//  use: { ...devices['Desktop Webkit'] },
		// },
	],
};

module.exports = config;
