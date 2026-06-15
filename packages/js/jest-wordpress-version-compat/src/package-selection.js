'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );

const {
	getNpmDistTagForWordPressVersion,
	isBundledPackage,
	isWordPressPackage,
} = require( './metadata' );

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

function readJsonFile( filePath ) {
	return JSON.parse( fs.readFileSync( filePath, 'utf8' ) );
}

function findProjectPackageJson( cwd = process.cwd() ) {
	const packageFile = findUp( 'package.json', cwd );

	return packageFile ? readJsonFile( packageFile ) : {};
}

function getPackageJsonDependencyEntries(
	packageJson,
	dependencySections = [
		'dependencies',
		'devDependencies',
		'peerDependencies',
		'optionalDependencies',
	]
) {
	return dependencySections.flatMap( ( section ) =>
		Object.entries( packageJson[ section ] || {} )
	);
}

function getPackageJsonWordPressDependencies(
	packageJson,
	dependencySections
) {
	const packages = new Set();

	for ( const [ packageName, versionSpec ] of getPackageJsonDependencyEntries(
		packageJson,
		dependencySections
	) ) {
		if (
			isWordPressPackage( packageName ) &&
			! isBundledPackage( packageName ) &&
			String( versionSpec ).startsWith( 'catalog:wp-' )
		) {
			packages.add( packageName );
		}
	}

	return [ ...packages ].sort();
}

function getNodeModuleLookupPaths( cwd = process.cwd() ) {
	const lookupPaths = [];
	let currentDirectory = path.resolve( cwd );

	while ( true ) {
		lookupPaths.push( path.join( currentDirectory, 'node_modules' ) );

		const parentDirectory = path.dirname( currentDirectory );

		if ( parentDirectory === currentDirectory ) {
			return lookupPaths;
		}

		currentDirectory = parentDirectory;
	}
}

function resolvePackageJsonPath( packageName, cwd = process.cwd() ) {
	for ( const nodeModulesPath of getNodeModuleLookupPaths( cwd ) ) {
		const packageJsonPath = path.join(
			nodeModulesPath,
			...packageName.split( '/' ),
			'package.json'
		);

		if ( fs.existsSync( packageJsonPath ) ) {
			return fs.realpathSync( packageJsonPath );
		}
	}

	return undefined;
}

function getWorkspaceDependencyNames( packageJson ) {
	return getPackageJsonDependencyEntries( packageJson, [
		'dependencies',
		'peerDependencies',
		'optionalDependencies',
	] )
		.filter( ( [ packageName, versionSpec ] ) =>
			packageName.startsWith( '@woocommerce/' ) &&
			String( versionSpec ).startsWith( 'workspace:' )
		)
		.map( ( [ packageName ] ) => packageName )
		.sort();
}

function getWorkspaceWordPressDependencies( packageJson, cwd = process.cwd() ) {
	const packages = new Set();
	const checkedWorkspacePackages = new Set();
	const packageQueue = getWorkspaceDependencyNames( packageJson ).map(
		( packageName ) => ( { packageName, cwd } )
	);

	for ( let index = 0; index < packageQueue.length; index++ ) {
		const { packageName, cwd: packageCwd } = packageQueue[ index ];

		if ( checkedWorkspacePackages.has( packageName ) ) {
			continue;
		}

		checkedWorkspacePackages.add( packageName );

		const packageJsonPath = resolvePackageJsonPath(
			packageName,
			packageCwd
		);

		if ( ! packageJsonPath ) {
			continue;
		}

		const workspacePackageJson = readJsonFile( packageJsonPath );

		for ( const wordpressPackageName of getPackageJsonWordPressDependencies(
			workspacePackageJson,
			[ 'dependencies', 'peerDependencies', 'optionalDependencies' ]
		) ) {
			packages.add( wordpressPackageName );
		}

		for ( const dependencyName of getWorkspaceDependencyNames(
			workspacePackageJson
		) ) {
			packageQueue.push( {
				packageName: dependencyName,
				cwd: path.dirname( packageJsonPath ),
			} );
		}
	}

	return [ ...packages ].sort();
}

function getWordPressDependencyNames( dependencies = {} ) {
	return Object.keys( dependencies )
		.filter( isWordPressPackage )
		.filter( ( packageName ) => ! isBundledPackage( packageName ) )
		.sort();
}

function normalizePackageList( packages ) {
	if ( ! packages ) {
		return undefined;
	}

	const packageList = Array.isArray( packages ) ? packages : [ packages ];

	return packageList
		.flatMap( ( packageName ) => String( packageName ).split( ',' ) )
		.map( ( packageName ) => packageName.trim() )
		.filter( Boolean )
		.filter( isWordPressPackage )
		.filter( ( packageName ) => ! isBundledPackage( packageName ) )
		.sort();
}

function resolveRequestedPackages( {
	wpVersion,
	packages,
	cwd = process.cwd(),
} = {} ) {
	getNpmDistTagForWordPressVersion( wpVersion );
	const packageJson = findProjectPackageJson( cwd );
	const configuredPackages = normalizePackageList( packages );

	if ( configuredPackages ) {
		return configuredPackages;
	}

	return [
		...new Set( [
			...getPackageJsonWordPressDependencies( packageJson ),
			...getWorkspaceWordPressDependencies( packageJson, cwd ),
		] ),
	].sort();
}

module.exports = {
	getWordPressDependencyNames,
	resolveRequestedPackages,
};
