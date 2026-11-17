const { posix } = require( 'node:path' );
const uglify = require( 'uglify-js' );

module.exports = function minifyBundleCopies( copies ) {
	const name = 'MinifyBundleCopies';

	return {
		apply( compiler ) {
			const { Compilation, WebpackError, sources } = compiler.webpack;

			compiler.hooks.thisCompilation.tap( name, ( compilation ) => {
				// Keep dependency metadata based on the original bundles.
				compilation.hooks.processAssets.tap(
					{ name, stage: Compilation.PROCESS_ASSETS_STAGE_REPORT },
					() => {
						for ( const asset of compilation.getAssets() ) {
							if (
								asset.name.endsWith( '.min.js' ) ||
								! copies.some( ( pattern ) =>
									posix.matchesGlob( asset.name, pattern )
								)
							) {
								continue;
							}
							const destination = asset.name.replace(
								/\.js$/,
								'.min.js'
							);
							// includeScripts emits its own .min.js copy; a second one would conflict.
							if ( compilation.getAsset( destination ) ) {
								continue;
							}
							const result = uglify.minify(
								asset.source
									.source()
									.toString()
									.replace( /^\uFEFF/, '' )
							);
							if ( result.error ) {
								compilation.errors.push(
									new WebpackError(
										`${ asset.name }: ${ result.error.message }`
									)
								);
								continue;
							}
							compilation.emitAsset(
								destination,
								new sources.RawSource( result.code ),
								{
									minimized: true,
								}
							);
						}
					}
				);
			} );
		},
	};
};
