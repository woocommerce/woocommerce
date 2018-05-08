/* eslint-disable */
const path = require( 'path' );
const ExtractTextPlugin = require( 'extract-text-webpack-plugin' );
var NODE_ENV = process.env.NODE_ENV || 'development';

/**
 * Given a string, returns a new string with dash separators converedd to
 * camel-case equivalent. This is not as aggressive as `_.camelCase` in
 * converting to uppercase, where Lodash will convert letters following
 * numbers.
 *
 * @param {string} string Input dash-delimited string.
 *
 * @return {string} Camel-cased string.
 */
function camelCaseDash( string ) {
	return string.replace(
		/-([a-z])/,
		( match, letter ) => letter.toUpperCase()
	);
}

const coreGlobals = [
	'blocks',
	'components',
	'date',
	'editor',
	'element',
	'utils',
	'data',
	'viewport',
	'core-data',
	'plugins',
	'edit-post',
	'hooks',
	'i18n',
	'api-request',
];

const externals = {
	react: 'React',
	'react-dom': 'ReactDOM',
	tinymce: 'tinymce',
	moment: 'moment',
	jquery: 'jQuery',
};

coreGlobals.forEach( ( name ) => {
	externals[ `@wordpress/${ name }` ] = {
		this: [ 'wp', camelCaseDash( name ) ],
	};
} );

const webpackConfig = {
	mode: NODE_ENV,
	entry: {
		index: './js/src/index.js',
	},
	output: {
		path: path.resolve( 'dist' ),
		filename: '[name].js',
		library: [ 'wc', '[name]' ],
		libraryTarget: 'this',
	},
	externals,
	module: {
		rules: [
			{
				test: /\.jsx?$/,
				loader: 'babel-loader',
				exclude: /node_modules/
			},
			{
				test: /\.scss$/,
				use: ExtractTextPlugin.extract( {
					fallback: 'style-loader',
					use: [ 'css-loader', 'sass-loader' ],
				} ),
			},
		],
	},
	plugins: [
		new ExtractTextPlugin( 'css/style.css' ),
	],
};

if ( webpackConfig.mode !== 'production' ) {
	webpackConfig.devtool = process.env.SOURCEMAP || 'source-map';
}

module.exports = webpackConfig;
