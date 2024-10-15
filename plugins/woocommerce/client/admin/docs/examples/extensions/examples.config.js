/**
 * External dependencies
 */
const path = require( 'path' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const fs = require( 'fs' );
const woocommerceAdminConfig = require( path.resolve(
	__dirname,
	'../../../webpack.config.js'
) );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

if ( ! process.env.WC_EXT ) {
	throw new Error( 'Please provide an extension.' );
}

const extension = process.env.WC_EXT;
const extensionPath = path.join( __dirname, `${ extension }/js/index.js` );

if ( ! fs.existsSync( extensionPath ) ) {
	throw new Error( 'Extension example does not exist.' );
}

const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );

const webpackConfig = {
	mode: 'development',
	entry: {
		[ extension ]: extensionPath,
	},
	output: {
		filename: 'index.js',
		path: path.resolve( __dirname, `../../../../${ extension }/dist` ),
		libraryTarget: 'window',
	},
	externals: woocommerceAdminConfig.externals,
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
							[
								'@babel/preset-env',
								{ loose: true, modules: 'commonjs' },
							],
						],
						plugins: [ 'transform-es2015-template-literals' ],
					},
				},
				include: new RegExp(
					'/node_modules/(' +
						'|acorn-jsx' +
						'|d3-array' +
						'|debug' +
						'|regexpu-core' +
						'|unicode-match-property-ecmascript' +
						'|unicode-match-property-value-ecmascript)/'
				),
			},
			{
				test: /\.s?css$/,
				use: [ MiniCssExtractPlugin.loader, 'css-loader' ],
			},
		],
	},
	resolve: {
		extensions: [ '.json', '.js', '.jsx' ],
		modules: [ 'node_modules' ],
		alias: {
			'gutenberg-components': path.resolve(
				__dirname,
				'../../../node_modules/@wordpress/components/src'
			),
		},
	},
	plugins: [
		new CopyWebpackPlugin( {
			patterns: [
				{
					from: path.join( __dirname, `${ extension }/` ),
					to: path.resolve(
						__dirname,
						`../../../../${ extension }/`
					),
				},
			],
		} ),
		new MiniCssExtractPlugin( {
			filename: '[name]/dist/style.css',
		} ),
		new WooCommerceDependencyExtractionWebpackPlugin(),
	],
	watchOptions: {
		ignored: [ '**/dist', '**/node_modules' ],
	},
};

webpackConfig.devtool = 'source-map';

module.exports = webpackConfig;
