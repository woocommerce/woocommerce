/**
 * External dependencies
 */
import { defineConfig, PlaywrightTestConfig } from '@playwright/test';

/**
 * Internal dependencies
 */
import baseConfig from './playwright.config';

const config: PlaywrightTestConfig = {
	...baseConfig,
	projects: [
		{
			name: 'chromium',
			testDir: '.',
			testMatch: '*.perf.ts',
		},
	],
};

export default defineConfig( config );
