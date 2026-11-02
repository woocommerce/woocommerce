const preset = require( './node_modules/@woocommerce/internal-js-tests/jest-preset.js' );

module.exports = {
	rootDir: './',
	roots: [ '<rootDir>/src' ],
	preset: './node_modules/@woocommerce/internal-js-tests/jest-preset.js',
	moduleNameMapper: {
		// The `/wp` entry ships as ESM, which jest cannot parse. The package
		// root is the same 17.1.0 source built as CommonJS, so tests exercise
		// the same DataForm; the e2e suite covers the shipped `/wp` bundle.
		'^@wordpress/dataviews/wp$': require.resolve( '@wordpress/dataviews' ),
		...preset.moduleNameMapper,
	},
};
