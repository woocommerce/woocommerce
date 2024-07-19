/**
 * External packages
 */
const path = require( 'path' );

// These modules need to be transformed because they are not transpiled to CommonJS.
// The top-level keys are the names of the packages and the values are the file
// regexes that need to be transformed. Note that these are relative to the
// package root and should be treated as such.
const transformModules = {
	'is-plain-obj': {
		'index\\.js$': 'babel-jest',
	},
};

module.exports = {
	moduleNameMapper: {
		tinymce: path.resolve( __dirname, 'build/mocks/tinymce' ),
		'@woocommerce/settings': path.resolve(
			__dirname,
			'build/mocks/woocommerce-settings'
		),
		'@woocommerce/tracks': path.resolve(
			__dirname,
			'build/mocks/woocommerce-tracks'
		),
		'~/(.*)': path.resolve(
			__dirname,
			'../../../plugins/woocommerce-admin/client/$1'
		),
		'\\.(jpg|jpeg|png|gif|eot|otf|webp|svg|ttf|woff|woff2|mp4|webm|wav|mp3|m4a|aac|oga)$':
			path.resolve( __dirname, 'build/mocks/static' ),
		'\\.(scss|css)$': path.resolve(
			__dirname,
			'build/mocks/style-mock.js'
		),
		// Force some modulse  to resolve with the CJS entry point, because Jest does not support package.json.exports.
		uuid: require.resolve( 'uuid' ),
		memize: require.resolve( 'memize' ),
	},
	restoreMocks: true,
	setupFiles: [
		path.resolve( __dirname, 'build/setup-window-globals.js' ),
		path.resolve( __dirname, 'build/setup-globals.js' ),
	],
	setupFilesAfterEnv: [
		path.resolve( __dirname, 'build/setup-react-testing-library.js' ),
	],
	testMatch: [
		'**/__tests__/**/*.[jt]s?(x)',
		'**/test/*.[jt]s?(x)',
		'**/?(*.)test.[jt]s?(x)',
	],
	testPathIgnorePatterns: [
		'\\.d\\.ts$', // This regex pattern matches any file that ends with .d.ts
	],
	// The keys for the transformed modules contains the name of the packages that should be transformed.
	transformIgnorePatterns: [
		'node_modules/(?!(?:\\.pnpm|' + Object.keys( transformModules ).join( '|' ) + ')/)',
		__dirname
	],
	// The values for the transformed modules contain an object with the transforms to apply.
	transform: Object.entries( transformModules ).reduce(
		( acc, [ moduleName, transform ] ) => {
			for ( const key in transform ) {
				acc[ `node_modules/${ moduleName }/${ key }` ] =
				transform[ key ];
			}

			return acc;
		},
		{
			'(?:src|client|assets/js)/.*\\.[jt]sx?$': 'ts-jest',
		}
	),
	testEnvironment: 'jest-environment-jsdom',
	timers: 'modern',
	verbose: true,
	cacheDirectory: path.resolve(
		__dirname,
		'../../../node_modules/.cache/jest'
	),
};
