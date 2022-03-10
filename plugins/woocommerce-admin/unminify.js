/**
 * Repurposed UnminifiedWebpackPlugin.
 *
 * See: https://github.com/leftstick/unminified-webpack-plugin/blob/master/index.js
 *
 * Changes:
 * 1. Remove check for UglifyJsPlugin - Terser is the successor.
 * 2. Remove check for development mode - we always want unminified files.
 * 3. Remove BannerPlugin support - we don't use it.
 * 4. Remove the 'min' suffix from the chunk loaded in the new `mainEntry` option.
 * 5. Hook into compilation later so we're running after Source Map generation.
 */
const path = require( 'path' );
const ModuleFilenameHelpers = require( 'webpack/lib/ModuleFilenameHelpers' );

const getFileName = ( name, ext, opts ) => {
	if ( name.match(/([-_.]min)[-_.]/ ) ) {
		return name.replace( /[-_.]min/, '' );
	}

	const suffix = ( opts.postfix || 'nomin' ) + '.' + ext;
	if ( name.match( new RegExp( '\.' + ext + '$' ) ) ) {
		return name.replace( new RegExp( ext + '$' ), suffix )
	}

	return name + suffix;
};

class UnminifyWebpackPlugin {
	constructor( opts ) {
		const options = opts || {};

		this.options = {
			test: options.test || /\.(js|css)($|\?)/i,
			mainEntry: options.mainEntry || false,
		};
	}

	apply( compiler ) {
		// Hook after asset optimization if we're using a devtool (source map).
		// @todo: Update to afterFinishAssets for Webpack 5.x?
		const compilationHook = compiler.options.devtool ? 'afterOptimizeAssets' : 'additionalAssets';

		compiler.hooks.compilation.tap( 'UnminifyWebpackPlugin', ( compilation ) => {
			compilation.hooks[ compilationHook ].tap( 'UnminifyWebpackPlugin', () => {
				const files = [
					...compilation.additionalChunkAssets
				];
	
				compilation.chunks.forEach( chunk => files.push( ...chunk.files ) );

				const finalFiles = files.filter( ModuleFilenameHelpers.matchObject.bind( null, this.options ) );

				finalFiles.forEach( ( minified ) => {
					const asset = compilation.assets[ minified ];
					let source = asset.source();
					const ext = path.extname( minified ).substr( 1 );
					const unminified = getFileName( minified, ext, this.options );
	
					// Remove the ".min" suffix from the lazy loaded chunk filenames.
					if ( this.options.mainEntry && minified === this.options.mainEntry ) {
						// See: https://github.com/webpack/webpack/blob/v4.43.0/lib/web/JsonpMainTemplatePlugin.js#L129
						// NOTE: This will break with Webpack 5.x!
						source = source.replace( / \+ "\.min\.js"$/m, ' + ".js"' );
					}
	
					compilation.assets[ unminified ] = {
						source: () => {
							return source;
						},
						size: () => {
							return source.length;
						}
					};
				} );
			} );
		} );
	}
}

module.exports = UnminifyWebpackPlugin;
