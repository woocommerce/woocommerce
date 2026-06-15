'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );

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

function findUp( fileName, startDirectory = process.cwd() ) {
	let currentDirectory = path.resolve( startDirectory );

	while ( true ) {
		const candidate = path.join( currentDirectory, fileName );

		if ( fs.existsSync( candidate ) ) {
			return candidate;
		}

		const parentDirectory = path.dirname( currentDirectory );

		if ( parentDirectory === currentDirectory ) {
			return undefined;
		}

		currentDirectory = parentDirectory;
	}
}

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

function readJsonFile( filePath ) {
	return JSON.parse( fs.readFileSync( filePath, 'utf8' ) );
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

function ensureCachePackageJson( cacheDirectory, wpVersion ) {
	fs.mkdirSync( cacheDirectory, { recursive: true } );

	const packageJsonPath = path.join( cacheDirectory, 'package.json' );

	if ( fs.existsSync( packageJsonPath ) ) {
		return;
	}

	fs.writeFileSync(
		packageJsonPath,
		JSON.stringify(
			{
				private: true,
				name: `jest-wordpress-version-compat-cache-${ wpVersion }`,
				description:
					'Generated cache for @wordpress package compatibility tests.',
			},
			null,
			2
		) + '\n'
	);
}

function getResolvedVersionsPath( cacheDirectory ) {
	return path.join( cacheDirectory, 'resolved-versions.json' );
}

function readResolvedVersions( cacheDirectory ) {
	const resolvedVersionsPath = getResolvedVersionsPath( cacheDirectory );

	if ( ! fs.existsSync( resolvedVersionsPath ) ) {
		return {};
	}

	return readJsonFile( resolvedVersionsPath );
}

function writeResolvedVersions( cacheDirectory, resolvedVersions ) {
	fs.mkdirSync( cacheDirectory, { recursive: true } );

	fs.writeFileSync(
		getResolvedVersionsPath( cacheDirectory ),
		JSON.stringify( resolvedVersions, null, 2 ) + '\n'
	);
}

function createCacheContext( wpVersion, cacheDirectory ) {
	return {
		cacheDirectory,
		hasResolvedVersionChanges: false,
		resolvedVersions: readResolvedVersions( cacheDirectory ),
		wpVersion,
	};
}

function setResolvedVersionValue( context, key, value ) {
	context.resolvedVersions[ key ] = value;
	context.hasResolvedVersionChanges = true;
}

function setResolvedDistTag( context, packageName, distTag ) {
	context.resolvedVersions.__distTags = {
		...( context.resolvedVersions.__distTags || {} ),
		[ packageName ]: distTag,
	};
	context.hasResolvedVersionChanges = true;
}

function setResolvedDependencies( context, packageName, version, dependencies ) {
	context.resolvedVersions.__dependencies = {
		...( context.resolvedVersions.__dependencies || {} ),
		[ packageName ]: {
			version,
			dependencies,
		},
	};
	context.hasResolvedVersionChanges = true;
}

function flushResolvedVersions( context ) {
	if ( ! context.hasResolvedVersionChanges ) {
		return;
	}

	writeResolvedVersions( context.cacheDirectory, context.resolvedVersions );
	context.hasResolvedVersionChanges = false;
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
	const requestedDistTag = getNpmDistTagForWordPressVersion(
		context.wpVersion
	);
	const isDynamicWordPressTarget = requestedDistTag.startsWith( 'wp-latest' );
	const distTag = resolveNpmDistTag( packageName, context );
	const cachedDistTag = context.resolvedVersions.__distTags?.[ packageName ];
	const cachedVersion = context.resolvedVersions[ packageName ];

	if (
		cachedVersion &&
		( ! isDynamicWordPressTarget || cachedDistTag === distTag )
	) {
		return cachedVersion;
	}

	const packageVersion = resolvePackageVersionFromNpm(
		packageName,
		context.wpVersion,
		distTag
	);

	if ( isDynamicWordPressTarget ) {
		setResolvedDistTag( context, packageName, distTag );
	}

	setResolvedVersionValue( context, packageName, packageVersion );

	return packageVersion;
}

function resolvePackageDependencies( packageName, version, context ) {
	const cachedDependencies =
		context.resolvedVersions.__dependencies?.[ packageName ];

	if (
		cachedDependencies?.version === version &&
		cachedDependencies.dependencies &&
		typeof cachedDependencies.dependencies === 'object'
	) {
		return cachedDependencies.dependencies;
	}

	const dependencies = resolvePackageDependenciesFromNpm(
		packageName,
		version
	);

	setResolvedDependencies( context, packageName, version, dependencies );

	return dependencies;
}

function resolveWordPressPackageDependencyClosure( packages, context ) {
	const resolvedPackages = new Set( packages );
	const packageQueue = [ ...packages ];

	for ( let index = 0; index < packageQueue.length; index++ ) {
		const packageName = packageQueue[ index ];
		const packageVersion = resolvePackageVersion( packageName, context );
		const dependencyNames = getWordPressDependencyNames(
			resolvePackageDependencies( packageName, packageVersion, context )
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

function getCachedPackageMismatches( packages, context ) {
	return packages.filter( ( packageName ) => {
		const targetVersion = resolvePackageVersion( packageName, context );

		return (
			getCachedPackageVersion( packageName, context.cacheDirectory ) !==
			targetVersion
		);
	} );
}

function toPackageSpec( packageName, context ) {
	return `${ packageName }@${ resolvePackageVersion(
		packageName,
		context
	) }`;
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
	offline = process.env.WP_JEST_DEPENDENCY_COMPAT_OFFLINE === '1',
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
	const missingPackages = getCachedPackageMismatches(
		cachePackages,
		context
	);

	flushResolvedVersions( context );

	if ( missingPackages.length === 0 ) {
		return createPrepareResult( {
			cacheDirectory,
			cachePackages,
			selectedPackages,
			wpVersion,
		} );
	}

	if ( offline ) {
		throw new Error(
			`Missing or stale cached @wordpress packages for WordPress ${ wpVersion }: ${ missingPackages.join(
				', '
			) }. Unset WP_JEST_DEPENDENCY_COMPAT_OFFLINE or run the compatibility test once with network access before using offline mode.`
		);
	}

	ensureCachePackageJson( cacheDirectory, wpVersion );

	const packageSpecs = missingPackages.map( ( packageName ) =>
		toPackageSpec( packageName, context )
	);

	logger.info?.(
		`Preparing ${ missingPackages.length } @wordpress package(s) for WordPress ${ wpVersion }.`
	);

	installPackages( packageSpecs, cacheDirectory );

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
