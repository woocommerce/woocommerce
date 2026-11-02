/**
 * External dependencies.
 */
const fs = require( 'fs' );
const path = require( 'path' );

// A cache for package files so that we don't keep loading them unnecessarily.
const packageFileCache = {};

/**
 * Loads a package file or pull it from the cache.
 *
 * @param {string} packagePath The path to the package directory.
 * @return {Object} The package file.
 */
function loadPackageFile( packagePath ) {
	// Resolve the absolute path for consistency when loading and updating.
	packagePath = path.resolve( __dirname, packagePath );

	if ( packageFileCache[ packagePath ] ) {
		return packageFileCache[ packagePath ];
	}

	const packageFile = JSON.parse(
		fs.readFileSync( path.join( packagePath, 'package.json' ), 'utf8' )
	);

	packageFileCache[ packagePath ] = packageFile;
	return packageFile;
}

/**
 * Loads a tsconfig.json, or null if missing or not plain JSON.
 *
 * Returning null on a parse failure keeps the hook from clobbering JSONC
 * tsconfigs that include structural comments. Affected packages must be
 * converted to plain JSON to participate in the references sync.
 *
 * @param {string} tsconfigPath Absolute path to the tsconfig.json file.
 * @return {Object|null} Parsed config, or null if missing or unparseable.
 */
function loadTsconfig( tsconfigPath ) {
	if ( ! fs.existsSync( tsconfigPath ) ) {
		return null;
	}
	try {
		return JSON.parse( fs.readFileSync( tsconfigPath, 'utf8' ) );
	} catch {
		return null;
	}
}

/**
 * Writes a tsconfig.json with the project's standard tab indentation.
 *
 * @param {string} tsconfigPath Absolute path to the tsconfig.json file.
 * @param {Object} tsconfig     Config object to serialize.
 */
function writeTsconfig( tsconfigPath, tsconfig ) {
	fs.writeFileSync(
		tsconfigPath,
		JSON.stringify( tsconfig, null, '\t' ) + '\n',
		'utf8'
	);
}

/**
 * Identify workspace packages that consume @woocommerce/internal-build.
 *
 * A TS consumer has @woocommerce/internal-build in dependencies or
 * devDependencies. Whether the package has a tsconfig.json on disk is
 * verified by loadTsconfig later in syncTsReferences.
 *
 * @param {Object} lockfile The lockfile passed to afterAllResolved.
 * @return {Map<string, { packagePath: string, absolutePath: string }>}
 */
function identifyTsConsumers( lockfile ) {
	const consumers = new Map();

	for ( const packagePath in lockfile.importers ) {
		const packageFile = loadPackageFile( packagePath );
		const allDeps = {
			...( packageFile.dependencies || {} ),
			...( packageFile.devDependencies || {} ),
		};
		if ( ! ( '@woocommerce/internal-build' in allDeps ) ) {
			continue;
		}

		const absolutePath = path.resolve( __dirname, packagePath );
		consumers.set( packageFile.name, { packagePath, absolutePath } );
	}

	return consumers;
}

/**
 * Compute the list of project references for a given consumer.
 *
 * References include workspace dependencies (from `dependencies`, not
 * `devDependencies`) that are themselves TS consumers. Paths are stored
 * as posix-style relative paths from the consumer to the dep.
 *
 * @param {Object} packageFile          The consumer's package.json contents.
 * @param {Object} resolvedDependencies The lockfile importer entry for the consumer.
 * @param {Map}    consumers            Output of identifyTsConsumers.
 * @param {string} consumerAbsolutePath Absolute path to the consumer's directory.
 * @return {Array<{ path: string }>} Sorted references array.
 */
function computeReferences(
	packageFile,
	resolvedDependencies,
	consumers,
	consumerAbsolutePath
) {
	const references = [];
	const declared = packageFile.dependencies || {};
	const resolved = resolvedDependencies.dependencies || {};

	for ( const depName of Object.keys( declared ) ) {
		if ( ! declared[ depName ].startsWith( 'workspace:' ) ) {
			continue;
		}
		if ( ! consumers.has( depName ) ) {
			continue;
		}
		const resolvedDep = resolved[ depName ];
		if ( ! resolvedDep || ! resolvedDep.startsWith( 'link:' ) ) {
			continue;
		}

		const depAbsolutePath = path.resolve(
			consumerAbsolutePath,
			resolvedDep.slice( 'link:'.length )
		);
		const relPath = path
			.relative( consumerAbsolutePath, depAbsolutePath )
			.split( path.sep )
			.join( '/' );

		references.push( { path: relPath } );
	}

	references.sort( ( a, b ) => a.path.localeCompare( b.path ) );
	return references;
}

/**
 * Synchronize TypeScript project references across all TS-consuming packages.
 *
 * For each consumer:
 *   - Set compilerOptions.composite = true
 *   - Replace the top-level `references` array with the computed list
 *
 * Workspace deps that are themselves TS consumers become references so that
 * `tsc -b` can walk the graph and build/type-check dependencies in order.
 *
 * @param {Object} lockfile The lockfile passed to afterAllResolved.
 * @param {Object} context  The pnpm hook context.
 */
function syncTsReferences( lockfile, context ) {
	context.log( '[tsrefs] Synchronizing TypeScript project references' );

	const consumers = identifyTsConsumers( lockfile );
	if ( consumers.size === 0 ) {
		context.log( '[tsrefs] No TS consumers found' );
		return;
	}

	// Update each consumer's own tsconfig.json with composite + references.
	for ( const [ name, { packagePath, absolutePath } ] of consumers ) {
		const tsconfigPath = path.join( absolutePath, 'tsconfig.json' );
		const tsconfig = loadTsconfig( tsconfigPath );
		if ( ! tsconfig ) {
			context.log(
				`[tsrefs][${ name }]    Skipped — tsconfig.json could not be parsed as plain JSON.`
			);
			continue;
		}

		const packageFile = loadPackageFile( packagePath );
		const references = computeReferences(
			packageFile,
			lockfile.importers[ packagePath ],
			consumers,
			absolutePath
		);

		const originalState = JSON.stringify( {
			composite: tsconfig.compilerOptions?.composite,
			references: tsconfig.references,
		} );

		tsconfig.compilerOptions = tsconfig.compilerOptions || {};
		tsconfig.compilerOptions.composite = true;
		tsconfig.references = references;

		const newState = JSON.stringify( {
			composite: tsconfig.compilerOptions.composite,
			references: tsconfig.references,
		} );

		if ( newState !== originalState ) {
			context.log(
				`[tsrefs][${ name }]    Updating references (${ references.length } entries)`
			);
			writeTsconfig( tsconfigPath, tsconfig );
		}
	}

	context.log( '[tsrefs] Done' );
}

/**
 * This hook allows for the mutation of the lockfile before it is serialized.
 *
 * @param {Object}					lockfile				 The lock file that was produced by PNPM.
 * @param {string}					lockfile.lockfileVersion The version of the lock file spec.
 * @param {Object.<string, Object>} lockfile.importers		 The packages in the workspace that are included in the lock file, keyed by the relative path to the package.
 * @param {Object}					context					 The hook context object.
 * @param {Function.<string>}		context.log				 Logs a message to the console.
 *
 * @return {Object} lockfile The updated lockfile.
 */
function afterAllResolved( lockfile, context ) {
	syncTsReferences( lockfile, context );

	return lockfile;
}

// Note: The hook function names are important. They are used by PNPM when determining what functions to call.
module.exports = {
	hooks: {
		afterAllResolved,
	},
};
