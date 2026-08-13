// eslint-disable-next-line max-len
const path = require( 'path' );
const {
	withWordPressDependencyCompat,
} = require( '@woocommerce/jest-wordpress-version-compat' );

const missingWpVersionMessage =
	'WP_VERSION is not set. This test run is using the installed @wordpress packages and may not rely on a validated WordPress package environment. Set WP_VERSION=latest, WP_VERSION=latest-1, or WP_VERSION=gutenberg to run WordPress package compatibility tests.';

const config = {
	rootDir: './js',
	collectCoverageFrom: [ 'js/**/*.js', '!**/node_modules/**' ],
	moduleDirectories: [ 'node_modules' ],
	preset: '@wordpress/jest-preset-default',
	testPathIgnorePatterns: [
		'<rootDir>/build/',
		'<rootDir>/node_modules/',
		'<rootDir>/tests/',
	],
	roots: [ '<rootDir>' ],
	transform: {
		'^.+\\.(js|ts|tsx)$': '<rootDir>/../babel-transformer.js',
	},
	verbose: true,
	cacheDirectory: '<rootDir>/../../node_modules/.cache/jest',
	testEnvironment: 'jest-fixed-jsdom',
};

// TODO: Migrate this custom Jest config to @woocommerce/internal-js-tests/jest-preset.js.
if ( process.env.WP_VERSION ) {
	module.exports = withWordPressDependencyCompat( config, {
		cwd: __dirname,
		wpVersion: process.env.WP_VERSION,
	} );
} else {
	if ( process.env.CI ) {
		throw new Error( missingWpVersionMessage );
	}

	// eslint-disable-next-line no-console
	console.warn( missingWpVersionMessage );

	module.exports = config;
}
