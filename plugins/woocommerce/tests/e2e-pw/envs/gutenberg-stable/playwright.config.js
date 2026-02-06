import config, { setupProjects } from '../../playwright.config.mjs';
import { tags } from '../../fixtures/fixtures';

process.env.USE_WP_ENV = 'true';

export default {
	...config,
	projects: [
		...setupProjects,
		{
			name: 'Gutenberg',
			grep: new RegExp( tags.GUTENBERG ),
			dependencies: [ 'site setup' ],
		},
	],
};
