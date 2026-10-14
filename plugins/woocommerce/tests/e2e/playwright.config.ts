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

export const coreSetupProjects = [
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
];

const blocksSetupProject = {
	name: 'blocks setup',
	testDir: `${ TESTS_ROOT_PATH }/fixtures`,
	testMatch: 'blocks-setup.ts',
};

/**
 * Spec folders that must run serially in `core-serial` (they mutate global
 * state or share fixtures). Every other folder under `tests/` runs in
 * `core-parallel` by default, except the other-project folders in `nonCoreSpecs`.
 *
 * Specs that only race each other on the same options run in `core-parallel`
 * with a shared `locks` entry (see `fixtures/fixtures.ts`) instead. A lock
 * does not help when a spec changes a setting that unlocked specs read.
 */
const serialRunSpecs = [
	// Flips the global `woocommerce_default_customer_address` (geolocation) and
	// `woocommerce_enable_ajax_add_to_cart` settings, which change add-to-cart
	// behavior for every other worker. (`cart.spec.ts` runs in core-parallel — it
	// scopes its tax rate to a dedicated tax class instead of toggling global tax.)
	'**/tests/cart/add-to-cart.spec.ts',
	// Activates a custom-gateway test plugin globally, which would surface its extra
	// payment button on every other worker's checkout.
	'**/tests/checkout/checkout-shortcode-custom-place-order-button.spec.ts',
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
	// Toggles the global out-of-stock catalog visibility setting while verifying
	// that converted external products remain visible on the storefront.
	'**/tests/product/product-grouped-stock-status.spec.ts',
	// Mutate global WooCommerce settings (store address/currency/country, tax)
	// that other workers' cart/checkout/storefront specs depend on.
	'**/tests/settings/settings-general.spec.ts',
	// Mutates the global woocommerce_permalinks option (the product base) and
	// restores it in teardown.
	'**/tests/settings/product-permalinks.spec.ts',
	'**/tests/settings/settings-tax.spec.ts',
	// Toggles the global `settings-ui` feature flag and resets all e2e feature flags
	// in afterAll.
	'**/tests/settings/settings-ui-feature-flag.spec.ts',
	// Toggles the global `woocommerce_cart_redirect_after_add` setting, which
	// changes add-to-cart behavior for every other worker — not parallel-safe.
	'**/tests/shop/cart-redirection.spec.ts',
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

/**
 * Blocks specs that must run serially in `blocks-serial`, which resets the
 * database after each test. Every other blocks spec runs in `blocks-parallel`
 * by default, without that reset, against a site that other workers use at the
 * same time.
 *
 * A parallel spec must clean up what it creates and must not change state that
 * other specs read, unless it shares a `locks` entry (see `fixtures/fixtures.ts`)
 * with every spec that reads that state.
 *
 * Never run `blocks-serial` and `blocks-parallel` in the same invocation: the
 * serial resets wipe the data of the parallel workers.
 */
const blocksSerialSpecs = [
	// Switch the active theme, which every other spec renders with.
	'**/tests/blocks/add-to-cart-with-options/add-to-cart-with-options.block_theme_with_templates.spec.ts',
	'**/tests/blocks/cart/cart-checkout-block-notices.shopper.block_theme.spec.ts',
	'**/tests/blocks/cart/cart-checkout-block-notices.shopper.classic_theme.spec.ts',
	'**/tests/blocks/mini-cart/mini-cart.classic_theme.spec.ts',
	'**/tests/blocks/product-button/product-button.classic_theme.spec.ts',
	'**/tests/blocks/style.classic_theme.spec.ts',
	'**/tests/blocks/templates/single-product-template.block_theme_with_templates.spec.ts',
	'**/tests/blocks/templates/template-customization.block_theme.spec.ts',
	'**/tests/blocks/templates/template-customization.block_theme_with_templates.spec.ts',
	'**/tests/blocks/templates/template-part-customization.classic_theme_with_template_parts.spec.ts',
	'**/tests/blocks/templates/template-part-customization.classic_theme_with_template_parts_support.spec.ts',
	'**/tests/blocks/templates/template-priority.block_theme.spec.ts',
	'**/tests/blocks/widget-area/widget-area.classic_theme.spec.ts',

	// Save site-wide templates, template parts or pages (single product, product
	// archive, header, cart, checkout), or the
	// `wc_blocks_use_blockified_product_grid_block_as_template` option. Every
	// spec that visits those pages reads them.
	'**/tests/blocks/add-to-cart-form/add-to-cart-form.block_theme.spec.ts',
	'**/tests/blocks/add-to-cart-with-options/add-to-cart-with-options.block_theme.spec.ts',
	'**/tests/blocks/all-products/all-products.block_theme.spec.ts',
	'**/tests/blocks/attributes-filter/attribute-filter.block_theme.spec.ts',
	'**/tests/blocks/cart-store/cart-line-identity.block_theme.spec.ts',
	'**/tests/blocks/checkout/checkout-block.merchant.block_theme.spec.ts',
	'**/tests/blocks/classic-template/classic-template.block_theme.spec.ts',
	'**/tests/blocks/mini-cart/mini-cart-block.shopper.block_theme.spec.ts',
	'**/tests/blocks/on-sale-badge/on-sale-badge-single-product-template.block_theme.spec.ts',
	'**/tests/blocks/page-content-wrapper/page-content-wrapper.block_theme.spec.ts',
	'**/tests/blocks/price-filter/price-filter.block_theme.spec.ts',
	'**/tests/blocks/product-collection/product-collection.block_theme.spec.ts',
	'**/tests/blocks/product-filters/active-filter-frontend.block_theme.spec.ts',
	'**/tests/blocks/product-filters/attribute-filter-frontend.block_theme.spec.ts',
	'**/tests/blocks/product-filters/product-filters-frontend.block_theme.spec.ts',
	'**/tests/blocks/product-gallery/inner-blocks/product-gallery-large-image/product-gallery-large-image.block_theme.spec.ts',
	'**/tests/blocks/product-gallery/inner-blocks/product-gallery-thumbnails/product-gallery-thumbnails.block_theme.spec.ts',
	'**/tests/blocks/product-gallery/product-gallery.block_theme.spec.ts',
	'**/tests/blocks/products/products.block_theme.spec.ts',
	'**/tests/blocks/rating-filter/rating-filter.block_theme.spec.ts',
	'**/tests/blocks/stock-filter/stock-filter.block_theme.spec.ts',
	'**/tests/blocks/templates/legacy-templates.block_theme.spec.ts',

	// Activate test plugins that change every cart, checkout or product page, or
	// race other specs on the `active_plugins` option.
	'**/tests/blocks/cart/cart-block.shopper.block_theme.spec.ts',
	'**/tests/blocks/cart/cart-checkout-block-extension-callbacks.shopper.block_theme.spec.ts',
	'**/tests/blocks/cart/cart-store.block_theme.spec.ts',
	'**/tests/blocks/checkout/additional-fields.guest-shopper.block_theme.spec.ts',
	'**/tests/blocks/checkout/additional-fields.merchant.block_theme.spec.ts',
	'**/tests/blocks/checkout/additional-fields.shopper.block_theme.spec.ts',
	'**/tests/blocks/checkout/checkout-block-custom-place-order-button.block_theme.spec.ts',
	'**/tests/blocks/checkout/checkout-block-extensibility.shopper.block_theme.spec.ts',
	'**/tests/blocks/checkout/checkout-block-locale-hide-country.block_theme.spec.ts',
	'**/tests/blocks/mini-cart/mini-cart.block_theme.spec.ts',
	'**/tests/blocks/product-button/product-button.block_theme.spec.ts',
	'**/tests/blocks/product-collection/compatibility-layer.block_theme.spec.ts',
	'**/tests/blocks/product-collection/product-picker.block_theme.spec.ts',
	'**/tests/blocks/product-collection/register-product-collection.block_theme.spec.ts',
	'**/tests/blocks/single-product-template/single-product-template-compatibility-layer.spec.ts',

	// Change global settings that cart, checkout and storefront specs read:
	// shipping, taxes, local pickup, account creation, site language, the shop
	// page slug, product reviews and feature flags.
	'**/tests/blocks/cart/cart-checkout-block-shipping.block_theme.spec.ts',
	'**/tests/blocks/cart/cart-checkout-block-taxes.shopper.block_theme.spec.ts',
	'**/tests/blocks/cart/cart-checkout-block-translations.shopper.block_theme.spec.ts',
	'**/tests/blocks/checkout/checkout-block.shopper.block_theme.spec.ts',
	'**/tests/blocks/checkout/order-confirmation.block_theme.spec.ts',
	'**/tests/blocks/local-pickup/local-pickup.merchant.block_theme.spec.ts',
	'**/tests/blocks/product-collection/inspector-controls.block_theme.spec.ts',
	'**/tests/blocks/single-product-template/single-product-template.product-rating.spec.ts',
	'**/tests/blocks/templates/shop-page.block_theme.spec.ts',

	// Add to the cart as the admin user, whose cart all workers share, and
	// place orders, which change the Best Sellers ranking.
	'**/tests/blocks/cart/cart-checkout-block-coupons.shopper.block_theme.spec.ts',

	// Create or edit products and categories that catalog specs count.
	'**/tests/blocks/product-collection/product-collection-errors.block_theme.spec.ts',
];

export default defineConfig< { resetDatabaseAfterEachTest: boolean } >( {
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
		...coreSetupProjects,
		blocksSetupProject,
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
			name: 'blocks-serial',
			testDir: `${ TESTS_ROOT_PATH }/tests/blocks`,
			testMatch: blocksSerialSpecs,
			dependencies: [ 'blocks setup' ],
			workers: 1,
			use: {
				...devices[ 'Desktop Chrome' ],
				storageState: BLOCKS_ADMIN_STATE,
			},
		},
		{
			name: 'blocks-parallel',
			testDir: `${ TESTS_ROOT_PATH }/tests/blocks`,
			testIgnore: blocksSerialSpecs,
			dependencies: [ 'blocks setup' ],
			use: {
				...devices[ 'Desktop Chrome' ],
				storageState: BLOCKS_ADMIN_STATE,
				resetDatabaseAfterEachTest: false,
			},
		},
	],
} );
