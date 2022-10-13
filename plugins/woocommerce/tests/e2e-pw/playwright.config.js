const { devices } = require( '@playwright/test' );
const {
	CI,
	E2E_MAX_FAILURES,
	BASE_URL,
	DEFAULT_TIMEOUT_OVERRIDE,
} = process.env;

const config = {
	timeout: DEFAULT_TIMEOUT_OVERRIDE
		? Number( DEFAULT_TIMEOUT_OVERRIDE )
		: 90 * 1000,
	expect: { timeout: 20 * 1000 },
	outputDir: './report',
	globalSetup: require.resolve( './global-setup' ),
	globalTeardown: require.resolve( './global-teardown' ),
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
		[ 'allure-playwright', { outputFolder: 'e2e/allure-results' } ],
		[ 'json', { outputFile: 'e2e/test-results.json' } ],
	],
	maxFailures: E2E_MAX_FAILURES ? Number( E2E_MAX_FAILURES ) : 0,
	use: {
		screenshot: 'only-on-failure',
		video: 'on-first-retry',
		trace: 'retain-on-failure',
		viewport: { width: 1280, height: 720 },
		baseURL: BASE_URL ?? 'http://localhost:8086',
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
