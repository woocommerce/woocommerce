'use strict';

const {
	getCachedPackagePath,
	getSelectedWordPressVersion,
	prepare,
	resolveRequestedPackages,
} = require( './cache' );

function createJestModuleNameMapper( {
	wpVersion = getSelectedWordPressVersion(),
	packages,
	cwd = process.cwd(),
	cacheRoot,
	lazy = true,
	all = false,
} = {} ) {
	const selectedPackages = resolveRequestedPackages( {
		wpVersion,
		packages,
		cwd,
		all,
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
		return Object.entries( preparedCache.packagePaths ).reduce(
			( moduleNameMapper, [ packageName, packagePath ] ) => {
				moduleNameMapper[ `^${ packageName }$` ] = packagePath;

				return moduleNameMapper;
			},
			{}
		);
	}

	return selectedPackages.reduce( ( moduleNameMapper, packageName ) => {
		moduleNameMapper[ `^${ packageName }$` ] = getCachedPackagePath(
			packageName,
			{
				wpVersion,
				cacheRoot,
				cwd,
			}
		);

		return moduleNameMapper;
	}, {} );
}

function withWordPressDependencyCompat( jestConfig = {}, options = {} ) {
	return {
		...jestConfig,
		moduleNameMapper: {
			...( jestConfig.moduleNameMapper || {} ),
			...createJestModuleNameMapper( options ),
		},
	};
}

module.exports = {
	createJestModuleNameMapper,
	withWordPressDependencyCompat,
};
