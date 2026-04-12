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
	// wp-6.8's block-editor uses registry selectors (`isSectionBlock`,
	// `__unstableGetEditorMode`) whose `.registry` property is set at
	// store-instantiation time. Multiple pnpm peer-context copies each bind
	// to a different registry, causing `TypeError: Cannot read properties of
	// undefined (reading 'select')` in any component rendering the block
	// canvas. Forcing a single copy ensures all selectors share one binding.
	'@wordpress/block-editor',
	'@wordpress/editor',
	'@wordpress/patterns',
];

const rootDir = path.resolve( __dirname, '../../..' );

// Resolve singleton packages starting from the workspace root, then falling
// back to known transitive-dependency locations. This covers packages like
// @wordpress/patterns that aren't direct deps of blocks but are pulled in by
// @wordpress/editor.
const singletonResolveBases = [
	rootDir,
	path.dirname(
		require.resolve( '@wordpress/editor/package.json', {
			paths: [ rootDir ],
		} )
	),
];
const singletonMap = {};
for ( const pkg of singletonPackages ) {
	for ( const base of singletonResolveBases ) {
		try {
			singletonMap[ pkg ] = require.resolve( pkg, {
				paths: [ base ],
			} );
			break;
		} catch ( e ) {
			// Try next base.
		}
	}
}

module.exports = ( modulePath, options ) => {
	// Force singleton packages to resolve from the workspace root, regardless
	// of where the requiring module lives (e.g. inside .pnpm).
	if ( singletonMap[ modulePath ] ) {
		return singletonMap[ modulePath ];
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
