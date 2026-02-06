/**
 * Internal dependencies
 */
import config, { setupProjects } from '../../playwright.config.mjs';
import { tags } from '../../fixtures/fixtures';

process.env.USE_WP_ENV = 'true';
process.env.DISABLE_HPOS = '1';

export default {
	...config,
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
