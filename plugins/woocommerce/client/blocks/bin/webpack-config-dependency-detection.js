/**
 * Webpack config for WooCommerce Dependency Detection script.
 *
 * This builds a standalone IIFE that PHP inlines into the page.
 * Unlike other blocks scripts, this has no external dependencies
 * and outputs a plain script without module exports.
 */

const path = require( 'path' );
const ProgressBarPlugin = require( 'progress-bar-webpack-plugin' );
const TerserPlugin = require( 'terser-webpack-plugin' );

/**
 * Internal dependencies
 */
const { getProgressBarPluginConfig, NODE_ENV } = require( './webpack-helpers' );

const ROOT_DIR = path.resolve( __dirname, '../../../../../' );
// Output to the standard blocks build directory (gitignored).
const BUILD_DIR = path.resolve( __dirname, '../build/' );
const BABEL_CACHE_DIR = path.join(
	ROOT_DIR,
	'node_modules/.cache/babel-loader'
);
const isProduction = NODE_ENV === 'production';

module.exports = {
	entry: {
		'dependency-detection':
			'./assets/js/dependency-detection/index.js',
	},
	output: {
		path: BUILD_DIR,
		filename: '[name].js',
		// IIFE - no module exports or library wrapping.
		iife: true,
		clean: false,
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [
							[
								'@wordpress/babel-preset-default',
								{
									modules: false,
									targets: {
										browsers: [
											'extends @wordpress/browserslist-config',
										],
									},
								},
							],
						],
						cacheDirectory: BABEL_CACHE_DIR,
						cacheCompression: false,
					},
				},
			},
		],
	},
	plugins: [
		new ProgressBarPlugin(
			getProgressBarPluginConfig( 'Dependency Detection' )
		),
		// Note: We intentionally do NOT define __WC_GLOBAL_EXPORTS_PLACEHOLDER__ here.
		// It must remain as an undefined reference that PHP will replace with the actual
		// JSON mapping at runtime. Defining it would cause Terser to inline it.
	],
	optimization: {
		// Always minimize - this is an inline script embedded in page HTML.
		minimize: true,
		minimizer: [
			new TerserPlugin( {
				// Don't extract license comments to a separate file.
				extractComments: false,
				terserOptions: {
					format: {
						// Remove all comments from output.
						comments: false,
					},
					compress: {
						// Don't inline variables - we need WC_GLOBAL_EXPORTS to remain
						// as a variable assignment for PHP placeholder replacement.
						reduce_vars: false,
						inline: false,
					},
					mangle: {
						// Don't mangle top-level names.
						toplevel: false,
					},
				},
			} ),
		],
	},
	// No source maps for inline script.
	devtool: false,
	// No externals - this is a standalone script.
	externals: {},
	resolve: {
		extensions: [ '.js' ],
	},
};
