/**
 * External dependencies
 */
import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';

/**
 * Internal dependencies
 */
import { adminFile as BLOCKS_ADMIN_STATE } from './utils/blocks/constants';

// __dirname is not natively available in ESM, but Playwright's config loader shims it.
dotenv.config( { path: __dirname + '/.env' } );

if ( ! process.env.BASE_URL ) {
	process.env.BASE_URL =
		'http://localhost:' + ( process.env.WP_ENV_TESTS_PORT || '8086' );
	console.log(
		'BASE_URL is not set. Using default: ' + process.env.BASE_URL
	);
}

// The blocks setup project uses @wordpress/e2e-test-utils-playwright, which derives
// the REST API root from WP_BASE_URL (its default is port 8889). Align it with the
// suite's base URL so REST setup targets the same WordPress instance.
if ( ! process.env.WP_BASE_URL ) {
	process.env.WP_BASE_URL = process.env.BASE_URL;
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
		'playwright-ctrf-json-reporter',
		{
			outputDir: `${ TESTS_ROOT_PATH }/test-results`,
			outputFile: `ctrf-report-${ Date.now() }.json`,
			branchName: process.env.GITHUB_REF_NAME || '',
			commit: process.env.GITHUB_SHA || '',
			appName: 'woocommerce-core',
			repositoryName: process.env.GITHUB_REPOSITORY || '',
		},
	],
	[
		`${ TESTS_ROOT_PATH }/reporters/environment-reporter.ts`,
		{ outputFolder: `${ TESTS_ROOT_PATH }/test-results/allure-results` },
	],
];

if ( process.env.CI ) {
	reporter.push( [ `${ TESTS_ROOT_PATH }/reporters/skipped-tests.ts` ] );
	reporter.push( [
		'junit',
		{
			outputFile: `${ TESTS_ROOT_PATH }/test-results/results.xml`,
			stripANSIControlSequences: true,
			includeProjectInTestName: true,
		},
	] );
} else {
	reporter.push( [
		'html',
		{
			outputFolder: `${ TESTS_ROOT_PATH }/playwright-report`,
			open: 'never',
		},
	] );
}

export const setupProjects = [
	{
		name: 'install wc',
		testDir: `${ TESTS_ROOT_PATH }/fixtures`,
		testMatch: 'install-wc.setup.ts',
	},
	{
		name: 'global authentication',
		testDir: `${ TESTS_ROOT_PATH }/fixtures`,
		testMatch: 'auth.setup.ts',
		dependencies: [ 'install wc' ],
	},
	{
		name: 'site setup',
		testDir: `${ TESTS_ROOT_PATH }/fixtures`,
		testMatch: `site.setup.ts`,
		dependencies: [ 'global authentication' ],
	},
	{
		name: 'blocks setup',
		testDir: `${ TESTS_ROOT_PATH }/fixtures`,
		testMatch: 'blocks-setup.ts',
	},
];

/**
 * Spec folders that must run serially in `core-serial` (they mutate global
 * state or share fixtures). Every other folder under `tests/` runs in
 * `core-parallel` by default, except the other-project folders in `nonCoreSpecs`.
 */
const serialRunSpecs = [
	'**/tests/analytics/**/*.spec.ts',
	'**/tests/brands/**/*.spec.ts',
	'**/tests/cart/**/*.spec.ts',
	'**/tests/checkout/**/*.spec.ts',
	'**/tests/coupons/**/*.spec.ts',
	'**/tests/customer/**/*.spec.ts',
	'**/tests/editor/**/*.spec.ts',
	'**/tests/email/**/*.spec.ts',
	'**/tests/email-editor/**/*.spec.ts',
	'**/tests/onboarding/**/*.spec.ts',
	'**/tests/order/**/*.spec.ts',
	'**/tests/product/product-import-csv.spec.ts',
	'**/tests/settings/**/*.spec.ts',
	'**/tests/shipping/**/*.spec.ts',
	'**/tests/shop/shop-title-after-deletion.spec.ts',
];

/**
 * Spec folders owned by other Playwright projects — excluded from both core projects.
 * PayPal tests don't run well in parallel (https://github.com/woocommerce/woocommerce/pull/63068);
 * blocks specs need the `blocks setup` project and its storage state.
 */
const nonCoreSpecs = [
	'**/api-tests/**',
	'**/tests/paypal/**',
	'**/tests/blocks/**',
];

export default defineConfig( {
	timeout: 120 * 1000,
	expect: { timeout: CI ? 20 * 1000 : 10 * 1000 },
	outputDir: TESTS_RESULTS_PATH,
	testDir: `${ TESTS_ROOT_PATH }/tests`,
	retries: CI ? 1 : 0,
	repeatEach: REPEAT_EACH ? Number( REPEAT_EACH ) : 1,
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
			name: 'core-serial',
			testMatch: serialRunSpecs,
			dependencies: [ 'site setup' ],
			workers: 1,
		},
		{
			name: 'core-parallel',
			testIgnore: [ ...serialRunSpecs, ...nonCoreSpecs ],
			dependencies: [ 'site setup' ],
		},
		{
			name: 'api',
			testMatch: '**/api-tests/**',
			dependencies: [ 'site setup' ],
			workers: 4,
		},
		{
			name: 'legacy-mini-cart',
			testMatch: [ '**/tests/cart/**', '**/tests/checkout/**' ],
			testIgnore: [ '**/tests/blocks/**' ],
			dependencies: [ 'site setup' ],
			workers: 1,
		},
		{
			name: 'paypal-standard',
			testMatch: [ '**/tests/paypal/**' ],
			dependencies: [ 'site setup' ],
			workers: 1,
		},
		{
			name: 'blocks-chromium',
			testDir: `${ TESTS_ROOT_PATH }/tests/blocks`,
			dependencies: [ 'blocks setup' ],
			workers: 1,
			use: {
				...devices[ 'Desktop Chrome' ],
				storageState: BLOCKS_ADMIN_STATE,
			},
		},
		{
			name: 'blocks-legacy-mini-cart',
			testDir: `${ TESTS_ROOT_PATH }/tests/blocks`,
			testMatch: [
				'**/mini-cart/**/*.spec.ts',
				'**/add-to-cart-with-options/**/*.spec.ts',
				'**/product-button/**/*.spec.ts',
				'**/product-collection/**/*.spec.ts',
			],
			dependencies: [ 'blocks setup' ],
			workers: 1,
			use: {
				...devices[ 'Desktop Chrome' ],
				storageState: BLOCKS_ADMIN_STATE,
			},
		},
	],
} );
