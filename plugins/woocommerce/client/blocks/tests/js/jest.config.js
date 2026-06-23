const path = require( 'path' );
const {
	withWordPressDependencyCompat,
} = require( '@woocommerce/jest-wordpress-version-compat' );

const rootDir = path.resolve( __dirname, '../../' );
const missingWpVersionMessage =
	'WP_VERSION is not set. This test run is using the installed @wordpress packages and may not rely on a validated WordPress package environment. Set WP_VERSION=latest, WP_VERSION=latest-1, or WP_VERSION=gutenberg to run WordPress package compatibility tests.';
const isGutenbergCompatRun = process.env.WP_VERSION === 'gutenberg';
const defaultTransformIgnorePattern =
	'/node_modules/(?!\\.pnpm/dinero\\.js|dinero\\.js)';
const gutenbergTransformedPackages = [ 'uuid', '@arraypress/waveform-player' ];
const gutenbergTransformIgnorePattern = [
	'/node_modules/(?!(?:',
	[
		...gutenbergTransformedPackages.map(
			( packageName ) =>
				`\\.cache/jest-wordpress-version-compat/gutenberg/node_modules/${ packageName }`
		),
		'\\.pnpm/dinero\\.js',
		'dinero\\.js',
		...gutenbergTransformedPackages,
	].join( '|' ),
	'))',
].join( '' );

/**
 * WordPress packages that must resolve to a single instance across the test
 * environment. pnpm 10 isolates transitive deps more strictly than pnpm 9,
 * creating multiple copies of packages that maintain global singleton state
 * (private-APIs lock/unlock, data registries, blocks registry). Forcing them
 * to the workspace copy via `require.resolve` keeps them in sync.
 *
 * Pattern follows @woocommerce/internal-js-tests' `mapWpModules` approach.
 */
const singletonWpModules = [
	'@wordpress/private-apis',
	'@wordpress/block-editor',
	'@wordpress/blocks',
	'@wordpress/components',
	'@wordpress/core-data',
	'@wordpress/data',
	'@wordpress/editor',
	'@wordpress/html-entities',
	'@wordpress/keyboard-shortcuts',
	'@wordpress/patterns',
	'@wordpress/rich-text',
	'@wordpress/notices',
];

// Compatibility runs need every mapped WordPress package and its singleton
// dependencies to come from the selected compatibility cache.
const wpSingletonMapper = process.env.WP_VERSION
	? {}
	: singletonWpModules.reduce( ( acc, mod ) => {
			try {
				acc[ `^${ mod }$` ] = require.resolve( mod );
			} catch ( e ) {
				// Not a direct dep — skip.
			}
			return acc;
	  }, {} );

