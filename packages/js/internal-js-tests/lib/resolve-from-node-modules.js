const fs = require( 'fs' );
const path = require( 'path' );

/**
 * Resolves a package from the node_modules chain above each start directory,
 * first match wins, and never from NODE_PATH.
 *
 * A plain require.resolve() also searches NODE_PATH, which the `jest` bin
 * shim pnpm generates points at pnpm's hoisted directory
 * (node_modules/.pnpm/node_modules). That holds a single version of each
 * package chosen across the whole install, so a full workspace install and
 * CI's filtered install could map a project to different versions.
 *
 * @param {string}   module    Package name.
 * @param {string[]} startDirs Directories to walk up from, in order of precedence.
 * @return {string|null} The package's entry file, or null when no start directory's chain has it.
 */
const resolveFromNodeModules = ( module, startDirs ) => {
	for ( const start of startDirs ) {
		let dir = start;
		for (;;) {
			if (
				fs.existsSync(
					path.join( dir, 'node_modules', module, 'package.json' )
				)
			) {
				return require.resolve( module, { paths: [ dir ] } );
			}
			const parent = path.dirname( dir );
			if ( parent === dir ) {
				break;
			}
			dir = parent;
		}
	}
	return null;
};

module.exports = { resolveFromNodeModules };
