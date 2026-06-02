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
 * Updates a package file on disk and in the cache.
 *
 * @param {string} packagePath The path to the package file to update.
 * @param {Object} packageFile The new package file contents.
 */
function updatePackageFile( packagePath, packageFile ) {
	// Resolve the absolute path for consistency when loading and updating.
	packagePath = path.resolve( __dirname, packagePath );
	packageFileCache[ packagePath ] = packageFile;

	fs.writeFileSync(
		path.join( packagePath, 'package.json' ),
		// Make sure to keep the newline at the end of the file.
		JSON.stringify( packageFile, null, '\t' ) + '\n',
		'utf8'
	);
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
 * Identify workspace packages that consume @woocommerce/internal-ts-config.
 *
 * A TS consumer has @woocommerce/internal-ts-config in dependencies or
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
		if ( ! ( '@woocommerce/internal-ts-config' in allDeps ) ) {
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
 * Populated config object based on declared and resolved dependencies.
 *
 * @param {string}            packageName          Package name.
 * @param {string}            packagePath          Package path.
 * @param {Object}            declaredDependencies Declared dependencies from package-file.
 * @param {Object}            resolvedDependencies Resolved dependencies from lock-file.
 * @param {Object}            config               Dependency output path configuration.
 * @param {Object}            context              The hook context object.
 * @param {Function.<string>} context.log          Logs a message to the console.
 *
 * @return void
 */
function updateConfig(
	packageName,
	packagePath,
	declaredDependencies,
	resolvedDependencies,
	config,
	context
) {
	for ( const [ key, value ] of Object.entries( declaredDependencies ) ) {
		if ( value.startsWith( 'workspace:' ) ) {
			const normalizedPath = path.join(
				packagePath,
				resolvedDependencies[ key ].replace( 'link:', '' )
			);
			context.log(
				`[wireit][${ packageName }]    Inspecting workspace dependency: ${ key } (${ normalizedPath })`
			);

			// Actualize output storage with the identified entries.
			const dependencyFile = loadPackageFile( normalizedPath );
			if ( dependencyFile.files ) {
				for ( const entry in dependencyFile.files ) {
					const entryValue = dependencyFile.files[ entry ];
					// Since 'build-module' and 'build-types' are generated simultaneously, it is more efficient for WireIt to track changes
					// to 'build-types' only. This approach also enables a clear separation of the CJS and ESM watch build cascades.
					if ( entryValue === 'build-module' && dependencyFile.files.includes( 'build-types' ) ) {
						continue;
					}

					let normalizedValue;
					if ( entryValue.startsWith( '!' ) ) {
						normalizedValue =
							'!' +
							path.join(
								'node_modules',
								key,
								entryValue.substring( 1 )
							);
					} else {
						normalizedValue = path.join(
							'node_modules',
							key,
							entryValue
						);
					}
					config.files.push( normalizedValue );

					context.log(
						`[wireit][${ packageName }]        - ${ normalizedValue }`
					);
				}
			} else {
				context.log( `[wireit][${ packageName }]        ---` );
			}
		}
	}
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
	context.log( '[wireit] Updating Dependency Lists' );

	for ( const packagePath in lockfile.importers ) {
		const packageFile = loadPackageFile( packagePath );
		if ( packageFile.wireit ) {
			context.log(
				`[wireit][${ packageFile.name }] Verifying 'wireit.dependencyOutputs'`
			);

			// Include the lock file in the fingerprint in case resolved versions change.
			const lockfilePath = path.join(
				path.relative( packagePath, '.' ),
				'pnpm-lock.yaml'
			);

			// Initialize outputs storage and hash it's original state.
			const config = {
				allowUsuallyExcludedPaths: true, // This is needed so we can reference files in `node_modules`.
				files: [ 'package.json', lockfilePath ], // The files list will include globs for dependency files that we should fingerprint.
			};
			const originalConfigState = JSON.stringify( config );

			// Walk through workspace-located dependencies and provision.
			updateConfig(
				packageFile.name,
				packagePath,
				{
					...( packageFile.dependencies || {} ),
					...( packageFile.devDependencies || {} ),
				},
				{
					...( lockfile.importers[ packagePath ].dependencies || {} ),
					...( lockfile.importers[ packagePath ].devDependencies ||
						{} ),
				},
				config,
				context
			);

			// Verify config state and update manifest on mismatch.
			let updated = false;
			const newConfigState = JSON.stringify( config );
			if ( newConfigState !== originalConfigState ) {
				const loadedConfigState = JSON.stringify(
					packageFile.wireit?.dependencyOutputs || {}
				);
				if ( newConfigState !== loadedConfigState ) {
					context.log(
						`[wireit][${ packageFile.name }]    Conclusion: outdated, updating 'wireit.dependencyOutputs'`
					);

					packageFile.wireit.dependencyOutputs = config;
					updatePackageFile( packagePath, packageFile );
					updated = true;
				}
			}
			if ( ! updated ) {
				context.log(
					`[wireit][${ packageFile.name }]    Conclusion: up to date`
				);
			}
		}
	}

	context.log( '[wireit] Done' );

	syncTsReferences( lockfile, context );

	return lockfile;
}

// Note: The hook function names are important. They are used by PNPM when determining what functions to call.
module.exports = {
	hooks: {
		afterAllResolved,
	},
};
