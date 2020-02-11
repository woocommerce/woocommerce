module.exports = {
	// Automatically clear mock calls and instances between every test
	clearMocks: true,

	// An array of file extensions your modules use
	moduleFileExtensions: [ 'js' ],

	moduleNameMapper: {
		'@woocommerce/e2e-tests/(.*)':
			'<rootDir>/node_modules/woocommerce/tests/e2e-tests/$1',
	},

	preset: 'jest-puppeteer',

	// Where to look for test files
	roots: [ '<rootDir>/tests/e2e-tests/specs' ],

	setupFiles: [ '<rootDir>/tests/e2e-tests/config/env.setup.js' ],
	// A list of paths to modules that run some code to configure or set up the testing framework
	// before each test
	setupFilesAfterEnv: [
		'<rootDir>/tests/e2e-tests/config/jest.setup.js',
		'expect-puppeteer',
	],

	// The glob patterns Jest uses to detect test files
	testMatch: [ '**/*.(test|spec).js' ],

	// Sort test path alphabetically. This is needed so that `activate-and-setup` tests run first
	testSequencer: '<rootDir>/tests/e2e-tests/config/jest-custom-sequencer.js',

	transformIgnorePatterns: [ 'node_modules/(?!(woocommerce)/)' ],
};
