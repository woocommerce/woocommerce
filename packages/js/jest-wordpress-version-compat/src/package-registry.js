'use strict';

const {
	installPackages,
	resolvePackageDependenciesFromNpm,
	resolvePackageVersionFromNpm,
} = require( './npm' );
const { getWordPressDependencyNames } = require( './package-selection' );
const { resolveWordPressVersionTarget } = require( './wordpress-org' );

function resolvePackageVersion( packageName, context ) {
	if ( context.packageVersions.has( packageName ) ) {
		return context.packageVersions.get( packageName );
	}

	const packageVersion = context.resolvePackageVersion(
		packageName,
		context.wpVersion,
		context.versionTarget.distTag
	);

	context.packageVersions.set( packageName, packageVersion );

	return packageVersion;
}

function resolvePackageVersionsWithDependencies( packages, context ) {
	const resolvedPackages = new Set( packages );
	const packageQueue = [ ...packages ];

	for ( let index = 0; index < packageQueue.length; index++ ) {
		const packageName = packageQueue[ index ];
		const packageVersion = resolvePackageVersion( packageName, context );
		const dependencyNames = getWordPressDependencyNames(
			context.resolvePackageDependencies( packageName, packageVersion )
		);

		for ( const dependencyName of dependencyNames ) {
			if ( resolvedPackages.has( dependencyName ) ) {
				continue;
			}

			resolvedPackages.add( dependencyName );
			packageQueue.push( dependencyName );
		}
	}

	return [ ...resolvedPackages ].sort().reduce( ( packageVersions, name ) => {
		packageVersions[ name ] = resolvePackageVersion( name, context );

		return packageVersions;
	}, {} );
}

function createPackageRegistry( {
	install = installPackages,
	resolvePackageDependencies = resolvePackageDependenciesFromNpm,
	resolvePackageVersion:
		resolvePackageVersionFromRegistry = resolvePackageVersionFromNpm,
	resolveTarget = resolveWordPressVersionTarget,
} = {} ) {
	return {
		install,
		resolvePackageVersions( { packages, wpVersion, versionTarget } ) {
			return resolvePackageVersionsWithDependencies( packages, {
				packageVersions: new Map(),
				resolvePackageDependencies,
				resolvePackageVersion: resolvePackageVersionFromRegistry,
				versionTarget,
				wpVersion,
			} );
		},
		resolveTarget,
	};
}

const defaultPackageRegistry = createPackageRegistry();

module.exports = {
	createPackageRegistry,
	defaultPackageRegistry,
};
