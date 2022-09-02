require( 'dotenv' ).config();
const {
	BASE_URL,
	VERBOSE,
	USE_INDEX_PERMALINKS,
	DEFAULT_TIMEOUT_OVERRIDE,
} = process.env;
const verboseOutput = VERBOSE === 'true';
const { defaults } = require( 'jest-config' );

/**
 * Override the default timeout, if specified.
 * Useful when running API tests against an externally hosted test site.
 */
const testTimeoutOverride = DEFAULT_TIMEOUT_OVERRIDE
	? Number( DEFAULT_TIMEOUT_OVERRIDE )
	: defaults.testTimeout;

// Update the API path if the `USE_INDEX_PERMALINKS` flag is set
const useIndexPermalinks = USE_INDEX_PERMALINKS === 'true';
let apiPath = `${ BASE_URL }/?rest_route=/wc/v3/`;
if ( useIndexPermalinks ) {
	apiPath = `${ BASE_URL }/wp-json/wc/v3/`;
}

module.exports = {
	// Use the `jest-runner-groups` package.
	runner: 'groups',

	// A set of global variables that need to be available in all test environments
	globals: {
		API_PATH: apiPath,
	},

	// Indicates whether each individual test should be reported during the run
	verbose: verboseOutput,

	/**
	 * Configure `jest-allure` for Jest v24 and above.
	 *
	 * @see https://www.npmjs.com/package/jest-allure#jest--v-24-
	 */
	setupFilesAfterEnv: [
		'jest-allure/dist/setup',
		'<rootDir>/allure.config.js',
	],

	/**
	 * Make sure Jest is using Jasmine as its test runner.
	 * `jest-allure` does not work with the `jest-circus` test runner, which is the default test runner of Jest starting from v27.
	 *
	 * @see https://github.com/zaqqaz/jest-allure#uses-jest-circus-or-jest--v-27-
	 */
	testRunner: 'jasmine2',

	testTimeout: testTimeoutOverride,
};
