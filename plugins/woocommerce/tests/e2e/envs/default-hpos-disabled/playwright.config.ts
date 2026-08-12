/**
 * Internal dependencies
 */
import defaultConfig, { setupProjects } from '../../playwright.config';
import { tags } from '../../fixtures/fixtures';

process.env.USE_WP_ENV = 'true';
process.env.DISABLE_HPOS = '1';

const config = {
	...defaultConfig,
	projects: [
		...setupProjects,
		{
			name: 'e2e-hpos-disabled',
			grep: new RegExp( tags.HPOS ),
			dependencies: [ 'site setup' ],
		},
		{
			name: 'api-hpos-disabled',
			testMatch: [ '**/api-tests/**' ],
			dependencies: [ 'site setup' ],
		},
	],
};

export default config;
