/** @format */
/**
 * External dependencies
 */
const path = require( 'path' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const fs = require( 'fs' );

const extArg = process.argv.find( arg => arg.startsWith( '--ext=' ) );

if ( ! extArg ) {
	throw new Error( 'Please provide an extension.' );
}

const extension = extArg.slice( 6 );
const extensionPath = path.join( __dirname, `${ extension }/js/index.js` );

if ( ! fs.existsSync( extensionPath ) ) {
	throw new Error( 'Extension example does not exist.' );
}

const externals = {
	'@wordpress/api-fetch': 'window.wp.apiFetch',
	'@wordpress/blocks': 'window.wp.blocks',
	'@wordpress/components': 'window.wp.components',
	'@wordpress/compose': 'window.wp.compose',
	'@wordpress/data': 'window.wp.data',
	'@wordpress/editor': 'window.wp.editor',
	'@wordpress/element': 'window.wp.element',
	'@wordpress/hooks': 'window.wp.hooks',
	'@wordpress/html-entities': 'window.wp.htmlEntities',
	'@wordpress/i18n': 'window.wp.i18n',
	'@wordpress/keycodes': 'window.wp.keycodes',
	tinymce: 'tinymce',
	moment: 'moment',
	react: 'React',
	lodash: 'lodash',
	'react-dom': 'ReactDOM',
	'@woocommerce/components': 'window.wc.components',
	'@woocommerce/csv-export': 'window.wc.csvExport',
	'@woocommerce/currency': 'window.wc.currency',
	'@woocommerce/date': 'window.wc.date',
	'@woocommerce/navigation': 'window.wc.navigation',
	'@woocommerce/number': 'window.wc.number',
};

const webpackConfig = {
	mode: 'development',
	entry: {
		[ extension ]: extensionPath,
	},
	output: {
		filename: '[name]/dist/index.js',
		path: path.resolve( __dirname ),
	},
	externals,
	module: {
		rules: [
			{
				parser: {
					amd: false,
				},
			},
			{
				test: /\.jsx?$/,
				loader: 'babel-loader',
				exclude: /node_modules/,
			},
			{
				test: /\.js?$/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [
							[ '@babel/preset-env', { loose: true, modules: 'commonjs' } ],
						],
						plugins: [ 'transform-es2015-template-literals' ],
					},
				},
			},
		],
	},
	resolve: {
		extensions: [ '.json', '.js', '.jsx' ],
		modules: [
			'node_modules',
		],
		alias: {
			'gutenberg-components': path.resolve( __dirname, 'node_modules/@wordpress/components/src' ),
		},
	},
	plugins: [
		new CopyWebpackPlugin( [
			{
				from: path.join( __dirname, `${ extension }/` ),
				to: path.resolve( __dirname, `../../../../${ extension }/` ),
			},
		] ),
	],
};

webpackConfig.devtool = 'source-map';

module.exports = webpackConfig;
