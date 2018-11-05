/** @format */
/**
 * External dependencies
 */
const path = require( 'path' );
const ExtractTextPlugin = require( 'extract-text-webpack-plugin' );
const NODE_ENV = process.env.NODE_ENV || 'development';

const externals = {
	'@woocommerce/components': { this: [ 'wc', 'components' ] },
	'@woocommerce/currency': { this: [ 'wc', 'currency' ] },
	'@woocommerce/date': { this: [ 'wc', 'date' ] },
	'@wordpress/api-fetch': { this: [ 'wp', 'apiFetch' ] },
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
	tinymce: 'tinymce',
	moment: 'moment',
	react: 'React',
	'react-dom': 'ReactDOM',
};

const wcAdminPackages = {
	components: './client/components',
	currency: './packages/currency',
	date: './packages/date',
	navigation: './packages/navigation',
};

Object.keys( wcAdminPackages ).forEach( ( name ) => {
	externals[ `@woocommerce/${ name }` ] = {
		this: [ 'wc', name ],
	};
} );

const webpackConfig = {
	mode: NODE_ENV,
	entry: {
		app: './client/index.js',
		embedded: './client/embedded.js',
		...wcAdminPackages,
	},
	output: {
		filename: './dist/[name]/index.js',
		path: __dirname,
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
			{ test: /\.md$/, use: 'raw-loader' },
			{
				test: /\.(scss|css)$/,
				use: ExtractTextPlugin.extract( {
					fallback: 'style-loader',
					use: [
						'css-loader',
						{
							// postcss loader so we can use autoprefixer and theme Gutenberg components
							loader: 'postcss-loader',
							options: {
								config: {
									path: 'postcss.config.js',
								},
							},
						},
						{
							loader: 'sass-loader',
							query: {
								includePaths: [ 'client/stylesheets/abstracts' ],
								data:
									'@import "_colors"; ' +
									'@import "_variables"; ' +
									'@import "_breakpoints"; ' +
									'@import "_mixins"; ',
							},
						},
					],
				} ),
			},
		],
	},
	resolve: {
		extensions: [ '.json', '.js', '.jsx' ],
		modules: [ path.join( __dirname, 'client' ), path.join( __dirname, 'packages' ), 'node_modules' ],
		alias: {
			'gutenberg-components': path.resolve( __dirname, 'node_modules/@wordpress/components/src' ),
		},
	},
	plugins: [
		new ExtractTextPlugin( {
			filename: './dist/[name]/style.css',
		} ),
	],
};

if ( webpackConfig.mode !== 'production' ) {
	webpackConfig.devtool = process.env.SOURCEMAP || 'source-map';
}

module.exports = webpackConfig;
