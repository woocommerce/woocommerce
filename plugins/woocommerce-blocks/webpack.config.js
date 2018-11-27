/**
 * External dependencies
 */
const path = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const CleanWebpackPlugin = require( 'clean-webpack-plugin' );
const NODE_ENV = process.env.NODE_ENV || 'development';

const externals = {
	// We can add @woocommerce packages here when wc-admin merges into wc core,
	// for now we need to fetch those from npm.
	'@wordpress/api-fetch': { this: [ 'wp', 'apiFetch' ] },
	'@wordpress/blocks': { this: [ 'wp', 'blocks' ] },
	'@wordpress/components': { this: [ 'wp', 'components' ] },
	'@wordpress/compose': { this: [ 'wp', 'compose' ] },
	'@wordpress/data': { this: [ 'wp', 'data' ] },
	'@wordpress/element': { this: [ 'wp', 'element' ] },
	'@wordpress/editor': { this: [ 'wp', 'editor' ] },
	'@wordpress/i18n': { this: [ 'wp', 'i18n' ] },
	'@wordpress/url': { this: [ 'wp', 'url' ] },
	lodash: 'lodash',
};

/**
 * Config for compiling Gutenberg blocks JS.
 */
const GutenbergBlocksConfig = {
	mode: NODE_ENV,
	entry: {
		// Legacy block
		'products-block': './assets/js/legacy/products-block.jsx',
		// New blocks
		'product-category-block': './assets/js/product-category-block.js',
	},
	output: {
		path: path.resolve( __dirname, './build/' ),
		filename: '[name].js',
		libraryTarget: 'this',
	},
	externals,
	module: {
		rules: [
			{
				test: /\.jsx?$/,
				exclude: /node_modules/,
				loader: 'babel-loader',
			},
			{
				test: /\.s[c|a]ss$/,
				use: [
					'style-loader',
					MiniCssExtractPlugin.loader,
					'css-loader',
					'postcss-loader',
					{
						loader: 'sass-loader',
						query: {
							includePaths: [ 'assets/css/abstracts' ],
							data:
								'@import "_colors"; ' +
								'@import "_breakpoints"; ' +
								'@import "_mixins"; ',
						},
					},
				],
			},
		],
	},
	plugins: [
		new CleanWebpackPlugin( 'build', {} ),
		new MiniCssExtractPlugin( {
			filename: '[name].css',
		} ),
	],
};

module.exports = [ GutenbergBlocksConfig ];
