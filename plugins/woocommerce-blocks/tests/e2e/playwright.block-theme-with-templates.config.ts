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
	outputDir: 'artifacts/test-results-block-theme-with-templates',
	fullyParallel: true,
	workers: 1,
	projects: [
		{
			name: 'blockThemeWithTemplatesConfiguration',
			testDir: '.',
			testMatch: /block-theme-with-templates.setup.ts/,
		},
		{
			name: 'blockThemeWithTemplates',
			testMatch: /.*.block_theme_with_templates.spec.ts/,
			dependencies: [ 'blockThemeWithTemplatesConfiguration' ],
		},
	],
} );
