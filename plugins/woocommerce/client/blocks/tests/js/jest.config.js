const path = require( 'path' );

const rootDir = path.resolve( __dirname, '../../' );

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
];

const wpSingletonMapper = singletonWpModules.reduce( ( acc, mod ) => {
	try {
		acc[ `^${ mod }$` ] = require.resolve( mod );
	} catch ( e ) {
		// Not a direct dep — skip.
	}
	return acc;
}, {} );

module.exports = {
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

		// WordPress singleton modules — bare specifiers only; sub-path
		// imports (e.g. @wordpress/data/build/foo) fall through to normal
		// resolution so they pick up the same physical copy.
		...wpSingletonMapper,
		// core-data sub-path redirects (pre-existing)
		'@wordpress/core-data/build/(.*)$':
			'<rootDir>/node_modules/@wordpress/core-data/build/$1',

		// WooCommerce workspace aliases
		'@woocommerce/data': '<rootDir>/node_modules/@woocommerce/data/build',
		'@woocommerce/sanitize':
			'<rootDir>/node_modules/@woocommerce/sanitize/src',
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
		'^react$': '<rootDir>/node_modules/react',
		'^react-dom$': '<rootDir>/node_modules/react-dom',
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
		'^.+\\.(js|ts|tsx)$': '<rootDir>/tests/js/scripts/babel-transformer.js',
	},
	transformIgnorePatterns: [
		'/node_modules/(?!\\.pnpm/dinero\\.js|dinero\\.js)',
	],
	verbose: true,
	cacheDirectory: '<rootDir>/../../node_modules/.cache/jest',
	testEnvironment: 'jest-fixed-jsdom',
};
