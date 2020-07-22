/**
 * Internal dependencies
 */
const FallbackModuleDirectoryPlugin = require( './bin/fallback-module-directory-webpack-plugin' );
const { NODE_ENV, FORCE_MAP, getAlias } = require( './bin/webpack-helpers.js' );
const {
	getCoreConfig,
	getMainConfig,
	getFrontConfig,
	getPaymentsConfig,
	getStylingConfig,
} = require( './bin/webpack-configs.js' );

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
	devtool: NODE_ENV === 'development' || FORCE_MAP ? 'source-map' : false,
};

// Core config for shared libraries.
const CoreConfig = {
	...sharedConfig,
	...getCoreConfig( { alias: getAlias() } ),
};

// Main Blocks config for registering Blocks and for the Editor.
const MainConfig = {
	...sharedConfig,
	...getMainConfig( {
		alias: getAlias(),
	} ),
};

// Frontend config for scripts used in the store itself.
const FrontendConfig = {
	...sharedConfig,
	...getFrontConfig( { alias: getAlias() } ),
};

/**
 * This is a temporary config for building the payment methods integration script until it can be
 * moved into the payment extension(s).
 */
const PaymentsConfig = {
	...sharedConfig,
	...getPaymentsConfig( { alias: getAlias() } ),
};

/**
 * Config to generate the CSS files.
 */
const StylingConfig = {
	...sharedConfig,
	...getStylingConfig( { alias: getAlias() } ),
};

/**
 * Legacy Configs are for builds targeting < WP5.3 and handle backwards compatibility and disabling
 * unsupported features.
 */
const LegacyMainConfig = {
	...sharedConfig,
	...getMainConfig( {
		fileSuffix: 'legacy',
		resolvePlugins: [
			new FallbackModuleDirectoryPlugin(
				'/legacy/',
				'/',
				getAlias( { pathPart: 'legacy' } )
			),
		],
		exclude: [
			'all-products',
			'price-filter',
			'attribute-filter',
			'active-filters',
			'checkout',
			'cart',
			'single-product',
		],
	} ),
};

const LegacyFrontendConfig = {
	...sharedConfig,
	...getFrontConfig( {
		fileSuffix: 'legacy',
		resolvePlugins: [
			new FallbackModuleDirectoryPlugin(
				'/legacy/',
				'/',
				getAlias( { pathPart: 'legacy' } )
			),
		],
		exclude: [
			'all-products',
			'price-filter',
			'attribute-filter',
			'active-filters',
			'checkout',
			'cart',
			'single-product',
		],
	} ),
};

const LegacyStylingConfig = {
	...sharedConfig,
	...getStylingConfig( {
		fileSuffix: 'legacy',
		exclude: [
			'all-products',
			'price-filter',
			'attribute-filter',
			'active-filters',
			'checkout',
			'cart',
			'single-product',
		],
	} ),
};

module.exports = [
	CoreConfig,
	MainConfig,
	FrontendConfig,
	PaymentsConfig,
	StylingConfig,
	LegacyMainConfig,
	LegacyFrontendConfig,
	LegacyStylingConfig,
];
