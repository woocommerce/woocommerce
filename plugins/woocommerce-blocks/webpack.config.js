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
	getExtensionsConfig,
	getStylingConfig,
	getCoreEditorConfig,
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

// Core config for shared libraries to be run inside the editor.
const CoreEditorConfig = {
	...sharedConfig,
	...getCoreEditorConfig( { alias: getAlias() } ),
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
 * Config for building experimental extension scripts.
 */
const ExtensionsConfig = {
	...sharedConfig,
	...getExtensionsConfig( { alias: getAlias() } ),
};

/**
 * Config for building the payment methods integration scripts.
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
 *
 * Now that WordPress 5.5 is released, as of WooCommerce Blocks 3.2.0 we don't support WP <5.3,
 * so these legacy builds are not used. Keeping the config so we can conveniently reinstate
 * these builds as needed (hence eslint-disable).
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

/**
 * Now that WordPress 5.5 is released, as of WooCommerce Blocks 3.2.0 we don't support WP <5.3,
 * so the legacy builds are not used. Keeping the config so we can conveniently reinstate
 * these builds as needed (hence eslint-disable).
 */
// eslint-disable-next-line no-unused-vars
const legacyConfigs = [
	LegacyMainConfig,
	LegacyFrontendConfig,
	LegacyStylingConfig,
];

module.exports = [
	CoreConfig,
	MainConfig,
	FrontendConfig,
	ExtensionsConfig,
	PaymentsConfig,
	StylingConfig,
	CoreEditorConfig,
];
