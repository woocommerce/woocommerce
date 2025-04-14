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
		JSON.stringify( packageFile, null, '\t' ) + "\n",
		'utf8'
	);
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
function updateConfig( packageName, packagePath, declaredDependencies, resolvedDependencies, config, context ) {
	for ( const [ key, value ] of Object.entries( declaredDependencies ) ) {
		if ( value.startsWith( 'workspace:' ) ) {
			const normalizedPath = path.join( packagePath, resolvedDependencies[key].replace( 'link:', '' ) );
			context.log( `[wireit][${ packageName }]    Inspecting workspace dependency: ${ key } (${ normalizedPath })` );

			// Actualize output storage with the identified entries.
			const dependencyFile = loadPackageFile( normalizedPath );
			if ( dependencyFile.files ) {
				for ( const entry in dependencyFile.files ) {
					const entryValue = dependencyFile.files[entry];
					let normalizedValue;
					if ( entryValue.startsWith( '!' ) ) {
						normalizedValue = '!' + path.join( 'node_modules', key, entryValue.substring( 1 ) );
					} else {
						normalizedValue = path.join( 'node_modules', key, entryValue );
					}
					config.files.push( normalizedValue );

					context.log( `[wireit][${ packageName }]        - ${ normalizedValue }` );
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
			context.log( `[wireit][${ packageFile.name }] Verifying 'wireit.dependencyOutputs'` );

			// Initialize outputs storage and hash it's original state.
			const config              = {
				allowUsuallyExcludedPaths: true, // This is needed so we can reference files in `node_modules`.
				files: [ "package.json" ],       // The files list will include globs for dependency files that we should fingerprint.
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
					...( lockfile.importers[ packagePath ].devDependencies || {} ),
				},
				config,
				context
			);

			// Verify config state and update manifest on mismatch.
			let updated = false;
			const newConfigState = JSON.stringify( config );
			if ( newConfigState !== originalConfigState ) {
				const loadedConfigState = JSON.stringify( packageFile.wireit?.dependencyOutputs || {} );
				if ( newConfigState !== loadedConfigState ) {
					context.log( `[wireit][${ packageFile.name }]    Conclusion: outdated, updating 'wireit.dependencyOutputs'` );

					packageFile.wireit.dependencyOutputs = config;
					updatePackageFile( packagePath, packageFile );
					updated = true;
				}
			}
			if ( ! updated ) {
				context.log( `[wireit][${ packageFile.name }]    Conclusion: up to date` );
			}
		}
	}

	context.log( '[wireit] Done' );

	return lockfile;
}

// Note: The hook function names are important. They are used by PNPM when determining what functions to call.
module.exports = {
	hooks: {
		afterAllResolved,
	},
};
