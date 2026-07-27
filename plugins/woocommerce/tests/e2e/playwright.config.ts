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
		'http://localhost:' + ( process.env.WP_ENV_PORT || '8086' );
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
	// Toggles the global `woocommerce_analytics_scheduled_import` option to
	// exercise the Settings-page scheduled/immediate switch. Kept serial because,
	// as a standalone file, running it in `core-parallel` would race
	// `analytics.spec.ts` — which also toggles that option — across workers.
	// (The other analytics specs are parallel: `analytics` owns its own
	// import-mode toggles plus the Overview manual-trigger tests in one file, so
	// those serialise within a single worker; `analytics-overview` only mutates
	// the admin's own `dashboard_sections` meta. No other parallel spec depends on
	// the order-import mode, and this serial job never runs concurrently with the
	// parallel one.)
	'**/tests/analytics/analytics-settings.spec.ts',
	// Flips the global `woocommerce_default_customer_address` (geolocation) and
	// `woocommerce_enable_ajax_add_to_cart` settings, which change add-to-cart
	// behavior for every other worker. (`cart.spec.ts` runs in core-parallel — it
	// scopes its tax rate to a dedicated tax class instead of toggling global tax.)
	'**/tests/cart/add-to-cart.spec.ts',
	// Activates a custom-gateway test plugin globally, which would surface its extra
	// payment button on every other worker's checkout.
	'**/tests/checkout/checkout-shortcode-custom-place-order-button.spec.ts',
	// Every spec toggles a global email feature flag via `setOption`:
	// `editor-tracking-selectors`/`settings-email-listing` flip
	// `woocommerce_feature_block_email_editor_enabled`, while `account-emails`/
	// `order-emails`/`settings-email` flip `woocommerce_feature_email_improvements_enabled`.
	// Run in parallel they race on those options — one file's afterAll disables the
	// editor (or flips improvements) mid-test for the others. Proven not parallel-safe:
	// an email-only `core-parallel` run failed across all three clusters.
	'**/tests/email/**/*.spec.ts',
	// Each spec toggles the global `woocommerce_feature_block_email_editor_enabled`
	// flag in beforeAll/afterAll; running the files concurrently races on that option
	// (`e2e-options/update` returns 400 "Update option FAILED") and the first file's
	// afterAll disables the editor mid-test for the others. Proven not parallel-safe.
	'**/tests/email-editor/**/*.spec.ts',
	// Mutate the global onboarding profile/options, site-visibility options and
	// the active theme.
	'**/tests/onboarding/**/*.spec.ts',
	// Toggles the global `woocommerce_downloads_grant_access_after_payment` setting.
	'**/tests/order/order-edit.spec.ts',
	// Submits and deletes product reviews via the Review Order form while it runs;
	// that concurrent churn on the shared reviews list makes `product-reviews`'
	// trash/undo/re-trash flow intermittently fail (proven by bisect: moving it
	// serial turns 3 consecutive product-reviews failures green).
	'**/tests/order/review-order-page.spec.ts',
	// Imports a fixed-content CSV (fixed SKUs/names) and asserts the imported rows
	// on the store-wide product list — collides with concurrently created products.
	'**/tests/product/product-import-csv.spec.ts',
	// Mutate global WooCommerce settings (store address/currency/country, tax)
	// that other workers' cart/checkout/storefront specs depend on.
	'**/tests/settings/settings-general.spec.ts',
	'**/tests/settings/settings-tax.spec.ts',
	// Unchecks and saves `woocommerce_enable_reviews`, flipping that global option
	// to `no` mid-run (restored only in afterAll). While off, the front-end Reviews
	// tab and admin review management disappear — proven to deterministically fail 3
	// `product/product-reviews.spec.ts` tests (shopper post + the edit/reply Reviews
	// tab assertions). Also toggles the global `settings-ui` feature flag and resets
	// ALL e2e feature flags in afterAll.
	'**/tests/settings/settings-ui-feature-flag.spec.ts',
	// Toggles the global `woocommerce_cart_redirect_after_add` setting, which
	// changes add-to-cart behavior for every other worker — not parallel-safe.
	'**/tests/shop/cart-redirection.spec.ts',
	// Trashes and restores the global Shop page in a fixture; while trashed, every
	// other worker's shop/cart/account navigation 404s.
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
		channel: 'chromium',
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
	],
} );
