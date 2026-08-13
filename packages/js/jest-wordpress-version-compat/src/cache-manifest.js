'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );

const { readJsonFile, writeJsonFile } = require( './file-utils' );

const CACHE_SCHEMA_VERSION = 1;

function readCacheManifest( cacheDirectory ) {
	try {
		return readJsonFile( path.join( cacheDirectory, 'package.json' ) );
	} catch ( error ) {
		return undefined;
	}
}

function isPlainObject( value ) {
	return Boolean(
		value && typeof value === 'object' && ! Array.isArray( value )
	);
}

function areArraysEqual( first, second ) {
	return (
		Array.isArray( first ) &&
		Array.isArray( second ) &&
		first.length === second.length &&
		first.every( ( value, index ) => value === second[ index ] )
	);
}

function getCacheManifestPackageVersions( manifest ) {
	if (
		! isPlainObject( manifest?.dependencies ) ||
		! isPlainObject( manifest?.overrides )
	) {
		return undefined;
	}

	if (
		JSON.stringify( manifest.dependencies ) !==
		JSON.stringify( manifest.overrides )
	) {
		return undefined;
	}

	return manifest.dependencies;
}

function isCacheManifestCurrent( {
	manifest,
	selectedPackages,
	versionTarget,
	wpVersion,
} ) {
	const metadata = manifest?.wordpressVersionCompat;

	return (
		metadata?.schemaVersion === CACHE_SCHEMA_VERSION &&
		metadata?.target === wpVersion &&
		metadata?.version === versionTarget.version &&
		metadata?.distTag === versionTarget.distTag &&
		areArraysEqual( metadata?.selectedPackages, selectedPackages ) &&
		Boolean( getCacheManifestPackageVersions( manifest ) )
	);
}

function getCurrentCacheManifestPackageVersions( {
	manifest,
	selectedPackages,
	versionTarget,
	wpVersion,
} ) {
	if (
		! isCacheManifestCurrent( {
			manifest,
			selectedPackages,
			versionTarget,
			wpVersion,
		} )
	) {
		return undefined;
	}

	return getCacheManifestPackageVersions( manifest );
}

function createCacheManifestPackageJson( {
	packageVersions,
	selectedPackages,
	versionTarget,
	wpVersion,
} ) {
	return {
		private: true,
		name: `jest-wordpress-version-compat-cache-${ wpVersion }`,
		description:
			'Generated cache for @wordpress package compatibility tests.',
		wordpressVersionCompat: {
			schemaVersion: CACHE_SCHEMA_VERSION,
			target: wpVersion,
			version: versionTarget.version,
			distTag: versionTarget.distTag,
			source: versionTarget.source,
			selectedPackages,
		},
		dependencies: packageVersions,
		overrides: packageVersions,
	};
}

function writeCacheManifest( {
	cacheDirectory,
	packageVersions,
	selectedPackages,
	versionTarget,
	wpVersion,
} ) {
	fs.mkdirSync( cacheDirectory, { recursive: true } );

	const manifestPath = path.join( cacheDirectory, 'package.json' );
	const manifest = createCacheManifestPackageJson( {
		packageVersions,
		selectedPackages,
		versionTarget,
		wpVersion,
	} );

	if (
		fs.existsSync( manifestPath ) &&
		JSON.stringify( readJsonFile( manifestPath ) ) ===
			JSON.stringify( manifest )
	) {
		return;
	}

	writeJsonFile( manifestPath, manifest );
}

module.exports = {
	CACHE_SCHEMA_VERSION,
	createCacheManifestPackageJson,
	getCacheManifestPackageVersions,
	getCurrentCacheManifestPackageVersions,
	isCacheManifestCurrent,
	readCacheManifest,
	writeCacheManifest,
};
