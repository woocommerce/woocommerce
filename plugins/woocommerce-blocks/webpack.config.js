/**
 * External dependencies
 */
const path = require( 'path' );
const MergeExtractFilesPlugin = require( './bin/merge-extract-files-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );
const ProgressBarPlugin = require( 'progress-bar-webpack-plugin' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const chalk = require( 'chalk' );
const NODE_ENV = process.env.NODE_ENV || 'development';

function findModuleMatch( module, match ) {
	if ( module.request && match.test( module.request ) ) {
		return true;
	} else if ( module.issuer ) {
		return findModuleMatch( module.issuer, match );
	}
	return false;
}

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
	resolve: {
		alias: {
			'@woocommerce/settings': path.resolve( __dirname, 'assets/js/settings/index.js' ),
		},
	},
};

/**
 * Config for compiling Gutenberg blocks JS.
 */
const GutenbergBlocksConfig = {
	...baseConfig,
	entry: {
		// Shared blocks code
		blocks: './assets/js/index.js',
		// Blocks
		'handpicked-products': './assets/js/blocks/handpicked-products/index.js',
		'product-best-sellers': './assets/js/blocks/product-best-sellers/index.js',
		'product-category': './assets/js/blocks/product-category/index.js',
		'product-categories': './assets/js/blocks/product-categories/index.js',
		'product-new': './assets/js/blocks/product-new/index.js',
		'product-on-sale': './assets/js/blocks/product-on-sale/index.js',
		'product-top-rated': './assets/js/blocks/product-top-rated/index.js',
		'products-by-attribute': './assets/js/blocks/products-by-attribute/index.js',
		'featured-product': './assets/js/blocks/featured-product/index.js',
		'reviews-by-product': './assets/js/blocks/reviews/reviews-by-product/index.js',
		'reviews-by-category': './assets/js/blocks/reviews/reviews-by-category/index.js',
		'product-search': './assets/js/blocks/product-search/index.js',
		'product-tag': './assets/js/blocks/product-tag/index.js',
		'featured-category': './assets/js/blocks/featured-category/index.js',
	},
	output: {
		path: path.resolve( __dirname, './build/' ),
		filename: '[name].js',
		library: [ 'wc', 'blocks', '[name]' ],
		libraryTarget: 'this',
		// This fixes an issue with multiple webpack projects using chunking
		// overwriting each other's chunk loader function.
		// See https://webpack.js.org/configuration/output/#outputjsonpfunction
		jsonpFunction: 'webpackWcBlocksJsonp',
	},
	optimization: {
		splitChunks: {
			cacheGroups: {
				commons: {
					test: /[\\/]node_modules[\\/]/,
					name: 'vendors',
					chunks: 'all',
					enforce: true,
				},
				editor: {
					// Capture all `editor` stylesheets and the components stylesheets.
					test: ( module = {} ) =>
						module.constructor.name === 'CssModule' &&
						( findModuleMatch( module, /editor\.scss$/ ) ||
							findModuleMatch( module, /[\\/]assets[\\/]components[\\/]/ ) ),
					name: 'editor',
					chunks: 'all',
					priority: 10,
				},
				style: {
					test: /style\.scss$/,
					name: 'style',
					chunks: 'all',
					priority: 5,
				},
			},
		},
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
						plugins: [
							NODE_ENV === 'production' ? require.resolve( 'babel-plugin-transform-react-remove-prop-types' ) : false,
						].filter( Boolean ),
					},
				},
			},
			{
				test: /\.s[c|a]ss$/,
				use: [
					'style-loader',
					MiniCssExtractPlugin.loader,
					{ loader: 'css-loader', options: { importLoaders: 1 } },
					'postcss-loader',
					{
						loader: 'sass-loader',
						query: {
							includePaths: [ 'assets/css/abstracts' ],
							data:
								'@import "_colors"; ' +
								'@import "_variables"; ' +
								'@import "_breakpoints"; ' +
								'@import "_mixins"; ',
						},
					},
				],
			},
		],
	},
	plugins: [
		new MiniCssExtractPlugin( {
			filename: '[name].css',
		} ),
		new MergeExtractFilesPlugin( [
			'build/editor.js',
			'build/style.js',
		], 'build/vendors.js' ),
		new ProgressBarPlugin( {
			format: chalk.blue( 'Build' ) + ' [:bar] ' + chalk.green( ':percent' ) + ' :msg (:elapsed seconds)',
		} ),
		new DependencyExtractionWebpackPlugin( { injectPolyfill: true } ),
	],
};

const BlocksFrontendConfig = {
	...baseConfig,
	entry: {
		'product-categories': './assets/js/blocks/product-categories/frontend.js',
		'reviews-by-product': './assets/js/blocks/reviews/reviews-by-product/frontend.js',
		'reviews-by-category': './assets/js/blocks/reviews/reviews-by-category/frontend.js',
	},
	output: {
		path: path.resolve( __dirname, './build/' ),
		filename: '[name]-frontend.js',
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
						presets: [
							[ '@babel/preset-env', {
								modules: false,
								targets: {
									browsers: [ 'extends @wordpress/browserslist-config' ],
								},
							} ],
						],
						plugins: [
							require.resolve( '@babel/plugin-proposal-object-rest-spread' ),
							require.resolve( '@babel/plugin-transform-react-jsx' ),
							require.resolve( '@babel/plugin-proposal-async-generator-functions' ),
							require.resolve( '@babel/plugin-transform-runtime' ),
							NODE_ENV === 'production' ? require.resolve( 'babel-plugin-transform-react-remove-prop-types' ) : false,
						].filter( Boolean ),
					},
				},
			},
			{
				test: /\.s[c|a]ss$/,
				use: {
					loader: 'ignore-loader',
				},
			},
		],
	},
	plugins: [
		new CleanWebpackPlugin(),
		new ProgressBarPlugin( {
			format: chalk.blue( 'Build frontend scripts' ) + ' [:bar] ' + chalk.green( ':percent' ) + ' :msg (:elapsed seconds)',
		} ),
		new DependencyExtractionWebpackPlugin( { injectPolyfill: true } ),
	],
};

module.exports = [ GutenbergBlocksConfig, BlocksFrontendConfig ];
