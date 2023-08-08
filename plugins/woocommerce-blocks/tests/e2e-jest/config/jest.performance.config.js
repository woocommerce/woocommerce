module.exports = {
	...require( './jest.config' ),

	reporters: [
		'default',
		'<rootDir>/tests/e2e-jest/config/performance-reporter.js',
	],

	// Where to look for test files
	roots: [ '<rootDir>/tests/e2e-jest/specs/performance' ],

	testPathIgnorePatterns: [],
};
