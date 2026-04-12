/**
 * External dependencies
 */
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const path = require( 'path' );
const WebpackRTLPlugin = require( '@automattic/webpack-rtl-plugin' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );
const postcssPlugins = require( '@wordpress/postcss-plugins-preset' );
const StyleAssetPlugin = require( './style-asset-plugin' );

/**
 * Custom plugin to rename .rtl.css files to -rtl.css for WordPress compatibility
 * This is needed because @automattic/webpack-rtl-plugin hardcodes the .rtl.css pattern
 */
class RTLFilenameFixPlugin {
	apply( compiler ) {
		compiler.hooks.compilation.tap(
			'RTLFilenameFixPlugin',
			( compilation ) => {
				compilation.hooks.processAssets.tap(
					{
						name: 'RTLFilenameFixPlugin',
						stage: compiler.webpack.Compilation
							.PROCESS_ASSETS_STAGE_OPTIMIZE_TRANSFER,
					},
					() => {
						for ( const filename of Object.keys(
							compilation.assets
						) ) {
							if ( filename.match( /\.rtl\.css(\?|$)/ ) ) {
								compilation.renameAsset(
									filename,
									filename.replace( '.rtl.css', '-rtl.css' )
								);
							}
						}
					}
				);
			}
		);
	}
}

const NODE_ENV = process.env.NODE_ENV || 'development';

module.exports = {
	plugin: MiniCssExtractPlugin,
	webpackConfig: {
		parser: {
			javascript: {
				exportsPresence: 'error',
			},
		},
		rules: [
			{
				test: /\.s?css$/,
				exclude: [
					/storybook\/wordpress/,
					/build-style\/*\/*.css/,
					/[\/\\](changelog|bin|docs|build|build-module|build-types|build-style|vendor|tests|test)[\/\\]/,
				],
				use: [
					MiniCssExtractPlugin.loader,
					'css-loader',
					{
						loader: 'postcss-loader',
						options: {
							postcssOptions: {
								plugins: postcssPlugins,
							},
						},
					},
					{
						loader: 'sass-loader',
						options: {
							sassOptions: {
								includePaths: [
									path.resolve( __dirname, 'abstracts' ),
								],
							},
							webpackImporter: true,
							additionalData: ( content, loaderContext ) => {
								const { resourcePath } = loaderContext;
								if ( resourcePath.includes( '@automattic+' ) ) {
									/*
									 * Skip adding additional data for @automattic/* packages to
									 * fix "SassError: @use rules must be written before any other rules."
									 * @automattic/* packages have included '@use "sass:math" and other necessary imports.
									 */
									return content;
								}

								return (
									'@use "sass:math";' +
									'@import "_colors"; ' +
									'@import "_variables"; ' +
									'@import "_breakpoints"; ' +
									'@import "_mixins"; ' +
									content
								);
							},
						},
					},
				],
			},
		],
		plugins: [
			new RemoveEmptyScriptsPlugin(),
			new MiniCssExtractPlugin( {
				filename: '[name]/style.css',
				chunkFilename: 'chunks/[id].style.css?ver=[contenthash]',
			} ),
			new WebpackRTLPlugin( {
				minify:
					NODE_ENV === 'development'
						? false
						: {
								preset: [
									'default',
									{
										discardComments: {
											removeAll: true, // Remove all comments
										},
										normalizeWhitespace: true, // Normalize whitespace
									},
								],
						  },
			} ),
			new RTLFilenameFixPlugin(), // Convert .rtl.css to -rtl.css for WordPress compatibility
			new StyleAssetPlugin(),
		],
	},
	StyleAssetPlugin,
};
