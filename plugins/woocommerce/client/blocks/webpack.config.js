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

/**
 * External dependencies
 */
const path = require( 'path' );

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
	cache: ( NODE_ENV !== 'development' && { type: 'memory' } ) || {
		type: 'filesystem',
		cacheDirectory: path.resolve(
			__dirname,
			'../../node_modules/.cache/webpack-blocks-cart-and-checkout'
		),
	},
	...getCartAndCheckoutFrontendConfig( { alias: getAlias() } ),
};

// Core config for shared libraries.
const CoreConfig = {
	...sharedConfig,
	cache: ( NODE_ENV !== 'development' && { type: 'memory' } ) || {
		type: 'filesystem',
		cacheDirectory: path.resolve(
			__dirname,
			'../../node_modules/.cache/webpack-blocks-core'
		),
	},
	...getCoreConfig( { alias: getAlias() } ),
};

// Main Blocks config for registering Blocks and for the Editor.
const MainConfig = {
	...sharedConfig,
	cache: ( NODE_ENV !== 'development' && { type: 'memory' } ) || {
		type: 'filesystem',
		cacheDirectory: path.resolve(
			__dirname,
			'../../node_modules/.cache/webpack-blocks-main'
		),
	},
	...getMainConfig( {
		alias: getAlias(),
	} ),
};

// Frontend config for scripts used in the store itself.
const FrontendConfig = {
	...sharedConfig,
	cache: ( NODE_ENV !== 'development' && { type: 'memory' } ) || {
		type: 'filesystem',
		cacheDirectory: path.resolve(
			__dirname,
			'../../node_modules/.cache/webpack-blocks-frontend'
		),
	},
	...getFrontConfig( { alias: getAlias() } ),
};

/**
 * Config for building experimental extension scripts.
 */
const ExtensionsConfig = {
	...sharedConfig,
	cache: ( NODE_ENV !== 'development' && { type: 'memory' } ) || {
		type: 'filesystem',
		cacheDirectory: path.resolve(
			__dirname,
			'../../node_modules/.cache/webpack-blocks-extensions'
		),
	},
	...getExtensionsConfig( { alias: getAlias() } ),
};

/**
 * Config for building the payment methods integration scripts.
 */
const PaymentsConfig = {
	...sharedConfig,
	cache: ( NODE_ENV !== 'development' && { type: 'memory' } ) || {
		type: 'filesystem',
		cacheDirectory: path.resolve(
			__dirname,
			'../../node_modules/.cache/webpack-blocks-payments'
		),
	},
	...getPaymentsConfig( { alias: getAlias() } ),
};

/**
 * Config to generate the CSS files.
 */
const StylingConfig = {
	...sharedConfig,
	cache: ( NODE_ENV !== 'development' && { type: 'memory' } ) || {
		type: 'filesystem',
		cacheDirectory: path.resolve(
			__dirname,
			'../../node_modules/.cache/webpack-blocks-styling'
		),
	},
	...getStylingConfig( { alias: getAlias() } ),
};

/**
 * Config to generate the site editor scripts.
 */
const SiteEditorConfig = {
	...sharedConfig,
	cache: ( NODE_ENV !== 'development' && { type: 'memory' } ) || {
		type: 'filesystem',
		cacheDirectory: path.resolve(
			__dirname,
			'../../node_modules/.cache/webpack-blocks-site-editor'
		),
	},
	...getSiteEditorConfig( { alias: getAlias() } ),
};

const InteractivityBlocksConfig = {
	...sharedConfig,
	cache: ( NODE_ENV !== 'development' && { type: 'memory' } ) || {
		type: 'filesystem',
		cacheDirectory: path.resolve(
			__dirname,
			'../../node_modules/.cache/webpack-blocks-interactivity'
		),
	},
	...interactivityBlocksConfig,
};

const InteractivityAPIConfig = {
	...sharedConfig,
	cache: ( NODE_ENV !== 'development' && { type: 'memory' } ) || {
		type: 'filesystem',
		cacheDirectory: path.resolve(
			__dirname,
			'../../node_modules/.cache/webpack-api-interactivity'
		),
	},
	...interactivityAPIConfig,
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
];
