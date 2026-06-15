'use strict';

const cache = require( './cache' );
const jestAdapter = require( './jest' );
const metadata = require( './metadata' );

module.exports = {
	...metadata,
	createJestWordPressDependencyMapper: jestAdapter.createJestModuleNameMapper,
	createJestModuleNameMapper: jestAdapter.createJestModuleNameMapper,
	getCacheDirectory: cache.getCacheDirectory,
	getCachedPackagePath: cache.getCachedPackagePath,
	prepareWordPressPackages: cache.prepare,
	withWordPressDependencyCompat: jestAdapter.withWordPressDependencyCompat,
};
