'use strict';

const path = require( 'node:path' );

const {
	getCurrentCacheManifestPackageVersions,
	readCacheManifest,
	writeCacheManifest,
} = require( './cache-manifest' );
const { createCachePreparationPlan } = require( './cache-plan' );
const { findUp, readJsonFile } = require( './file-utils' );
const { defaultPackageRegistry } = require( './package-registry' );
const { resolveRequestedPackages } = require( './package-selection' );

const CACHE_DIRECTORY_NAME = 'jest-wordpress-version-compat';

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

function getInstalledPackageVersions( packages, cacheDirectory ) {
	return packages.reduce( ( installedPackageVersions, packageName ) => {
		const version = getCachedPackageVersion( packageName, cacheDirectory );

		if ( version ) {
			installedPackageVersions[ packageName ] = version;
		}

		return installedPackageVersions;
	}, {} );
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
	registry = defaultPackageRegistry,
} = {} ) {
	const selectedPackages = resolveRequestedPackages( {
		wpVersion,
		packages,
		cwd,
	} );
	const cacheDirectory = getCacheDirectory( { wpVersion, cacheRoot, cwd } );

	if ( selectedPackages.length === 0 ) {
		return createPrepareResult( {
			cacheDirectory,
			cachePackages: [],
			selectedPackages,
			wpVersion,
		} );
	}

	const versionTarget = registry.resolveTarget( wpVersion );
	const cachedManifest = readCacheManifest( cacheDirectory );
	let packageVersions = getCurrentCacheManifestPackageVersions( {
		manifest: cachedManifest,
		selectedPackages,
		versionTarget,
		wpVersion,
	} );

	if ( ! packageVersions ) {
		packageVersions = registry.resolvePackageVersions( {
			packages: selectedPackages,
			versionTarget,
			wpVersion,
		} );
		writeCacheManifest( {
			cacheDirectory,
			packageVersions,
			selectedPackages,
			versionTarget,
			wpVersion,
		} );
	}

	const plan = createCachePreparationPlan( {
		installedPackageVersions: getInstalledPackageVersions(
			Object.keys( packageVersions ),
			cacheDirectory
		),
		packageVersions,
	} );

	if ( ! plan.shouldInstall ) {
		return createPrepareResult( {
			cacheDirectory,
			cachePackages: plan.cachePackages,
			selectedPackages,
			wpVersion,
		} );
	}

	logger.info?.(
		`Preparing ${ plan.missingPackages.length } @wordpress package(s) for WordPress ${ wpVersion }.`
	);

	registry.install( cacheDirectory );
	logger.info?.(
		`Downloaded ${ plan.missingPackages.length } @wordpress package(s) for WordPress ${ wpVersion }.`
	);

	return createPrepareResult( {
		cacheDirectory,
		cachePackages: plan.cachePackages,
		installedPackages: plan.missingPackages,
		selectedPackages,
		wpVersion,
	} );
}

module.exports = {
	prepare,
};
