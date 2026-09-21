/**
 * Internal dependencies
 */
import defaultConfig, { coreSetupProjects } from '../../playwright.config';
import { tags } from '../../fixtures/fixtures';

process.env.USE_WP_ENV = 'true';

const config = {
	...defaultConfig,
	projects: [
		...coreSetupProjects,
		{
			name: 'WooPayments',
			grep: new RegExp( tags.PAYMENTS ),
			dependencies: [ 'site setup' ],
		},
	],
};

export default config;
