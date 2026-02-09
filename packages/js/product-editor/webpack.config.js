/**
 * External dependencies
 */
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const path = require( 'path' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );
const WebpackRTLPlugin = require( '@automattic/webpack-rtl-plugin' );

/**
 * Custom plugin to rename .rtl.css files to -rtl.css for WordPress compatibility
 * This is needed because @automattic/webpack-rtl-plugin hardcodes the .rtl.css pattern
 */
class RTLFilenameFixPlugin {
	apply( compiler ) {
		compiler.hooks.afterEmit.tap( 'RTLFilenameFixPlugin', ( compilation ) => {
			// This runs after assets are emitted, so we use file system operations
			const fs = require( 'fs' );
			const path = require( 'path' );
			
			compilation.entrypoints.forEach( ( entrypoint ) => {
				entrypoint.chunks.forEach( ( chunk ) => {
					chunk.files.forEach( ( filename ) => {
						if ( filename.endsWith( '.rtl.css' ) ) {
							const oldPath = path.join( compilation.outputOptions.path, filename );
							const newPath = oldPath.replace( '.rtl.css', '-rtl.css' );
							
							if ( fs.existsSync( oldPath ) ) {
								try {
									// Copy to new filename
									fs.copyFileSync( oldPath, newPath );
									// Remove old file
									fs.unlinkSync( oldPath );
									
									// Update compilation records
									const newFilename = filename.replace( '.rtl.css', '-rtl.css' );
									chunk.files.delete( filename );
									chunk.files.add( newFilename );
								} catch ( error ) {
									console.warn( `RTL filename fix failed for ${filename}:`, error.message );
								}
							}
						}
					} );
				} );
			} );
		} );
	}
}

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
	cache: ( NODE_ENV !== 'development' && { type: 'memory' } ) || {
		type: 'filesystem',
		cacheDirectory: path.resolve(
			__dirname,
			'node_modules/.cache/webpack'
		),
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
		new WebpackRTLPlugin( {
			test: /(?<!style)\.css$/,
			filename: '[name]-rtl.css',
			minify: NODE_ENV === 'development' ? false : {
				preset: [
					'default',
					{
						discardComments: {
							removeAll: true, // Remove all comments
						},
						normalizeWhitespace: true, // Normalize whitespace
					},
				],
			},
		} ),
		new WebpackRTLPlugin( {
			test: /style\.css$/,
			filename: '[name]/style-rtl.css',
			minify: NODE_ENV === 'development' ? false : {
				preset: [
					'default',
					{
						discardComments: {
							removeAll: true, // Remove all comments
						},
						normalizeWhitespace: true, // Normalize whitespace
					},
				],
			},
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
		new RTLFilenameFixPlugin(), // Convert .rtl.css to -rtl.css for WordPress compatibility
		new StyleAssetPlugin(),
	],
};
