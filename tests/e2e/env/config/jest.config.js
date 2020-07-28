const { jestConfig } = require( '@automattic/puppeteer-utils' );

module.exports = {
	...jestConfig,
	moduleNameMapper: {
		'@woocommerce/e2e-tests/(.*)':
			'<rootDir>/tests/e2e/$1',
	},

	setupFiles: [ '<rootDir>/config/env.setup.js' ],
	// A list of paths to modules that run some code to configure or set up the testing framework
	// before each test
	setupFilesAfterEnv: [
		'<rootDir>/build/setup/jest.setup.js',
		'expect-puppeteer',
	],

	// Sort test path alphabetically. This is needed so that `activate-and-setup` tests run first
	testSequencer: '<rootDir>/config/jest-custom-sequencer.js',
	// Set the test timeout in milliseconds.
	testTimeout: parseInt( global.process.env.jest_test_timeout ),

	transformIgnorePatterns: [
		...jestConfig.transformIgnorePatterns,
		'node_modules/(?!(woocommerce)/)'
	],
};
