/**
 * Internal dependencies
 */
import defaultConfig, { setupProjects } from '../../playwright.config';
import { tags } from '../../fixtures/fixtures';

process.env.USE_WP_ENV = 'true';

const config = {
	...defaultConfig,
	projects: [
		...setupProjects,
		{
			name: 'WooCommerce Shipping & Tax',
			grep: new RegExp( tags.SERVICES ),
			dependencies: [ 'site setup' ],
		},
	],
};

export default config;
