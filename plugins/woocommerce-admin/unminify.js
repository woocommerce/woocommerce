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
 * 5. Hook into compilation later so we're running after Source Map generation. (https://webpack.js.org/api/compilation-hooks/: PROCESS_ASSETS_STAGE_OPTIMIZE_INLINE) 
 */
const path = require( 'path' );
const ModuleFilenameHelpers = require( 'webpack/lib/ModuleFilenameHelpers' );
const webpack = require( 'webpack' );

const getFileName = ( name, ext, opts ) => {
	if ( name.match( /([-_.]min)[-_.]/ ) ) {
		return name.replace( /[-_.]min/, '' );
	}

	const suffix = ( opts.postfix || 'nomin' ) + '.' + ext;
	if ( name.match( new RegExp( '.' + ext + '$' ) ) ) {
		return name.replace( new RegExp( ext + '$' ), suffix );
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
		const options = this.options;
		const outputNormal = {};

		compiler.hooks.compilation.tap(
			'UnminifyWebpackPlugin',
			( compilation ) => {
				compilation.hooks.processAssets.tap(
					{
						name: 'UnminifyWebpackPlugin',
						stage: webpack.Compilation.PROCESS_ASSETS_STAGE_DERIVED,
					},
					( assets ) => {
						Object.entries( assets ).forEach(
							( [ pathname, source ] ) => {
								if (
									! ModuleFilenameHelpers.matchObject(
										options,
										pathname
									)
								) {
									return;
								}

								let sourceCode = source.source();
								if (
									options.mainEntry &&
									pathname === options.mainEntry
								) {
									sourceCode = sourceCode.replace(
										/ \+ "\.min\.js"$/m,
										' + ".js"'
									);
								}

								const dest = compiler.options.output.path;
								const outputPath = path.resolve(
									dest,
									getFileName(
										pathname,
										path.extname( pathname ).substr( 1 ),
										options
									)
								);

								outputNormal[ outputPath ] = {
									filename: getFileName(
										pathname,
										path.extname( pathname ).substr( 1 ),
										options
									),
									content: sourceCode,
									size: Buffer.from( sourceCode, 'utf-8' )
										.length,
								};
							}
						);
					}
				);

				compilation.hooks.afterProcessAssets.tap(
					'UnminifiedWebpackPlugin',
					() => {
						for ( const [ key, value ] of Object.entries(
							outputNormal
						) ) {
							compilation.emitAsset(
								value.filename,
								new webpack.sources.RawSource( value.content )
							);
						}
					}
				);
			}
		);
	}
}

module.exports = UnminifyWebpackPlugin;
