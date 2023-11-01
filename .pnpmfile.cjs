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
 *
 * @return {Object} The package file.
 */
function loadPackageFile( packagePath ) {
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
	packageFileCache[ packagePath ] = packageFile;

	fs.writeFileSync(
		path.join( packagePath, 'package.json' ),
		JSON.stringify( packageFile, null, '\t' ),
		'utf8'
	);
}

/**
 * Gets the outputs for a given package.
 *
 * @param {string} packageFile The package file to read file outputs from.
 *
 * @return {Array.<string>} The globs describing the package's files.
 */
function getPackageOutputs( packageFile ) {
	// All of the outputs should be relative to the package's path instead of the monorepo root.
	// This is how wireit expects the files to be configured.
	const basePath = path.join( 'node_modules', packageFile.name );

	// We're going to construct the package outputs according to the same rules that NPM follows when packaging.
	const packageOutputs = [];

	// Packages that explicitly declare their outputs have made this easy for us.
	if ( packageFile.files ) {
		// We're going to make the glob relative to the package directory instead of the dependency directory.
		// To do this though, we need to transform the path a little bit.
		for ( const fileGlob of packageFile.files ) {
			let relativeGlob = fileGlob;

			// Negation globs need to move the exclamation point to the beginning of the output glob.
			let negation = relativeGlob.startsWith( '!' ) ? '!' : '';
			if ( negation ) {
				relativeGlob = relativeGlob.substring( 1 );
			}

			// Normalize leading slashes.
			if ( relativeGlob.startsWith( '/' ) ) {
				relativeGlob = relativeGlob.substring( 1 );
			}

			// Now we can construct a glob relative to the package directory.
			packageOutputs.push( `${ negation }${ basePath }/${ relativeGlob }` );
		}
	} else {
		// This is a VERY heavy-handed approach and will simply include every file in the package directory.
		packageOutputs.push( `${ basePath }/**/*` );

		// We can make this a little bit smarter by ignoring some common directories.
		packageOutputs.push( `!${ basePath }/node_modules` );
		packageOutputs.push( `!${ basePath }/.git` );
		packageOutputs.push( `!${ basePath }/.svn` );
		packageOutputs.push( `!${ basePath }/src` ); // We generally name our source directories "src" and don't need source files.
	}

	return packageOutputs;
}

/**
 * Checks to see if a package is linked and returns the path if it is.
 *
 * @param {string} packagePath The path to the package we're checking.
 * @param {string} lockVersion The package version from the lock file.
 *
 * @return {string|false} Returns the linked package path or false if the package is not linked.
 */
function isLinkedPackage( packagePath, lockVersion ) {
	// We can parse the version that PNPM stores in order to get the relative path to the package.
	// file: dependencies use a relative path with dependencies listed in parentheses after it.
	// workspace: dependencies just store the relative path from the package itself.
	const match = lockVersion.match( /^(?:file:|link:)([^<>:"|?*()]+)/i );
	if ( ! match ) {
		return false;
	}

	let relativePath = match[ 1 ];

	// Linked paths are relative to the package instead of the monorepo.
	if ( lockVersion.startsWith( 'link:' ) ) {
		relativePath = path.join( packagePath, relativePath );
	}

	return relativePath;
}

/**
 * Gets the paths to any packages linked in the lock file.
 *
 * @param {string} packagePath The path to the package to check.
 * @param {Object} lockPackage The package information from the lock file.
 *
 * @return {Array.<Object>} The linked package file keyed by the relative path to the package.
 */
function getLinkedPackages( packagePath, lockPackage ) {
	// Include both the dependencies and devDependencies in the list of packages to check.
	const possiblePackages = Object.assign(
		{},
		lockPackage.dependencies || {},
		lockPackage.devDependencies || {}
	);

	// We need to check all of the possible packages and figure out whether or not they're linked.
	const linkedPackages = {};
	for ( const packageName in possiblePackages ) {
		const linkedPackagePath = isLinkedPackage(
			packagePath,
			possiblePackages[ packageName ]
		);
		if ( ! linkedPackagePath ) {
			continue;
		}

		// Load the linked package file and mark it as a dependency.
		linkedPackages[ linkedPackagePath ] =
			loadPackageFile( linkedPackagePath );
	}

	return Object.values( linkedPackages );
}

/**
 * Hooks up all of the dependency outputs as file dependencies for wireit to fingerprint them.
 *
 * @param {Object.<string, Object>} lockPackages The paths to all of the packages we're processing.
 * @param {Object}                  context      The hook context object.
 * @param {Function.<string>}       context.log  Logs a message to the console.
 */
function updateWireitDependencies( lockPackages, context ) {
	context.log( '[wireit] Updating Dependency Lists' );

	// Rather than using wireit for task orchestration we are going to rely on PNPM in order to provide a more consistent developer experience.
	// In order to achieve this, however, we need to make sure that all of the dependencies are included in the fingerprint. If we don't, then
	// changes in dependency packages won't invalidate the cache and downstream packages won't be rebuilt unless they themselves change. This
	// is problematic because it means that we can't rely on the cache to be up to date and we'll have to rebuild everything every time.
	for ( const packagePath in lockPackages ) {
		const packageFile = loadPackageFile( packagePath );

		// We only care about packages using wireit.
		if ( ! packageFile.wireit ) {
			continue;
		}

		context.log( `[wireit][${ packageFile.name }] Updating Configuration` );

		// Only the packages that are linked need to be considered. The packages installed from the
		// registry are already included in the fingerprint by their very nature. If they are
		// changed then the lock file will be updated and the fingerprint will change too.
		const linkedPackages = getLinkedPackages(
			packagePath,
			lockPackages[ packagePath ]
		);

		// In order to make maintaining the list easy we use a wireit-only script named "dependencies" to keep the list up to date.
		// This is an automatically generated script and that we own and so we should make sure it's always as-expected.
		packageFile.wireit.dependencyOutputs = {
			// This is needed so we can reference files in `node_modules`.
			allowUsuallyExcludedPaths: true,

			// The files list will include globs for dependency files that we should fingerprint.
			files: [],
		};

		// We're going to spin through all of the dependencies for the package and add
		// their outputs to the list. We can then use these are file dependencies for
		// wireit and it will fingerprint them for us.
		for ( const linkedPackage of linkedPackages ) {
			const packageOutputs = getPackageOutputs( linkedPackage );
			packageFile.wireit.dependencyOutputs.files.push( ...packageOutputs );

			context.log(
				`[wireit][${ packageFile.name }] Added '${ linkedPackage.name }' Outputs`
			);
		}
		updatePackageFile( packagePath, packageFile );
	}

	context.log( '[wireit] Done' );
}

/**
 * This hook allows for the mutation of the lockfile before it is serialized.
 *
 * @param {Object}                  lockfile                 The lock file that was produced by PNPM.
 * @param {string}                  lockfile.lockfileVersion The version of the lock file spec.
 * @param {Object.<string, Object>} lockfile.importers       The packages in the workspace that are included in the lock file, keyed by the relative path to the package.
 * @param {Object}                  context                  The hook context object.
 * @param {Function.<string>}       context.log              Logs a message to the console.
 *
 * @return {Object} lockfile The updated lockfile.
 */
function afterAllResolved( lockfile, context ) {
	updateWireitDependencies( lockfile.importers, context );
	return lockfile;
}

// Note: The hook function names are important. They are used by PNPM when determining what functions to call.
module.exports = {
	hooks: {
		afterAllResolved,
	},
};
