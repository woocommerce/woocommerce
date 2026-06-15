'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );

const { prepare } = require( './cache' );
const { createCachedTransformConfig } = require( './jest-transform' );

const singletonPackages = [ 'react', 'react-dom' ];

function isSingletonModuleNameMapperPattern( pattern ) {
	return singletonPackages.some(
		( packageName ) =>
			pattern === `^${ packageName }$` ||
			pattern.startsWith( `^${ packageName }/` )
	);
}

function removeSingletonModuleNameMapperEntries( moduleNameMapper = {} ) {
	return Object.fromEntries(
		Object.entries( moduleNameMapper ).filter(
			( [ pattern ] ) => ! isSingletonModuleNameMapperPattern( pattern )
		)
	);
}

// WordPress packages can depend on React singletons. When a compatibility cache
// installs React, keep React and React DOM imports pinned to that same cache so
// JSX runtimes and hooks do not resolve to a second copy. Skip the mapping when
// the cache does not contain React, otherwise non-React packages would point
// subpath imports like react/jsx-runtime at files that were never installed.
function getCachedPackagePath( packageName, cacheDirectory ) {
	return path.join(
		cacheDirectory,
		'node_modules',
		...packageName.split( '/' )
	);
}

function createSingletonModuleNameMapper( { cacheDirectory } ) {
	return singletonPackages.reduce( ( moduleNameMapper, packageName ) => {
		const packagePath = getCachedPackagePath( packageName, cacheDirectory );

		if ( ! fs.existsSync( path.join( packagePath, 'package.json' ) ) ) {
			return moduleNameMapper;
		}

		moduleNameMapper[ `^${ packageName }$` ] = packagePath;
		moduleNameMapper[
			`^${ packageName }/(.*?)(?:\\.js)?$`
		] = `${ packagePath }/$1.js`;

		return moduleNameMapper;
	}, {} );
}

function createJestModuleNameMapper( options = {} ) {
	return {
		...Object.entries( options.packagePaths ).reduce(
			( moduleNameMapper, [ packageName, packagePath ] ) => {
				moduleNameMapper[ `^${ packageName }$` ] = packagePath;

				return moduleNameMapper;
			},
			{}
		),
		...createSingletonModuleNameMapper( options ),
	};
}

function withWordPressDependencyCompat( jestConfig = {}, options = {} ) {
	const preparedCache = prepare( options );
	const cachedTransformConfig = createCachedTransformConfig( {
		cacheDirectory: preparedCache.cacheDirectory,
		transform: jestConfig.transform,
		transformIgnorePatterns: jestConfig.transformIgnorePatterns,
		cwd: options.cwd,
	} );

	return {
		...jestConfig,
		moduleNameMapper: {
			...removeSingletonModuleNameMapperEntries(
				jestConfig.moduleNameMapper
			),
			...createJestModuleNameMapper( preparedCache ),
		},
		...cachedTransformConfig,
	};
}

module.exports = {
	withWordPressDependencyCompat,
};
