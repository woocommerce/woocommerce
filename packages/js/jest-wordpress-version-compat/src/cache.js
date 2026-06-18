'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );

const { findUp, readJsonFile, writeJsonFile } = require( './file-utils' );
const { getNpmDistTagForWordPressVersion } = require( './metadata' );
const {
	installPackages,
	resolvePackageDependenciesFromNpm,
	resolvePackageVersionFromNpm,
	resolveWordPressDistTagFromNpm,
} = require( './npm' );
const {
	getWordPressDependencyNames,
	resolveRequestedPackages,
} = require( './package-selection' );

const CACHE_DIRECTORY_NAME = 'jest-wordpress-version-compat';
const latestWordPressDistTagCache = new Map();

function findWorkspaceRoot( cwd = process.cwd() ) {
	const workspaceFile = findUp( 'pnpm-workspace.yaml', cwd );

	if ( workspaceFile ) {
		return path.dirname( workspaceFile );
	}

	const packageFile = findUp( 'package.json', cwd );

	return packageFile ? path.dirname( packageFile ) : path.resolve( cwd );
}

function getDefaultCacheRoot( cwd = process.cwd() ) {
	return path.join(
		findWorkspaceRoot( cwd ),
		'node_modules',
		'.cache',
		CACHE_DIRECTORY_NAME
	);
}

function getSelectedWordPressVersion() {
	return process.env.WP_VERSION || 'latest';
}

function getCacheDirectory( {
	wpVersion = getSelectedWordPressVersion(),
	cacheRoot,
	cwd = process.cwd(),
} = {} ) {
	return path.join( cacheRoot || getDefaultCacheRoot( cwd ), wpVersion );
}

function getCachedPackagePath( packageName, cacheDirectory ) {
	return path.join(
		cacheDirectory,
		'node_modules',
		...packageName.split( '/' )
	);
}

function getCachedPackageVersion( packageName, cacheDirectory ) {
	try {
		return readJsonFile(
			path.join(
				getCachedPackagePath( packageName, cacheDirectory ),
				'package.json'
			)
		).version;
	} catch ( error ) {
		return undefined;
	}
}

function createCachePackageJson( wpVersion, packageVersions ) {
	return {
		private: true,
		name: `jest-wordpress-version-compat-cache-${ wpVersion }`,
		description:
			'Generated cache for @wordpress package compatibility tests.',
		dependencies: packageVersions,
		overrides: packageVersions,
	};
}

function writeCachePackageJson( cacheDirectory, wpVersion, packageVersions ) {
	fs.mkdirSync( cacheDirectory, { recursive: true } );

	const packageJsonPath = path.join( cacheDirectory, 'package.json' );
	const packageJson = createCachePackageJson( wpVersion, packageVersions );

	if (
		fs.existsSync( packageJsonPath ) &&
		JSON.stringify( readJsonFile( packageJsonPath ) ) ===
			JSON.stringify( packageJson )
	) {
		return;
	}

	writeJsonFile( packageJsonPath, packageJson );
}

function createCacheContext( wpVersion, cacheDirectory ) {
	return {
		cacheDirectory,
		wpVersion,
	};
}

function resolveNpmDistTag( packageName, context ) {
	const requestedDistTag = getNpmDistTagForWordPressVersion(
		context.wpVersion
	);

	if ( requestedDistTag === 'latest' ) {
		return requestedDistTag;
	}

	const cacheKey = [
		context.cacheDirectory,
		packageName,
		requestedDistTag,
	].join( ':' );

	if ( latestWordPressDistTagCache.has( cacheKey ) ) {
		return latestWordPressDistTagCache.get( cacheKey );
	}

	const offset = requestedDistTag === 'wp-latest-1' ? 1 : 0;
	const resolvedDistTag = resolveWordPressDistTagFromNpm(
		packageName,
		offset
	);

	latestWordPressDistTagCache.set( cacheKey, resolvedDistTag );

	return resolvedDistTag;
}

function resolvePackageVersion( packageName, context ) {
	const distTag = resolveNpmDistTag( packageName, context );

	return resolvePackageVersionFromNpm(
		packageName,
		context.wpVersion,
		distTag
	);
}

function getPackageVersions( packages, context ) {
	return packages.reduce( ( packageVersions, packageName ) => {
		packageVersions[ packageName ] = resolvePackageVersion(
			packageName,
			context
		);

		return packageVersions;
	}, {} );
}

function resolvePackageDependencies( packageName, version ) {
	return resolvePackageDependenciesFromNpm( packageName, version );
}

function resolveWordPressPackageDependencyClosure( packages, context ) {
	const resolvedPackages = new Set( packages );
	const packageQueue = [ ...packages ];

	for ( let index = 0; index < packageQueue.length; index++ ) {
		const packageName = packageQueue[ index ];
		const packageVersion = resolvePackageVersion( packageName, context );
		const dependencyNames = getWordPressDependencyNames(
			resolvePackageDependencies( packageName, packageVersion )
		);

		for ( const dependencyName of dependencyNames ) {
			if ( resolvedPackages.has( dependencyName ) ) {
				continue;
			}

			resolvedPackages.add( dependencyName );
			packageQueue.push( dependencyName );
		}
	}

	return [ ...resolvedPackages ].sort();
}

function getCachedPackageMismatches(
	packages,
	packageVersions,
	cacheDirectory
) {
	return packages.filter( ( packageName ) => {
		return (
			getCachedPackageVersion( packageName, cacheDirectory ) !==
			packageVersions[ packageName ]
		);
	} );
}

function getPackagePaths( packages, cacheDirectory ) {
	return packages.reduce( ( packagePaths, packageName ) => {
		packagePaths[ packageName ] = getCachedPackagePath(
			packageName,
			cacheDirectory
		);

		return packagePaths;
	}, {} );
}

function createPrepareResult( {
	cacheDirectory,
	cachePackages,
	installedPackages = [],
	selectedPackages,
	wpVersion,
} ) {
	return {
		cachePackages,
		cacheDirectory,
		installedPackages,
		packagePaths: getPackagePaths( cachePackages, cacheDirectory ),
		packages: selectedPackages,
		wpVersion,
	};
}

function prepare( {
	wpVersion = getSelectedWordPressVersion(),
	packages,
	cwd = process.cwd(),
	cacheRoot,
	logger = console,
} = {} ) {
	const selectedPackages = resolveRequestedPackages( {
		wpVersion,
		packages,
		cwd,
	} );
	const cacheDirectory = getCacheDirectory( { wpVersion, cacheRoot, cwd } );
	const context = createCacheContext( wpVersion, cacheDirectory );
	const cachePackages = resolveWordPressPackageDependencyClosure(
		selectedPackages,
		context
	);
	const packageVersions = getPackageVersions( cachePackages, context );
	const missingPackages = getCachedPackageMismatches(
		cachePackages,
		packageVersions,
		cacheDirectory
	);

	if ( cachePackages.length > 0 ) {
		writeCachePackageJson( cacheDirectory, wpVersion, packageVersions );
	}

	if ( missingPackages.length === 0 ) {
		return createPrepareResult( {
			cacheDirectory,
			cachePackages,
			selectedPackages,
			wpVersion,
		} );
	}

	logger.info?.(
		`Preparing ${ missingPackages.length } @wordpress package(s) for WordPress ${ wpVersion }.`
	);

	installPackages( cacheDirectory );

	return createPrepareResult( {
		cacheDirectory,
		cachePackages,
		installedPackages: missingPackages,
		selectedPackages,
		wpVersion,
	} );
}

module.exports = {
	prepare,
};