const config = {
	rootDir,
	collectCoverageFrom: [
		'assets/js/**/*.js',
		'!**/node_modules/**',
		'!**/vendor/**',
		'!**/test/**',
	],
	moduleDirectories: [ 'node_modules' ],
	moduleNameMapper: {
		'\\.(jpg|jpeg|png|gif|eot|otf|webp|svg|ttf|woff|woff2)$':
			'<rootDir>/tests/js/config/file-mock.js',
		'^client-zip$': '<rootDir>/tests/js/config/client-zip-mock.js',

		// WordPress singleton modules — bare specifiers only; sub-path
		// imports (e.g. @wordpress/data/build/foo) fall through to normal
		// resolution so they pick up the same physical copy.
		...wpSingletonMapper,
		// core-data sub-path redirects (pre-existing)
		'@wordpress/core-data/build/(.*)$':
			'<rootDir>/node_modules/@wordpress/core-data/build/$1',

		'@woocommerce/atomic-blocks': 'assets/js/atomic/blocks',
		'@woocommerce/atomic-utils': 'assets/js/atomic/utils',
		'@woocommerce/icons': 'assets/js/icons',
		'@woocommerce/settings': 'assets/js/settings/shared',
		'@woocommerce/blocks/(.*)$': 'assets/js/blocks/$1',
		'@woocommerce/block-settings': 'assets/js/settings/blocks',
		'@woocommerce/editor-components(.*)$': 'assets/js/editor-components/$1',
		'@woocommerce/blocks-registry': 'assets/js/blocks-registry',
		'@woocommerce/blocks-checkout$': 'packages/checkout',
		'@woocommerce/blocks-checkout-events': 'assets/js/events',
		'@woocommerce/blocks-components': 'packages/components',
		'@woocommerce/price-format': 'packages/prices',
		'@woocommerce/block-hocs(.*)$': 'assets/js/hocs/$1',
		'@woocommerce/base-components(.*)$': 'assets/js/base/components/$1',
		'@woocommerce/base-context(.*)$': 'assets/js/base/context/$1',
		'@woocommerce/base-hocs(.*)$': 'assets/js/base/hocs/$1',
		'@woocommerce/base-hooks(.*)$': 'assets/js/base/hooks/$1',
		'@woocommerce/base-utils(.*)$': 'assets/js/base/utils',
		'@woocommerce/block-data': 'assets/js/data',
		'@woocommerce/resource-previews': 'assets/js/previews',
		'@woocommerce/shared-context': 'assets/js/shared/context',
		'@woocommerce/shared-hocs': 'assets/js/shared/hocs',
		'@woocommerce/blocks-test-utils/(.*)$': 'tests/utils/$1',
		'@woocommerce/blocks-test-utils': 'tests/utils',
		'@woocommerce/types': 'assets/js/types',
		'@woocommerce/utils': 'assets/js/utils',
		'@woocommerce/test-utils/msw': 'tests/js/config/msw-setup.js',
		'@woocommerce/entities': 'assets/js/entities',
		'@woocommerce/stores/(.*)$': 'assets/js/base/stores/$1',
		...( process.env.WP_VERSION
			? {}
			: {
					'^react$': '<rootDir>/node_modules/react',
					'^react-dom$': '<rootDir>/node_modules/react-dom',
			  } ),
		// Catch-all for monorepo @woocommerce/* packages: route bare and
		// subpath imports through source so tests don't depend on built
		// artifacts. Must come after all blocks-internal aliases above and
		// before the generic build-module rewrite so @woocommerce/* subpaths
		// land on src/ instead of build/.
		'^@woocommerce/([^/]+)/(?:src|build|build-module|build-types)/(.+)$':
			'<rootDir>/../../../../packages/js/$1/src/$2',
		'^@woocommerce/([^/]+)/(.+)$':
			'<rootDir>/../../../../packages/js/$1/src/$2',
		'^@woocommerce/([^/]+)$': '<rootDir>/../../../../packages/js/$1/src',
		'^(.+)/build-module/(.*)$': '$1/build/$2',
	},
	preset: '@wordpress/jest-preset-default',
	setupFiles: [ '<rootDir>/tests/js/config/global-mocks.js' ],
	setupFilesAfterEnv: [
		'<rootDir>/tests/js/config/testing-library.js',
		'<rootDir>/tests/js/config/msw-setup.js',
	],
	testPathIgnorePatterns: [
		'<rootDir>/bin/',
		'<rootDir>/build/',
		'<rootDir>/docs/',
		'<rootDir>/node_modules/',
		'<rootDir>/vendor/',
		'<rootDir>/tests/',
	],
	roots: [ '<rootDir>', '<rootDir>/../legacy/js' ],
	resolver: '<rootDir>/tests/js/scripts/resolver.js',
	transform: {
		...( isGutenbergCompatRun
			? Object.fromEntries(
					gutenbergTransformedPackages.map( ( packageName ) => [
						`node_modules/${ packageName }/(.+)`,
						'<rootDir>/tests/js/scripts/babel-transformer.js',
					] )
			  )
			: {} ),
		'^.+\\.(js|ts|tsx)$': '<rootDir>/tests/js/scripts/babel-transformer.js',
	},
	transformIgnorePatterns: [
		isGutenbergCompatRun
			? gutenbergTransformIgnorePattern
			: defaultTransformIgnorePattern,
	],
	verbose: true,
	cacheDirectory: '<rootDir>/../../node_modules/.cache/jest',
	testEnvironment: 'jest-fixed-jsdom',
};

// TODO: Migrate this custom Jest config to @woocommerce/internal-js-tests/jest-preset.js.
if ( process.env.WP_VERSION ) {
	module.exports = withWordPressDependencyCompat( config, {
		cwd: rootDir,
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
