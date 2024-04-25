/**
 * External dependencies
 */
import { defineConfig, PlaywrightTestConfig } from '@playwright/test';
import { BASE_URL, STORAGE_STATE_PATH } from '@woocommerce/e2e-utils';

import { fileURLToPath } from 'url';

// eslint-disable-next-line @typescript-eslint/no-var-requires
require( 'dotenv' ).config();
interface ExtendedPlaywrightTestConfig extends PlaywrightTestConfig {
	use: {
		stateDir?: string;
	} & PlaywrightTestConfig[ 'use' ];
}

const { CI, DEFAULT_TIMEOUT_OVERRIDE, E2E_MAX_FAILURES } = process.env;

const config: ExtendedPlaywrightTestConfig = {
	timeout: parseInt( DEFAULT_TIMEOUT_OVERRIDE || '', 10 ) || 100_000, // Defaults to 100s.
	outputDir: 'artifacts/test-results',
	globalSetup: fileURLToPath(
		new URL( 'global-setup.ts', 'file:' + __filename ).href
	),
	globalTeardown: require.resolve( './global-teardown' ),
	testDir: 'tests',
	retries: CI ? 2 : 0,
	workers: 1,
	// Don't report slow test "files", as we're running our tests in serial.
	reportSlowTests: null,
	reporter: process.env.CI
		? [ [ 'github' ], [ 'list' ], [ 'html' ] ]
		: 'list',
	maxFailures: E2E_MAX_FAILURES ? Number( E2E_MAX_FAILURES ) : 0,
	snapshotPathTemplate:
		'{testDir}/{testFileDir}/__screenshots__/{arg}{testName}{ext}',
	use: {
		baseURL: BASE_URL,
		screenshot: 'only-on-failure',
		stateDir: 'tests/e2e/test-results/storage/',
		trace: 'retain-on-failure',
		video: 'on-first-retry',
		viewport: { width: 1280, height: 720 },
		storageState: STORAGE_STATE_PATH,
		actionTimeout: 10_000,
		navigationTimeout: 10_000,
	},
	projects: [
		{
			name: 'blockThemeConfiguration',
			testDir: '.',
			testMatch: /block-theme.setup.ts/,
		},
		{
			name: 'blockTheme',
			testMatch: /.*.block_theme.spec.ts/,
			dependencies: [ 'blockThemeConfiguration' ],
		},
	],
};

export default defineConfig( config );
