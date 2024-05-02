/**
 * External dependencies
 */
import { fileURLToPath } from 'url';
import { BASE_URL, STORAGE_STATE_PATH } from '@woocommerce/e2e-utils';
import { PlaywrightTestConfig, defineConfig, devices } from '@playwright/test';

const { CI, DEFAULT_TIMEOUT_OVERRIDE } = process.env;

const config: PlaywrightTestConfig = {
	maxFailures: 0,
	timeout: parseInt( DEFAULT_TIMEOUT_OVERRIDE || '', 10 ) || 100_000, // Defaults to 100s.
	outputDir: './artifacts/test-results',
	globalSetup: fileURLToPath(
		new URL( 'global-setup.ts', 'file:' + __filename ).href
	),
	testDir: './tests',
	retries: CI ? 2 : 0,
	// We're running our tests in serial, so we only need one worker.
	workers: 1,
	fullyParallel: false,
	// Don't report slow test "files", as we're running our tests in serial.
	reportSlowTests: null,
	reporter: process.env.CI
		? [
				[ 'github' ],
				[ 'list' ],
				[ 'blob', { outputDir: './artifacts/blob-report' } ],
		  ]
		: 'list',
	use: {
		baseURL: BASE_URL,
		screenshot: 'only-on-failure',
		trace: 'retain-on-failure',
		video: 'on-first-retry',
		viewport: { width: 1280, height: 720 },
		storageState: STORAGE_STATE_PATH,
		actionTimeout: 10_000,
		navigationTimeout: 10_000,
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
};

export default defineConfig( config );
