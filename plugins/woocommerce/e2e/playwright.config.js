const { devices } = require( '@playwright/test' );

const config = {
	timeout: 30000,
	outputDir: './report',
	globalSetup: require.resolve( './global-setup' ),
	globalTeardown: require.resolve( './global-teardown' ),
	testDir: 'tests',
	retries: 1,
	workers: 4,
	reporter: [ [ 'list' ], [ 'html', { outputFolder: 'e2e/output' } ] ],
	use: {
		screenshot: 'only-on-failure',
		video: 'on-first-retry',
		trace: 'retain-on-failure',
		viewport: { width: 1280, height: 720 },
		baseURL: 'http://localhost:8084',
	},
	projects: [
		{
			name: 'Chrome',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
};

module.exports = config;
