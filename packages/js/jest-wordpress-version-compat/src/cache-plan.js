'use strict';

function createCachePreparationPlan( {
	installedPackageVersions,
	packageVersions,
} ) {
	const cachePackages = Object.keys( packageVersions ).sort();
	const missingPackages = cachePackages.filter(
		( packageName ) =>
			installedPackageVersions[ packageName ] !==
			packageVersions[ packageName ]
	);

	return {
		cachePackages,
		missingPackages,
		packageVersions,
		shouldInstall: missingPackages.length > 0,
	};
}

module.exports = {
	createCachePreparationPlan,
};
