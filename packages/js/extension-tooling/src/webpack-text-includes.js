const fs = require( 'node:fs' );
const path = require( 'node:path' );

function expandIncludes( compilation, filename, included, indentation = '' ) {
	if ( included.has( filename ) ) {
		return '';
	}
	included.add( filename );
	let source;
	try {
		source = fs.readFileSync( filename, 'utf8' ).replace( /^\uFEFF/, '' );
	} catch ( error ) {
		if ( error.code === 'ENOENT' ) {
			compilation.missingDependencies.add( filename );
		}
		throw error;
	}
	compilation.fileDependencies.add( filename );
	const newline = source.match( /\r?\n/ )?.[ 0 ] || '\n';
	return source
		.split( newline )
		.map( ( line, index ) => {
			const match = line.match(
				/^(\s*)include\s*\(\s*(['"])([^'"]+)\2\s*\);?\s*$/
			);
			if ( ! match ) {
				// An unexpanded include would fail in the browser, so fail the build instead.
				if ( /^\s*include\s*\(/.test( line ) ) {
					throw new Error(
						`${ filename }:${
							index + 1
						}: unsupported include() line. Use include( 'path.js' ); on its own line.`
					);
				}
				return line ? indentation + line : line;
			}
			return expandIncludes(
				compilation,
				path.join( path.dirname( filename ), match[ 3 ] ),
				included,
				indentation + match[ 1 ]
			);
		} )
		.join( newline );
}

module.exports = function includeScripts( entries ) {
	const name = 'IncludeScripts';

	return {
		apply( compiler ) {
			const { Compilation, sources } = compiler.webpack;

			compiler.hooks.thisCompilation.tap( name, ( compilation ) => {
				compilation.hooks.processAssets.tap(
					{
						name,
						stage: Compilation.PROCESS_ASSETS_STAGE_ADDITIONAL,
					},
					() => {
						for ( const [ source, destination ] of entries ) {
							try {
								const script = new sources.RawSource(
									expandIncludes(
										compilation,
										path.resolve(
											compiler.context,
											source
										),
										new Set()
									)
								);
								compilation.emitAsset( destination, script );
								compilation.emitAsset(
									destination.replace( /\.js$/, '.min.js' ),
									script
								);
							} catch ( error ) {
								compilation.errors.push( error );
							}
						}
					}
				);
			} );
		},
	};
};
