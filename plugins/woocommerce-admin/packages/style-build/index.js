/**
 * External dependencies
 */
const MiniCssExtractPlugin = require( '@automattic/mini-css-extract-plugin-with-rtl' );
const path = require( 'path' );
const WebpackRTLPlugin = require( 'webpack-rtl-plugin' );
const FixStyleOnlyEntriesPlugin = require( 'webpack-fix-style-only-entries' );
const postcssPlugins = require( '@wordpress/postcss-plugins-preset' );

const NODE_ENV = process.env.NODE_ENV || 'development';

module.exports = {
	webpackConfig: {
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
							ident: 'postcss',
							plugins: postcssPlugins,
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
							additionalData:
								'@use "sass:math";' +
								'@import "_colors"; ' +
								'@import "_variables"; ' +
								'@import "_breakpoints"; ' +
								'@import "_mixins"; ',
						},
					},
				],
			},
		],
		plugins: [
			new FixStyleOnlyEntriesPlugin(),
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
