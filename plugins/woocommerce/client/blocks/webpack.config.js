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
const {
	getUnifiedMainConfig,
	getUnifiedStylingConfig,
} = require( './bin/webpack-config-block-editor-unified-assets.js' );

const interactivityBlocksConfig = require( './bin/webpack-config-interactive-blocks.js' );
const dependencyDetectionConfig = require( './bin/webpack-config-dependency-detection.js' );
const isWatch =
	NODE_ENV === 'development' && process.argv.includes( '--watch' );

const getCacheConfig = ( name, configPaths = [] ) =>
	isWatch || process.env.CI
		? { type: 'memory' }
		: {
				type: 'filesystem',
				// webpack's default name includes the config's position in the
				// exported list, which changes with WC_BLOCKS_BUILD_SHARD.
				name: `${ name }-${ NODE_ENV }`,
				cacheDirectory: path.resolve(
					__dirname,
					`node_modules/.cache/webpack-${ name }`
				),
				buildDependencies: {
					config: [
						__filename,
						path.resolve( __dirname, '../../../../pnpm-lock.yaml' ),
						require.resolve( '@woocommerce/dependency-extraction-webpack-plugin' ),
						require.resolve( '@woocommerce/internal-build/style-build' ),
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
	name: 'cart-and-checkout-frontend',
	cache: getCacheConfig( 'cart-and-checkout-frontend', [] ),
	...getCartAndCheckoutFrontendConfig( { alias: getAlias() } ),
};

// Core config for shared libraries.
const CoreConfig = {
	...sharedConfig,
	name: 'core',
	cache: getCacheConfig( 'core', [] ),
	...getCoreConfig( { alias: getAlias() } ),
};

// Main Blocks config for registering Blocks and for the Editor.
const MainConfig = {
	...sharedConfig,
	name: 'main',
	cache: getCacheConfig( 'main', [] ),
	...getMainConfig( { alias: getAlias() } ),
};

// Unified Blocks config enabled at runtime by a WooCommerce feature flag.
const UnifiedMainConfig = {
	...sharedConfig,
	name: 'unified-main',
	cache: getCacheConfig( 'unified-main', [] ),
	...getUnifiedMainConfig( { alias: getAlias() } ),
};

// Frontend config for scripts used in the store itself.
const FrontendConfig = {
	...sharedConfig,
	name: 'frontend',
	cache: getCacheConfig( 'frontend', [] ),
	...getFrontConfig( { alias: getAlias() } ),
};

/**
 * Config for building experimental extension scripts.
 */
const ExtensionsConfig = {
	...sharedConfig,
	name: 'extensions',
	cache: getCacheConfig( 'extensions', [] ),
	...getExtensionsConfig( { alias: getAlias() } ),
};

/**
 * Config for building the payment methods integration scripts.
 */
const PaymentsConfig = {
	...sharedConfig,
	name: 'payments',
	cache: getCacheConfig( 'payments', [] ),
	...getPaymentsConfig( { alias: getAlias() } ),
};

/**
 * Config to generate the CSS files.
 */
const StylingConfig = {
	...sharedConfig,
	name: 'styling',
	cache: getCacheConfig( 'styling', [] ),
	...getStylingConfig( { alias: getAlias() } ),
};

// Unified editor styles enabled at runtime by a WooCommerce feature flag.
const UnifiedStylingConfig = {
	...sharedConfig,
	name: 'unified-styling',
	cache: getCacheConfig( 'unified-styling', [] ),
	...getUnifiedStylingConfig( { alias: getAlias() } ),
};

// Scripts used exclusively in the Site Editor by the legacy asset path.
const SiteEditorConfig = {
	...sharedConfig,
	name: 'site-editor',
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

/**
 * Config for the dependency detection inline script.
 * This is a standalone IIFE that PHP reads and inlines.
 */
const DependencyDetectionConfig = {
	...sharedConfig,
	name: 'dependency-detection',
	cache: getCacheConfig( 'dependency-detection', [
		'bin/webpack-config-dependency-detection.js',
	] ),
	...dependencyDetectionConfig,
};

const configs = [
	CartAndCheckoutFrontendConfig,
	CoreConfig,
	MainConfig,
	UnifiedMainConfig,
	FrontendConfig,
	ExtensionsConfig,
	PaymentsConfig,
	SiteEditorConfig,
	StylingConfig,
	UnifiedStylingConfig,
	InteractivityBlocksConfig,
	DependencyDetectionConfig,
];

// Relative build cost of each config, used to split them evenly between the
// processes started by bin/webpack-parallel.js. Configs not listed count as 1.
const buildWeights = {
	'unified-styling': 32,
	main: 30,
	'unified-main': 29,
	styling: 27,
	'cart-and-checkout-frontend': 16,
	core: 14,
	'interactivity-blocks-modules': 13,
	frontend: 9,
	extensions: 4,
	payments: 2,
};

/**
 * Returns the configs one process should build when the build is split into
 * `count` processes. Each config goes to the process with the least work so far.
 *
 * @param {number} index Zero-based process index.
 * @param {number} count Number of processes.
 */
const getShard = ( index, count ) => {
	const loads = new Array( count ).fill( 0 );
	const weight = ( config ) => buildWeights[ config.name ] ?? 1;
	return [ ...configs ]
		.sort( ( a, b ) => weight( b ) - weight( a ) )
		.filter( ( config ) => {
			const lightest = loads.indexOf( Math.min( ...loads ) );
			loads[ lightest ] += weight( config );
			return lightest === index;
		} );
};

const shard = process.env.WC_BLOCKS_BUILD_SHARD?.match( /^(\d+)\/(\d+)$/ );
module.exports = shard
	? getShard( Number( shard[ 1 ] ), Number( shard[ 2 ] ) )
	: configs;
