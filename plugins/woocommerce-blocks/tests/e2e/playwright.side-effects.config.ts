/**
 * External dependencies
 */
import { defineConfig } from '@playwright/test';
import path from 'path';

/**
 * Internal dependencies
 */
import config from './playwright.config';

export default defineConfig( {
	...config,
	outputDir: path.join(
		process.cwd(),
		'artifacts/test-results-side-effects'
	),
	fullyParallel: false,
	workers: 1,
	projects: [
		{
			name: 'blockThemeConfiguration',
			testDir: '.',
			testMatch: /block-theme.setup.ts/,
		},
		{
			name: 'blockThemeWithGlobalSideEffects',
			testMatch: /.*.block_theme.side_effects.spec.ts/,
			dependencies: [ 'blockThemeConfiguration' ],
		},
	],
} );
