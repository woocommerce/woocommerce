const path = require( 'path' );

const packagesToAugment = [ 'uuid', 'parsel-js' ];

/**
 * WordPress packages that must resolve to a single instance across the entire
 * test environment. pnpm 10 isolates transitive dependencies more strictly than
 * pnpm 9, which causes packages nested inside .pnpm to get their own copies of
 * these deps — breaking singleton assumptions like the private API lock/unlock
 * mechanism, shared data stores, and the blocks registry.
 *
 * Only packages that maintain global singleton state should be listed here.
 * Packages like @wordpress/compose or @wordpress/element must NOT be forced —
 * doing so breaks version-matched transitive deps.
 */
const singletonPackages = [
	'@wordpress/private-apis',
	'@wordpress/data',
	'@wordpress/blocks',
	'@wordpress/keyboard-shortcuts',
];

const rootDir = path.resolve( __dirname, '../../..' );
const singletonMap = {};
for ( const pkg of singletonPackages ) {
	try {
		singletonMap[ pkg ] = require.resolve( pkg, {
			paths: [ rootDir ],
		} );
	} catch ( e ) {
		// Package not installed in this workspace — skip.
	}
}

module.exports = ( modulePath, options ) => {
	// Force singleton packages to resolve from the workspace root, regardless
	// of where the requiring module lives (e.g. inside .pnpm).
	if ( singletonMap[ modulePath ] ) {
		return singletonMap[ modulePath ];
	}

	// Prevent transitive dependencies (e.g. @wordpress/patterns → @wordpress/block-editor@14.x)
	// from pulling in incompatible versions of block-editor. When a package inside .pnpm
	// tries to load @wordpress/block-editor, redirect to the workspace's version (13.x).
	// This is necessary because pnpm 10 isolates transitive deps more strictly than pnpm 9,
	// and @wordpress/block-library's dependency on @wordpress/patterns pulls in a much newer
	// block-editor that crashes due to version mismatches.
	if (
		modulePath === '@wordpress/block-editor' &&
		options.basedir &&
		options.basedir.includes( '.pnpm' )
	) {
		try {
			return require.resolve( '@wordpress/block-editor', {
				paths: [ rootDir ],
			} );
		} catch ( e ) {
			// Fall through to default resolution.
		}
	}

	// Call the defaultResolver, so we leverage its cache, error handling, etc.
	return options.defaultResolver( modulePath, {
		...options,
		// Use packageFilter to process parsed `package.json` before the resolution (see https://www.npmjs.com/package/resolve#resolveid-opts-cb)
		packageFilter: ( pkg ) => {
			// This is a workaround for https://github.com/uuidjs/uuid/pull/616 and https://github.com/LeaVerou/parsel/issues/79

			// jest-environment-jsdom 28+ tries to use browser exports instead of default exports,
			// but uuid only offers an ESM browser export and not a CommonJS one. Parsel incorrectly
			// prioritizes the browser export over the node export, causing a Jest error related to
			// trying to parse "export" syntax.
			if ( packagesToAugment.includes( pkg.name ) ) {
				delete pkg.exports;
				delete pkg.module;
			}
			return pkg;
		},
	} );
};
