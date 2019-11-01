/**
 * External dependencies
 */
const path = require( 'path' );
const { kebabCase } = require( 'lodash' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );
const ProgressBarPlugin = require( 'progress-bar-webpack-plugin' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const chalk = require( 'chalk' );
const NODE_ENV = process.env.NODE_ENV || 'development';
const FallbackModuleDirectoryPlugin = require( './bin/fallback-module-directory-webpack-plugin' );
const {
	getAlias,
	getMainConfig,
	getFrontConfig,
} = require( './bin/webpack-helpers.js' );

const baseConfig = {
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
};

const CoreConfig = {
	...baseConfig,
	entry: {
		wcBlocksRegistry: './assets/js/blocks-registry/index.js',
		wcSettings: './assets/js/settings/shared/index.js',
		wcBlocksData: './assets/js/data/index.js',
	},
	output: {
		filename: ( chunkData ) => {
			return `${ kebabCase( chunkData.chunk.name ) }.js`;
		},
		path: path.resolve( __dirname, './build/' ),
		library: [ 'wc', '[name]' ],
		libraryTarget: 'this',
		// This fixes an issue with multiple webpack projects using chunking
		// overwriting each other's chunk loader function.
		// See https://webpack.js.org/configuration/output/#outputjsonpfunction
		jsonpFunction: 'webpackWcBlocksJsonp',
	},
	module: {
		rules: [
			{
				test: /\.jsx?$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader?cacheDirectory',
					options: {
						presets: [ '@wordpress/babel-preset-default' ],
					},
				},
			},
		],
	},
	plugins: [
		new CleanWebpackPlugin(),
		new ProgressBarPlugin( {
			format:
				chalk.blue( 'Build core script' ) +
				' [:bar] ' +
				chalk.green( ':percent' ) +
				' :msg (:elapsed seconds)',
		} ),
		new DependencyExtractionWebpackPlugin( { injectPolyfill: true } ),
	],
};

const GutenbergBlocksConfig = {
	...baseConfig,
	...getMainConfig( { alias: getAlias() } ),
};

const BlocksFrontendConfig = {
	...baseConfig,
	...getFrontConfig( { alias: getAlias() } ),
};

/**
 * Currently Legacy Configs are for builds targeting < WP5.3
 */

// eslint-disable-next-line no-unused-vars
const LegacyBlocksConfig = {
	...baseConfig,
	...getMainConfig( {
		fileSuffix: 'legacy',
		resolvePlugins: [
			new FallbackModuleDirectoryPlugin(
				'/legacy/',
				'/',
				getAlias( { pathPart: 'legacy' } )
			),
		],
		exclude: [ 'all-products', 'price-filter' ],
	} ),
};

// eslint-disable-next-line no-unused-vars
const LegacyFrontendBlocksConfig = {
	...baseConfig,
	...getFrontConfig( {
		fileSuffix: 'legacy',
		resolvePlugins: [
			new FallbackModuleDirectoryPlugin(
				'/legacy/',
				'/',
				getAlias( { pathPart: 'legacy' } )
			),
		],
		exclude: [ 'all-products', 'price-filter' ],
	} ),
};

module.exports = [
	CoreConfig,
	GutenbergBlocksConfig,
	BlocksFrontendConfig,
	LegacyBlocksConfig,
	LegacyFrontendBlocksConfig,
];
