/**
 * External dependencies
 */
import { defineConfig, devices } from '@playwright/test';

require( 'dotenv' ).config( { path: __dirname + '/.env' } );

if ( ! process.env.BASE_URL ) {
	process.env.BASE_URL =
		'http://localhost:' + ( process.env.WP_ENV_TESTS_PORT || '8086' );
	console.log(
		'BASE_URL is not set. Using default: ' + process.env.BASE_URL
	);
}

const { BASE_URL, CI, E2E_MAX_FAILURES, REPEAT_EACH } = process.env;

export const TESTS_ROOT_PATH = __dirname;
export const TESTS_RESULTS_PATH = `${ TESTS_ROOT_PATH }/test-results`;
export const STORAGE_DIR_PATH = `${ TESTS_ROOT_PATH }/.state/`;
export const ADMIN_STATE_PATH = `${ STORAGE_DIR_PATH }/admin.json`;
export const CUSTOMER_STATE_PATH = `${ STORAGE_DIR_PATH }/customer.json`;
export const CONSUMER_KEY = { name: '', key: '', secret: '' };

const reporter = [
	[ 'list' ],
	[
		'allure-playwright',
		{
			resultsDir: `${ TESTS_ROOT_PATH }/test-results/allure-results`,
			detail: true,
			suiteTitle: true,
		},
	],
	[
		'json',
		{
			outputFile: `${ TESTS_ROOT_PATH }/test-results/test-results-${ Date.now() }.json`,
		},
	],
	[
		`${ TESTS_ROOT_PATH }/reporters/environment-reporter.js`,
		{ outputFolder: `${ TESTS_ROOT_PATH }/test-results/allure-results` },
	],
	[
		`${ TESTS_ROOT_PATH }/reporters/flaky-tests-reporter.js`,
		{ outputFolder: `${ TESTS_ROOT_PATH }/test-results/flaky-tests` },
	],
];

if ( process.env.CI ) {
	reporter.push( [ 'buildkite-test-collector/playwright/reporter' ] );
	reporter.push( [ `${ TESTS_ROOT_PATH }/reporters/skipped-tests.js` ] );
} else {
	reporter.push( [
		'html',
		{
			outputFolder: `${ TESTS_ROOT_PATH }/playwright-report`,
			open: 'on-failure',
		},
	] );
}

export const setupProjects = [
	{
		name: 'install wc',
		testDir: `${ TESTS_ROOT_PATH }/fixtures`,
		testMatch: 'install-wc.setup.js',
	},
	{
		name: 'global authentication',
		testDir: `${ TESTS_ROOT_PATH }/fixtures`,
		testMatch: 'auth.setup.js',
		dependencies: [ 'install wc' ],
	},
	{
		name: 'site setup',
		testDir: `${ TESTS_ROOT_PATH }/fixtures`,
		testMatch: `site.setup.js`,
		dependencies: [ 'global authentication' ],
	},
];

export default defineConfig( {
	timeout: 120 * 1000,
	expect: { timeout: CI ? 20 * 1000 : 10 * 1000 },
	outputDir: TESTS_RESULTS_PATH,
	testDir: `${ TESTS_ROOT_PATH }/tests`,
	retries: CI ? 1 : 0,
	repeatEach: REPEAT_EACH ? Number( REPEAT_EACH ) : 1,
	workers: 1,
	reportSlowTests: { max: 5, threshold: 30 * 1000 }, // 30 seconds threshold
	reporter,
	maxFailures: E2E_MAX_FAILURES ? Number( E2E_MAX_FAILURES ) : 0,
	forbidOnly: !! CI,
	use: {
		baseURL: `${ BASE_URL }/`.replace( /\/+$/, '/' ),
		screenshot: { mode: 'only-on-failure', fullPage: true },
		trace:
			/^https?:\/\/localhost/.test( BASE_URL ) || ! CI
				? 'retain-on-first-failure'
				: 'off',
		video: 'retain-on-failure',
		actionTimeout: CI ? 20 * 1000 : 10 * 1000,
		navigationTimeout: CI ? 20 * 1000 : 10 * 1000,
		contextOptions: {
			reducedMotion: 'reduce',
		},
		channel: 'chrome',
		...devices[ 'Desktop Chrome' ],
	},
	snapshotPathTemplate: '{testDir}/{testFilePath}-snapshots/{arg}',

	projects: [
		...setupProjects,
		{
			name: 'e2e',
			testIgnore: '**/api-tests/**',
			dependencies: [ 'site setup' ],
		},
		{
			name: 'api',
			testMatch: '**/api-tests/**',
			dependencies: [ 'site setup' ],
		},
	],
} );
