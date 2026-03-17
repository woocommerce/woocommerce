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
			name: 'WooCommerce PayPal Payments',
			grep: new RegExp( tags.PAYMENTS ),
			dependencies: [ 'site setup' ],
		},
	],
};

export default config;
