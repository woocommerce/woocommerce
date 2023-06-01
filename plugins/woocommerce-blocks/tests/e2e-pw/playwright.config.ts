/**
 * External dependencies
 */
import { defineConfig, PlaywrightTestConfig } from '@playwright/test';
import { BASE_URL, STORAGE_STATE_PATH } from '@woocommerce/e2e-utils';
import path from 'path';

import { fileURLToPath } from 'url';

interface ExtendedPlaywrightTestConfig extends PlaywrightTestConfig {
	use: {
		stateDir?: string;
	} & PlaywrightTestConfig[ 'use' ];
}

const { CI, DEFAULT_TIMEOUT_OVERRIDE, E2E_MAX_FAILURES } = process.env;

const config: ExtendedPlaywrightTestConfig = {
	timeout: DEFAULT_TIMEOUT_OVERRIDE
		? Number( DEFAULT_TIMEOUT_OVERRIDE )
		: 90 * 1000,
	expect: { timeout: 20 * 1000 },
	outputDir: path.join( process.cwd(), 'artifacts/test-results' ),
	globalSetup: fileURLToPath(
		new URL( 'global-setup.ts', 'file:' + __filename ).href
	),
	globalTeardown: require.resolve( './global-teardown' ),
	testDir: 'tests',
	retries: CI ? 2 : 0,
	workers: 1,
	reporter: process.env.CI
		? [ [ 'github' ], [ 'list' ], [ 'html' ] ]
		: 'list',
	maxFailures: E2E_MAX_FAILURES ? Number( E2E_MAX_FAILURES ) : 0,
	use: {
		baseURL: BASE_URL,
		screenshot: 'only-on-failure',
		stateDir: './tests/e2e-pw/test-results/storage/',
		trace: 'retain-on-failure',
		video: 'on-first-retry',
		viewport: { width: 1280, height: 720 },
		storageState: STORAGE_STATE_PATH,
	},
	projects: [
		{
			name: 'blockThemeConfiguration',
			testMatch: /block-theme.setup.ts/,
		},
		{
			name: 'blockTheme',
			testMatch: /.*.block_theme.spec.ts/,
			dependencies: [ 'blockThemeConfiguration' ],
		},
		{
			name: 'blockThemeWithGlobalSideEffects',
			testMatch: /.*.block_theme.side_effects.spec.ts/,
			dependencies: [ 'blockTheme' ],
			fullyParallel: false,
		},
		{
			name: 'classicThemeConfiguration',
			testMatch: /block-theme.setup.ts/,
			dependencies: [ 'blockThemeWithGlobalSideEffects' ],
		},
		{
			name: 'classicTheme',
			testMatch: /.*.classic_theme.spec.ts/,
			dependencies: [ 'classicThemeConfiguration' ],
		},
	],
};

export default defineConfig( config );
