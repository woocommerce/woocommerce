/**
 * External dependencies
 */
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const path = require( 'path' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );
const WebpackRTLPlugin = require( 'webpack-rtl-plugin' );

/**
 * Internal dependencies
 */
const {
	webpackConfig,
	plugin,
	StyleAssetPlugin,
} = require( '@woocommerce/internal-style-build' );
const {
	blockEntryPoints,
	getBlockMetaData,
	getEntryPointName,
} = require( './config/block-entry-points' );

const NODE_ENV = process.env.NODE_ENV || 'development';

module.exports = {
	mode: process.env.NODE_ENV || 'development',
	entry: {
		'build-style': __dirname + '/src/style.scss',
		...blockEntryPoints,
	},
	output: {
		path: __dirname,
	},
	module: {
		parser: webpackConfig.parser,
		rules: webpackConfig.rules,
	},
	plugins: [
		new RemoveEmptyScriptsPlugin(),
		new plugin( {
			filename: ( data ) => {
				return data.chunk.name.startsWith( '/build/blocks' )
					? `[name].css`
					: `[name]/style.css`;
			},
			chunkFilename: 'chunks/[id].style.css',
		} ),
		new WebpackRTLPlugin( {
			test: /(?<!style)\.css$/,
			filename: '[name]-rtl.css',
			minify: NODE_ENV === 'development' ? false : { safe: true },
		} ),
		new WebpackRTLPlugin( {
			test: /style\.css$/,
			filename: '[name]/style-rtl.css',
			minify: NODE_ENV === 'development' ? false : { safe: true },
		} ),
		new CopyWebpackPlugin( {
			patterns: [
				{
					from: './src/**/block.json',
					to( { absoluteFilename } ) {
						const blockMetaData = getBlockMetaData(
							path.resolve( __dirname, absoluteFilename )
						);
						const entryPointName = getEntryPointName(
							absoluteFilename,
							blockMetaData
						);
						return `./${ entryPointName }`;
					},
				},
			],
		} ),
		new StyleAssetPlugin(),
	],
};
