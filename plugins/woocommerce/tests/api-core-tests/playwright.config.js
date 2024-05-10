const { devices } = require( '@playwright/test' );
require( 'dotenv' ).config( { path: __dirname + '/.env' } );

const { API_BASE_URL, CI, DEFAULT_TIMEOUT_OVERRIDE, USER_KEY, USER_SECRET } =
	process.env;

const baseURL = API_BASE_URL ?? 'http://localhost:8086';
const userKey = USER_KEY ?? 'admin';
const userSecret = USER_SECRET ?? 'password';
const base64auth = btoa( `${ userKey }:${ userSecret }` );

const config = {
	userKey,
	userSecret,
	timeout: DEFAULT_TIMEOUT_OVERRIDE
		? Number( DEFAULT_TIMEOUT_OVERRIDE )
		: 90 * 1000,
	expect: { timeout: 20 * 1000 },
	globalSetup: require.resolve( './global-setup' ),
	outputDir: './test-results/report',
	testDir: 'tests',
	retries: CI ? 4 : 2,
	workers: 4,
	reporter: [
		[ 'list' ],
		[
			'html',
			{
				outputFolder:
					process.env.PLAYWRIGHT_HTML_REPORT ??
					'./test-results/playwright-report',
				open: CI ? 'never' : 'always',
			},
		],
		[
			'allure-playwright',
			{
				outputFolder:
					process.env.ALLURE_RESULTS_DIR ??
					'./tests/api-core-tests/test-results/allure-results',
			},
		],
		[
			'json',
			{
				outputFile:
					process.env.PLAYWRIGHT_JSON_OUTPUT_NAME ??
					'./test-results/test-results.json',
			},
		],
		[ 'buildkite-test-collector/playwright/reporter' ],
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
