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
			name: 'Gutenberg',
			grep: new RegExp( tags.GUTENBERG ),
			dependencies: [ 'site setup' ],
		},
	],
};

export default config;
