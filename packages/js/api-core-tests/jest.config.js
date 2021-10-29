require('dotenv').config();
const { BASE_URL, VERBOSE, USE_INDEX_PERMALINKS } = process.env;
const verboseOutput = VERBOSE === 'true';

// Update the API path if the `USE_INDEX_PERMALINKS` flag is set
const useIndexPermalinks = USE_INDEX_PERMALINKS === 'true';
let apiPath = `${BASE_URL}/?rest_route=/wc/v3/`;
if ( useIndexPermalinks ) {
	apiPath = `${BASE_URL}/wp-json/wc/v3/`;
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
};
