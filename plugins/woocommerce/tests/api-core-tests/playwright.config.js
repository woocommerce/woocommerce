const { devices } = require( '@playwright/test' );
require( 'dotenv' ).config();

let baseURL = 'http://localhost:8086';
let userKey = 'admin';
let userSecret = 'password';

if ( process.env.BASE_URL ) {
	baseURL = process.env.BASE_URL;
}

if ( process.env.USER_KEY ) {
	userKey = process.env.USER_KEY;
}

if ( process.env.USER_SECRET ) {
	userSecret = process.env.USER_SECRET;
}

const base64auth = btoa( `${ userKey }:${ userSecret }` );

const config = {
	timeout: 90 * 1000,
	expect: { timeout: 20 * 1000 },
	outputDir: './report',
	testDir: 'tests',
	retries: process.env.CI ? 4 : 2,
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
		[ 'json', { outputFile: 'api-test-report/test-results.json' } ],
	],
	use: {
		screenshot: 'only-on-failure',
		video: 'on-first-retry',
		trace: 'retain-on-failure',
		viewport: { width: 1280, height: 720 },
		baseURL,
		extraHTTPHeaders: {
			// Add authorization token to all requests.
			Authorization: `Basic ${ base64auth }`,
		},
	},
	projects: [
		{
			name: 'Chrome',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
};

module.exports = config;
