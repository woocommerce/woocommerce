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
	...getCartAndCheckoutFrontendConfig( { alias: getAlias() } ),
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
 * Config to generate the site editor scripts.
 */
const SiteEditorConfig = {
	...sharedConfig,
	...getSiteEditorConfig( { alias: getAlias() } ),
};

const InteractivityBlocksConfig = {
	...sharedConfig,
	...interactivityBlocksConfig,
};

const InteractivityAPIConfig = {
	...sharedConfig,
	...interactivityAPIConfig,
};

/**
 * Config for the dependency detection inline script.
 * This is a standalone IIFE that PHP reads and inlines.
 */
const DependencyDetectionConfig = {
	...sharedConfig,
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
