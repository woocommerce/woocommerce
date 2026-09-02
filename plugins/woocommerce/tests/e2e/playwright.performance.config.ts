/**
 * External dependencies
 */
import { defineConfig, devices } from '@playwright/test';

/**
 * Internal dependencies
 */
import baseConfig, { TESTS_ROOT_PATH } from './playwright.config';
import { adminFile } from './utils/blocks/constants';

export default defineConfig( {
	...baseConfig,
	projects: [
		{
			name: 'blocks setup',
			testDir: `${ TESTS_ROOT_PATH }/fixtures`,
			testMatch: 'blocks-setup.ts',
		},
		{
			name: 'blocks-performance',
			testDir: `${ TESTS_ROOT_PATH }/tests/blocks`,
			testMatch: '**/*.perf.ts',
			dependencies: [ 'blocks setup' ],
			fullyParallel: false,
			use: { ...devices[ 'Desktop Chrome' ], storageState: adminFile },
		},
	],
} );
