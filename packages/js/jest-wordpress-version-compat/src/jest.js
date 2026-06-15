'use strict';

const {
	getCachedPackagePath,
	getSelectedWordPressVersion,
	prepare,
	resolveRequestedPackages,
} = require( './cache' );

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

function createSingletonModuleNameMapper( {
	wpVersion = getSelectedWordPressVersion(),
	cacheRoot,
	cwd = process.cwd(),
} = {} ) {
	return singletonPackages.reduce( ( moduleNameMapper, packageName ) => {
		const packagePath = getCachedPackagePath( packageName, {
			wpVersion,
			cacheRoot,
			cwd,
		} );

		moduleNameMapper[ `^${ packageName }$` ] = packagePath;
		moduleNameMapper[ `^${ packageName }/(.*?)(?:\\.js)?$` ] =
			`${ packagePath }/$1.js`;

		return moduleNameMapper;
	}, {} );
}

function createJestModuleNameMapper( {
	wpVersion = getSelectedWordPressVersion(),
	packages,
	cwd = process.cwd(),
	cacheRoot,
	lazy = true,
} = {} ) {
	const selectedPackages = resolveRequestedPackages( {
		wpVersion,
		packages,
		cwd,
	} );

	const preparedCache = lazy
		? prepare( {
				wpVersion,
				packages: selectedPackages,
				cwd,
				cacheRoot,
		  } )
		: undefined;

	if ( preparedCache?.packagePaths ) {
		return {
			...Object.entries( preparedCache.packagePaths ).reduce(
				( moduleNameMapper, [ packageName, packagePath ] ) => {
					moduleNameMapper[ `^${ packageName }$` ] = packagePath;

					return moduleNameMapper;
				},
				{}
			),
			...createSingletonModuleNameMapper( { wpVersion, cacheRoot, cwd } ),
		};
	}

	return {
		...selectedPackages.reduce( ( moduleNameMapper, packageName ) => {
			moduleNameMapper[ `^${ packageName }$` ] = getCachedPackagePath(
				packageName,
				{
					wpVersion,
					cacheRoot,
					cwd,
				}
			);

			return moduleNameMapper;
		}, {} ),
		...createSingletonModuleNameMapper( { wpVersion, cacheRoot, cwd } ),
	};
}

function withWordPressDependencyCompat( jestConfig = {}, options = {} ) {
	return {
		...jestConfig,
		moduleNameMapper: {
			...removeSingletonModuleNameMapperEntries(
				jestConfig.moduleNameMapper
			),
			...createJestModuleNameMapper( options ),
		},
	};
}

module.exports = {
	createJestModuleNameMapper,
	withWordPressDependencyCompat,
};
