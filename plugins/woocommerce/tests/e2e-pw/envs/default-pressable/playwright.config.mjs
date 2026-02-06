/**
 * Internal dependencies
 */
import config, {
	setupProjects,
	TESTS_ROOT_PATH,
} from '../../playwright.config.mjs';
import { tags } from '../../fixtures/fixtures';

process.env.IS_PRESSABLE = 'true';
process.env.INSTALL_WC = 'true';

const grepInvert = new RegExp(
	`${ tags.SKIP_ON_PRESSABLE }|${ tags.SKIP_ON_EXTERNAL_ENV }|${ tags.COULD_BE_LOWER_LEVEL_TEST }|${ tags.NON_CRITICAL }|${ tags.TO_BE_REMOVED }`
);

export default {
	...config,
	projects: [
		...setupProjects,
		{
			name: 'reset',
			testDir: `${ TESTS_ROOT_PATH }/fixtures`,
			testMatch: 'reset.setup.js',
		},
		{
			name: 'e2e-pressable',
			testIgnore: [ '**/api-tests/**', '**/js-file-monitor/**' ],
			grepInvert,
			dependencies: [ 'reset', 'site setup' ],
		},
		{
			name: 'api-pressable',
			testMatch: [ '**/api-tests/**' ],
			grepInvert,
			dependencies: [ 'reset', 'site setup' ],
		},
	],
};
