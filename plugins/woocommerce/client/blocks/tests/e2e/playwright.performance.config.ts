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
			// testDir is inherited from the base config, which points at the
			// migrated specs under tests/e2e-pw/tests/blocks.
			testMatch: '**/*.perf.ts',
		},
	],
};

export default defineConfig( config );
