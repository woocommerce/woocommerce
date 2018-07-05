/**
 * /* eslint-disable
 *
 * @format
 */

const path = require( 'path' );
const ExtractTextPlugin = require( 'extract-text-webpack-plugin' );
const NODE_ENV = process.env.NODE_ENV || 'development';

const externals = {
	'@wordpress/blocks': { this: [ 'wp', 'blocks' ] },
	'@wordpress/components': { this: [ 'wp', 'components' ] },
	'@wordpress/compose': { this: [ 'wp', 'compose' ] },
	'@wordpress/data': { this: [ 'wp', 'data' ] },
	'@wordpress/editor': { this: [ 'wp', 'editor' ] },
	'@wordpress/element': { this: [ 'wp', 'element' ] },
	'@wordpress/hooks': { this: [ 'wp', 'hooks' ] },
	'@wordpress/html-entities': { this: [ 'wp', 'htmlEntities' ] },
	'@wordpress/i18n': { this: [ 'wp', 'i18n' ] },
	'@wordpress/keycodes': { this: [ 'wp', 'keycodes' ] },
	'@wordpress/api-request': { this: [ 'wp', 'apiRequest' ] },
	jquery: 'jQuery',
	tinymce: 'tinymce',
	moment: 'moment',
	react: 'React',
	'react-dom': 'ReactDOM',
};

const webpackConfig = {
	mode: NODE_ENV,
	entry: {
		index: './client/index.js',
		embedded: './client/embedded.js',
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
				exclude: /node_modules/,
			},
			{
				test: /\.(scss|css)$/,
				use: ExtractTextPlugin.extract( {
					fallback: 'style-loader',
					use: [
						'css-loader',
						{
							loader: 'sass-loader',
							query: {
								includePaths: [ 'client/stylesheets' ],
								data:
									'@import "_variables"; @import "_colors"; @import "_breakpoints"; @import "_mixins";',
							},
						},
					],
				} ),
			},
		],
	},
	resolve: {
		extensions: [ '.json', '.js', '.jsx' ],
		modules: [ path.join( __dirname, 'client' ), 'node_modules' ],
	},
	plugins: [ new ExtractTextPlugin( 'css/[name].css' ) ],
};

if ( webpackConfig.mode !== 'production' ) {
	webpackConfig.devtool = process.env.SOURCEMAP || 'source-map';
}

module.exports = webpackConfig;
