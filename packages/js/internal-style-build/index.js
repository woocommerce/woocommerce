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
		compiler.hooks.afterEmit.tap(
			'RTLFilenameFixPlugin',
			( compilation ) => {
				// This runs after assets are emitted, so we use file system operations
				const fs = require( 'fs' );
				const nodePath = require( 'path' );

				// Handle all chunks (including those from entrypoints and standalone chunks)
				compilation.chunks.forEach( ( chunk ) => {
					chunk.files.forEach( ( filename ) => {
						if ( filename.match( /\.rtl\.css(\?|$)/ ) ) {
							// Extract actual filename without query string for file operations
							const actualFilename = filename.split( '?' )[ 0 ];
							const oldPath = nodePath.join(
								compilation.outputOptions.path,
								actualFilename
							);
							const newPath = oldPath.replace(
								'.rtl.css',
								'-rtl.css'
							);

							if ( fs.existsSync( oldPath ) ) {
								try {
									// Copy to new filename
									fs.copyFileSync( oldPath, newPath );
									// Remove old file
									fs.unlinkSync( oldPath );

									// Update compilation records
									const newFilename = filename.replace(
										'.rtl.css',
										'-rtl.css'
									);
									chunk.files.delete( filename );
									chunk.files.add( newFilename );
								} catch ( error ) {
									// eslint-disable-next-line no-console
									console.warn(
										`RTL filename fix failed for ${ filename }:`,
										error.message
									);
								}
							}
						}
					} );
				} );
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
