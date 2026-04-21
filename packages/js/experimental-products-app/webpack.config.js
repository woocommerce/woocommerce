/**
 * External dependencies
 */
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );
const WebpackRTLPlugin = require( '@automattic/webpack-rtl-plugin' );
const path = require( 'path' );

/**
 * Custom plugin to rename .rtl.css files to -rtl.css for WordPress compatibility
 * This is needed because @automattic/webpack-rtl-plugin hardcodes the .rtl.css pattern
 */
class RTLFilenameFixPlugin {
	apply( compiler ) {
		compiler.hooks.afterEmit.tap( 'RTLFilenameFixPlugin', ( compilation ) => {
			const fs = require( 'fs' );

			compilation.entrypoints.forEach( ( entrypoint ) => {
				entrypoint.chunks.forEach( ( chunk ) => {
					chunk.files.forEach( ( filename ) => {
						if ( filename.endsWith( '.rtl.css' ) ) {
							const oldPath = path.join(
								compilation.outputOptions.path,
								filename
							);
							const newPath = oldPath.replace(
								'.rtl.css',
								'-rtl.css'
							);

							if ( fs.existsSync( oldPath ) ) {
								try {
									fs.copyFileSync( oldPath, newPath );
									fs.unlinkSync( oldPath );

									const newFilename = filename.replace(
										'.rtl.css',
										'-rtl.css'
									);
									chunk.files.delete( filename );
									chunk.files.add( newFilename );
								} catch ( error ) {
									console.warn(
										`RTL filename fix failed for ${ filename }:`,
										error.message
									);
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

const NODE_ENV = process.env.NODE_ENV || 'development';

module.exports = {
	mode: NODE_ENV,
	cache: ( process.env.CI && { type: 'memory' } ) || {
		type: 'filesystem',
		cacheDirectory: path.resolve(
			__dirname,
			'node_modules/.cache/webpack'
		),
	},
	entry: {
		'build-style': __dirname + '/src/style.scss',
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
			filename: '[name]/style.css',
			chunkFilename: 'chunks/[id].style.css',
		} ),
		new WebpackRTLPlugin( {
			test: /(?<!style)\.css$/,
			filename: '[name]-rtl.css',
			minify:
				NODE_ENV === 'development'
					? false
					: {
							preset: [
								'default',
								{
									discardComments: {
										removeAll: true,
									},
									normalizeWhitespace: true,
								},
							],
					  },
		} ),
		new WebpackRTLPlugin( {
			test: /style\.css$/,
			filename: '[name]/style-rtl.css',
			minify:
				NODE_ENV === 'development'
					? false
					: {
							preset: [
								'default',
								{
									discardComments: {
										removeAll: true,
									},
									normalizeWhitespace: true,
								},
							],
					  },
		} ),
		new RTLFilenameFixPlugin(),
		new StyleAssetPlugin(),
	],
};
