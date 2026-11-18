const { posix } = require( 'node:path' );
const uglify = require( 'uglify-js' );

module.exports = function minifyBundleCopies( copies, inPlace = [] ) {
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
							const replace = inPlace.some( ( pattern ) =>
								posix.matchesGlob( asset.name, pattern )
							);
							const copy =
								! asset.name.endsWith( '.min.js' ) &&
								copies.some( ( pattern ) =>
									posix.matchesGlob( asset.name, pattern )
								);
							if ( ! replace && ! copy ) {
								continue;
							}
							const destination = replace
								? asset.name
								: asset.name.replace( /\.js$/, '.min.js' );
							// includeScripts emits its own .min.js copy; a second one would conflict.
							if (
								! replace &&
								compilation.getAsset( destination )
							) {
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
							const output = new sources.RawSource( result.code );
							if ( replace ) {
								compilation.updateAsset( destination, output );
							} else {
								compilation.emitAsset( destination, output, {
									minimized: true,
								} );
							}
						}
					}
				);
			} );
		},
	};
};
