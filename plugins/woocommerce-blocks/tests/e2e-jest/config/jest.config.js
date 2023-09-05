module.exports = {
	...require( '@wordpress/scripts/config/jest-e2e.config' ),
	rootDir: '../../../',
	// Automatically clear mock calls and instances between every test
	clearMocks: true,
	moduleNameMapper: {
		'@woocommerce/blocks-test-utils': '<rootDir>/tests/utils',
	},

	reporters: [
		'default',
		[
			'jest-html-reporters',
			{ publicPath: './reports/e2e', filename: 'index.html' },
		],
		process.env.CI && '@wordpress/e2e-tests/config/flaky-tests-reporter.js',
	].filter( Boolean ),

	testEnvironment: '<rootDir>/tests/e2e-jest/config/environment.js',
	testRunner: 'jest-circus/runner',
	// Where to look for test files
	roots: [ '<rootDir>/tests/e2e-jest/specs' ],
	globalSetup: '<rootDir>/tests/e2e-jest/config/setup.js',
	globalTeardown: '<rootDir>/tests/e2e-jest/config/teardown.js',
	setupFiles: [],
	// A list of paths to modules that run some code to configure or set up the testing framework
	// before each test
	setupFilesAfterEnv: [
		'<rootDir>/tests/e2e-jest/config/custom-matchers/index.js',
		'<rootDir>/tests/e2e-jest/config/jest.setup.js',
		'expect-puppeteer',
	],
	testPathIgnorePatterns: [
		'<rootDir>/tests/e2e-jest/specs/performance',
		// Ignore all the files that have utils in the name
		'utils',
	],
	transformIgnorePatterns: [
		'node_modules/?!(simple-html-tokenizer|is-plain-obj|is-plain-object|memize)',
		'node_modules/@woocommerce/e2e-utils/node_modules/config',
	],
	testMatch: [ '**/?(*.)+(spec|test).[jt]s?(x)' ],
};
