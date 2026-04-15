/**
 * External dependencies
 */
const path = require( 'path' );

/**
 * Internal dependencies
 */
const { NODE_ENV, getAlias } = require( './bin/webpack-helpers.js' );
const {
	getCoreConfig,
	getMainConfig,
	getFrontConfig,
	getPaymentsConfig,
	getExtensionsConfig,
	getSiteEditorConfig,
	getStylingConfig,
	getCartAndCheckoutFrontendConfig,
} = require( './bin/webpack-configs.js' );

const interactivityBlocksConfig = require( './bin/webpack-config-interactive-blocks.js' );
const interactivityAPIConfig = require( './bin/webpack-config-interactivity.js' );
const dependencyDetectionConfig = require( './bin/webpack-config-dependency-detection.js' );
const isWatch =
	NODE_ENV === 'development' && process.argv.includes( '--watch' );

const getCacheConfig = ( name, configPaths = [] ) =>
	isWatch || process.env.CI
		? { type: 'memory' }
		: {
				type: 'filesystem',
				cacheDirectory: path.resolve(
					__dirname,
					`node_modules/.cache/webpack-${ name }`
				),
				buildDependencies: {
					config: [
						__filename,
						path.resolve( __dirname, 'bin/webpack-configs.js' ),
						path.resolve( __dirname, 'bin/webpack-helpers.js' ),
						require.resolve(
							'@woocommerce/dependency-extraction-webpack-plugin/src/index'
						),
						require.resolve(
							'@woocommerce/internal-style-build/webpack-rtl-plugin'
						),
						...configPaths.map( ( configPath ) =>
							path.resolve( __dirname, configPath )
						),
					],
				},
		  };

// Only options shared between all configs should be defined here.
const sharedConfig = {
	mode: NODE_ENV,
	performance: {
		hints: false,
	},
	stats: {
		all: false,
		assets: true,
		builtAt: true,
		colors: true,
		errors: true,
		hash: true,
		timings: true,
	},
	watchOptions: {
		ignored: /node_modules/,
	},
	devtool: NODE_ENV === 'development' ? 'source-map' : false,
};

const CartAndCheckoutFrontendConfig = {
	...sharedConfig,
	cache: getCacheConfig( 'cart-and-checkout-frontend', [] ),
	...getCartAndCheckoutFrontendConfig( { alias: getAlias() } ),
};

// Core config for shared libraries.
const CoreConfig = {
	...sharedConfig,
	cache: getCacheConfig( 'core', [] ),
	...getCoreConfig( { alias: getAlias() } ),
};

// Main Blocks config for registering Blocks and for the Editor.
const MainConfig = {
	...sharedConfig,
	cache: getCacheConfig( 'main', [] ),
	...getMainConfig( { alias: getAlias() } ),
};

// Frontend config for scripts used in the store itself.
const FrontendConfig = {
	...sharedConfig,
	cache: getCacheConfig( 'frontend', [] ),
	...getFrontConfig( { alias: getAlias() } ),
};

/**
 * Config for building experimental extension scripts.
 */
const ExtensionsConfig = {
	...sharedConfig,
	cache: getCacheConfig( 'extensions', [] ),
	...getExtensionsConfig( { alias: getAlias() } ),
};

/**
 * Config for building the payment methods integration scripts.
 */
const PaymentsConfig = {
	...sharedConfig,
	cache: getCacheConfig( 'payments', [] ),
	...getPaymentsConfig( { alias: getAlias() } ),
};

/**
 * Config to generate the CSS files.
 */
const StylingConfig = {
	...sharedConfig,
	cache: getCacheConfig( 'styling', [] ),
	...getStylingConfig( { alias: getAlias() } ),
};

/**
 * Config to generate the site editor scripts.
 */
const SiteEditorConfig = {
	...sharedConfig,
	cache: getCacheConfig( 'site-editor', [] ),
	...getSiteEditorConfig( { alias: getAlias() } ),
};

const InteractivityBlocksConfig = {
	...sharedConfig,
	cache: getCacheConfig( 'interactivity-blocks', [
		'bin/webpack-config-interactive-blocks.js',
	] ),
	...interactivityBlocksConfig,
};

const InteractivityAPIConfig = {
	...sharedConfig,
	cache: getCacheConfig( 'interactivity-api', [
		'bin/webpack-config-interactivity.js',
	] ),
	...interactivityAPIConfig,
};

/**
 * Config for the dependency detection inline script.
 * This is a standalone IIFE that PHP reads and inlines.
 */
const DependencyDetectionConfig = {
	...sharedConfig,
	cache: getCacheConfig( 'dependency-detection', [
		'bin/webpack-config-dependency-detection.js',
	] ),
	...dependencyDetectionConfig,
};

module.exports = [
	CartAndCheckoutFrontendConfig,
	CoreConfig,
	MainConfig,
	FrontendConfig,
	ExtensionsConfig,
	PaymentsConfig,
	SiteEditorConfig,
	StylingConfig,
	InteractivityBlocksConfig,
	InteractivityAPIConfig,
	DependencyDetectionConfig,
];
