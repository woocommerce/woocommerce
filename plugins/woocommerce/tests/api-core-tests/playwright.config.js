const { devices } = require( '@playwright/test' );
require( 'dotenv' ).config({ path: __dirname + '/.env' });

const {
	BASE_URL,
	CI,
	DEFAULT_TIMEOUT_OVERRIDE,
	USER_KEY,
	USER_SECRET,
} = process.env;

const baseURL = BASE_URL ?? 'http://localhost:8086';
const userKey = USER_KEY ?? 'admin';
const userSecret = USER_SECRET ?? 'password';
const base64auth = btoa( `${ userKey }:${ userSecret }` );

const config = {
	timeout: DEFAULT_TIMEOUT_OVERRIDE
		? Number( DEFAULT_TIMEOUT_OVERRIDE )
		: 90 * 1000,
	expect: { timeout: 20 * 1000 },
	outputDir: './report',
	testDir: 'tests',
	retries: CI ? 4 : 2,
	workers: 4,
	reporter: [
		[ 'list' ],
		[
			'html',
			{
				outputFolder: 'output',
				open: CI ? 'never' : 'always',
			},
		],
		[
			'allure-playwright',
			{
				outputFolder:
					process.env.ALLURE_RESULTS_DIR ??
					'tests/api-core-tests/api-test-report/allure-results',
			},
		],
		[
			'json',
			{
				outputFile:
					'tests/api-core-tests/api-test-report/test-results.json',
			},
		],
	],
	use: {
		screenshot: 'only-on-failure',
		video: 'on-first-retry',
		trace: 'retain-on-failure',
		viewport: { width: 1280, height: 720 },
		baseURL: baseURL,
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
