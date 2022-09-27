const { devices } = require( '@playwright/test' );
require( 'dotenv' ).config();

const base64auth = btoa(
	`${ process.env.USER_KEY }:${ process.env.USER_SECRET }`
);

const config = {
	timeout: 90 * 1000,
	expect: { timeout: 20 * 1000 },
	outputDir: './report',
	testDir: 'tests',
	retries: process.env.CI ? 4 : 1,
	workers: 4,
	reporter: [
		[ 'list' ],
		[
			'html',
			{
				outputFolder: 'output',
				open: process.env.CI ? 'never' : 'always',
			},
		],
		[
			'allure-playwright',
			{ outputFolder: 'api-test-report/allure-results' },
		],
		[ 'json', { outputFile: 'e2e/test-results.json' } ],
	],
	use: {
		screenshot: 'only-on-failure',
		video: 'on-first-retry',
		trace: 'retain-on-failure',
		viewport: { width: 1280, height: 720 },
		baseURL: 'http://localhost:8086',
		extraHTTPHeaders: {
			// Add authorization token to all requests.
			Authorization: `Basic ${ base64auth }`,
		},
		stateDir: 'e2e/storage/',
	},
	projects: [
		{
			name: 'Chrome',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
};

module.exports = config;
