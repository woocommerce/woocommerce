/**
 * External dependencies
 */
import { fileURLToPath } from 'url';
import { BASE_URL, STORAGE_STATE_PATH } from '@woocommerce/e2e-utils';
import { PlaywrightTestConfig, defineConfig, devices } from '@playwright/test';

const { CI, DEFAULT_TIMEOUT_OVERRIDE } = process.env;

const config: PlaywrightTestConfig = {
	maxFailures: CI ? 30 : 0,
	timeout: parseInt( DEFAULT_TIMEOUT_OVERRIDE || '', 10 ) || 100_000, // Defaults to 100s.
	outputDir: `${ __dirname }/artifacts/test-results`,
	globalSetup: fileURLToPath(
		new URL( 'global-setup.ts', 'file:' + __filename ).href
	),
	testDir: './tests',
	retries: CI ? 1 : 0,
	workers: 1,
	reportSlowTests: { max: 5, threshold: 30 * 1000 }, // 30 seconds threshold
	fullyParallel: false,
	forbidOnly: !! CI,
	reporter: process.env.CI
		? [
				[ 'list' ],
				[
					'allure-playwright',
					{
						outputFolder: `${ __dirname }/artifacts/test-results/allure-results`,
					},
				],
				[ 'buildkite-test-collector/playwright/reporter' ],
				[
					'junit',
					{
						outputFile: `${ __dirname }/artifacts/test-results/results.xml`,
						stripANSIControlSequences: true,
						includeProjectInTestName: true,
					},
				],
				[
					'playwright-ctrf-json-reporter',
					{
						outputDir: `${ __dirname }/artifacts/test-results`,
						outputFile: `ctrf-report-${ Date.now() }.json`,
						branchName: process.env.GITHUB_REF_NAME || '',
						commit: process.env.GITHUB_SHA || '',
						appName: 'woocommerce-blocks',
						repositoryName: process.env.GITHUB_REPOSITORY || '',
					},
				],
		  ]
		: 'list',
	use: {
		baseURL: BASE_URL,
		screenshot: { mode: 'only-on-failure', fullPage: true },
		trace:
			/^https?:\/\/localhost/.test( BASE_URL ) || ! CI
				? 'retain-on-first-failure'
				: 'off',
		video: 'on-first-retry',
		viewport: { width: 1280, height: 720 },
		storageState: STORAGE_STATE_PATH,
		actionTimeout: 10_000,
		navigationTimeout: 10_000,
		contextOptions: {
			reducedMotion: 'reduce',
		},
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
			fullyParallel: true,
		},
		{
			name: 'legacy-mini-cart',
			testMatch: [
				'**/tests/mini-cart/**/*.spec.ts',
				'**/tests/add-to-cart-with-options/**/*.spec.ts',
				'**/tests/product-button/**/*.spec.ts',
				'**/tests/product-collection/**/*.spec.ts',
			],
			fullyParallel: true,
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
};

export default defineConfig( config );
