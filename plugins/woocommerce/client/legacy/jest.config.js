const preset = require( '@wordpress/jest-preset-default/jest-preset.js' );

module.exports = {
	...preset,
	rootDir: './js',
	collectCoverageFrom: [ 'js/**/*.js', '!**/node_modules/**' ],
	moduleDirectories: [ 'node_modules' ],
	testPathIgnorePatterns: [
		'<rootDir>/build/',
		'<rootDir>/node_modules/',
		'<rootDir>/tests/',
	],
	roots: [ '<rootDir>' ],
	transform: {},
	verbose: true,
	cacheDirectory: '<rootDir>/../../node_modules/.cache/jest',
	testEnvironment: 'jest-fixed-jsdom',
};
