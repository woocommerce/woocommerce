/**
 * @flow strict
 * @format
 */

// https://jestjs.io/docs/en/configuration.html

module.exports = {
	// Automatically clear mock calls and instances between every test
	clearMocks: true,

	// An array of file extensions your modules use
	moduleFileExtensions: [ 'js' ],

	preset: 'jest-puppeteer',

	// Where to look for test files
	roots: [ '<rootDir>/tests/e2e-tests/specs' ],

	//setupFiles: [ '<rootDir>/.node_modules/regenerator-runtime/runtime' ],

	// A list of paths to modules that run some code to configure or set up the testing framework
	// before each test
	setupFilesAfterEnv: [
		'<rootDir>/tests/e2e-tests/config/jest.setup.js',
		'expect-puppeteer',
	],

	// The glob patterns Jest uses to detect test files
	testMatch: [ '**/*.(test|spec).js' ],
};
