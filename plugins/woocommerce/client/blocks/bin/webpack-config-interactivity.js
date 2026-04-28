/**
 * External dependencies
 */
const path = require( 'path' );
const { DefinePlugin } = require( 'webpack' );

/**
 * Internal dependencies
 */
const DependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );
const FilesystemCacheWarningsPlugin = require( './filesystem-cache-warnings-webpack-plugin.js' );
const { sharedOptimizationConfig } = require( './webpack-shared-config' );

const { NODE_ENV: mode = 'development' } = process.env;
const ROOT_DIR = path.resolve( __dirname, '../../../../../' );
const BABEL_CACHE_DIR = path.join(
	ROOT_DIR,
	'node_modules/.cache/babel-loader'
);

// Config to build and incubate the interactivity API within WooCommerce.
module.exports = {
	entry: {
		'@wordpress/interactivity': path.resolve(
			__dirname,
			'..',
			'node_modules/@wordpress/interactivity/src/index.ts'
		),

		'@wordpress/interactivity-router': path.resolve(
			__dirname,
			'..',
			'node_modules/@wordpress/interactivity-router/src/index.ts'
		),
	},
	optimization: sharedOptimizationConfig,
	name: 'interactivity-api',
	experiments: {
		outputModule: true,
	},
	output: {
		devtoolNamespace: 'wc',
		filename: '[name].js',
		library: {
			type: 'module',
		},
		path: path.resolve( __dirname, '../build/' ),
		asyncChunks: false,
		chunkFormat: 'module',
		environment: { module: true },
		module: true,
	},
	resolve: {
		extensions: [ '.js', '.ts', '.tsx' ],
	},
	plugins: [
		new DependencyExtractionWebpackPlugin( {
			combineAssets: true,
			combinedOutputFile: './interactivity-api-assets.php',
		} ),
		new DefinePlugin( {
			'globalThis.SCRIPT_DEBUG': JSON.stringify( mode === 'development' ),
		} ),
		// Suppress file system cache warnings (unsupported serialization related).
		new FilesystemCacheWarningsPlugin(),
	],
	module: {
		rules: [
			{
				test: /\.[jt]sx?$/,
				use: [
					{
						loader: require.resolve( 'babel-loader' ),
						options: {
							presets: [
								require.resolve(
									'@wordpress/babel-preset-default'
								),
							],
							cacheDirectory: BABEL_CACHE_DIR,
							cacheCompression: false,
						},
					},
				],
			},
		],
	},
};
