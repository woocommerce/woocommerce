/** @format */
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
	'@wordpress/editor': { this: [ 'wp', 'editor' ] },
	'@wordpress/element': { this: [ 'wp', 'element' ] },
	'@wordpress/i18n': { this: [ 'wp', 'i18n' ] },
};

/**
 * Config for compiling Gutenberg blocks JS.
 */
const GutenbergBlocksConfig = {
	mode: NODE_ENV,
	entry: {
		'products-block': './assets/js/products-block.jsx',
		// 'next-block-name': './assets/js/gutenberg/some-other-block.jsx', <-- How to add more gutenblocks to this.
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
					'sass-loader',
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
