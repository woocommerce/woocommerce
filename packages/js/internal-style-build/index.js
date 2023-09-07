/**
 * External dependencies
 */
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const path = require( 'path' );
const WebpackRTLPlugin = require( 'webpack-rtl-plugin' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );
const postcssPlugins = require( '@wordpress/postcss-plugins-preset' );

const NODE_ENV = process.env.NODE_ENV || 'development';

module.exports = {
	webpackConfig: {
		parser: {
			javascript: {
				exportsPresence: 'error',
			},
		},
		rules: [
			{
				test: /\.s?css$/,
				exclude: [ /storybook\/wordpress/, /build-style\/*\/*.css/ ],
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
				chunkFilename: 'chunks/[id].style.css',
			} ),
			new WebpackRTLPlugin( {
				filename: '[name]/style-rtl.css',
				minify: NODE_ENV === 'development' ? false : { safe: true },
			} ),
		],
	},
};
