const { devices } = require( '@playwright/test' );
const { CI, E2E_MAX_FAILURES, ALLURE_RESULTS_DIR } = process.env;

const config = {
	timeout: 90 * 1000,
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
		[
			'allure-playwright',
			{
				outputFolder:
					ALLURE_RESULTS_DIR ?? 'tests/e2e-pw/allure-results',
			},
		],
		[ 'json', { outputFile: 'tests/e2e-pw/test-results.json' } ],
	],
	maxFailures: E2E_MAX_FAILURES ? Number( E2E_MAX_FAILURES ) : 0,
	use: {
		screenshot: 'only-on-failure',
		video: 'on-first-retry',
		trace: 'retain-on-failure',
		viewport: { width: 1280, height: 720 },
		baseURL: 'http://localhost:8086',
		stateDir: 'tests/e2e-pw/storage/',
	},
	projects: [
		{
			name: 'Chrome',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
};

module.exports = config;
