/**
 * Internal dependencies
 */
import defaultConfig, {
	setupProjects,
	TESTS_ROOT_PATH,
} from '../../playwright.config';
import { tags } from '../../fixtures/fixtures';

process.env.IS_WPCOM = 'true';
process.env.INSTALL_WC = 'true';

const grepInvert = new RegExp(
	`${ tags.SKIP_ON_WPCOM }|${ tags.SKIP_ON_EXTERNAL_ENV }|${ tags.COULD_BE_LOWER_LEVEL_TEST }|${ tags.NON_CRITICAL }|${ tags.TO_BE_REMOVED }`
);

const config = {
	...defaultConfig,
	projects: [
		...setupProjects,
		{
			name: 'reset',
			testDir: `${ TESTS_ROOT_PATH }/fixtures`,
			testMatch: 'reset.setup.ts',
		},
		{
			name: 'e2e-wpcom',
			testIgnore: [ '**/api-tests/**', '**/js-file-monitor/**' ],
			grepInvert,
			dependencies: [ 'reset', 'site setup' ],
		},
		{
			name: 'api-wpcom',
			testMatch: [ '**/api-tests/**' ],
			grepInvert,
			dependencies: [ 'reset', 'site setup' ],
		},
	],
};

export default config;
