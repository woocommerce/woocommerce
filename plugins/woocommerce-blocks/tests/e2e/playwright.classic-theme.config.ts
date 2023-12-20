/**
 * External dependencies
 */
import { defineConfig } from '@playwright/test';

/**
 * Internal dependencies
 */
import config from './playwright.config';

export default defineConfig( {
	...config,
	outputDir: 'artifacts/test-results-classic-theme',
	fullyParallel: false,
	workers: 1,
	projects: [
		{
			name: 'classicThemeConfiguration',
			testDir: '.',
			testMatch: /classic-theme.setup.ts/,
		},
		{
			name: 'classicTheme',
			testMatch: /.*.classic_theme.spec.ts/,
			dependencies: [ 'classicThemeConfiguration' ],
		},
	],
} );
