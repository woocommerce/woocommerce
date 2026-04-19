/**
 * External dependencies
 */
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const path = require( 'path' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );

/**
 * Internal dependencies
 */
const {
	webpackConfig,
	plugin,
	StyleAssetPlugin,
	WebpackRTLPlugin,
} = require( '@woocommerce/internal-style-build' );
const {
	blockEntryPoints,
	getBlockMetaData,
	getEntryPointName,
} = require( './config/block-entry-points' );

const NODE_ENV = process.env.NODE_ENV || 'development';

module.exports = {
	mode: NODE_ENV,
	cache: ( process.env.CI && { type: 'memory' } ) || {
		type: 'filesystem',
		cacheDirectory: path.resolve(
			__dirname,
			'node_modules/.cache/webpack'
		),
		buildDependencies: {
			config: [
				__filename,
				path.resolve( __dirname, '../../../pnpm-lock.yaml' ),
				require.resolve( '@woocommerce/internal-style-build' ),
			],
		},
	},
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
		new WebpackRTLPlugin(),
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
