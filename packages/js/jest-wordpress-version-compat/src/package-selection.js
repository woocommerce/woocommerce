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

function getPackageJsonWordPressDependencies( packageJson ) {
	const dependencySections = [
		'dependencies',
		'devDependencies',
		'peerDependencies',
		'optionalDependencies',
	];
	const packages = new Set();

	for ( const section of dependencySections ) {
		for ( const [ packageName, versionSpec ] of Object.entries(
			packageJson[ section ] || {}
		) ) {
			if (
				isWordPressPackage( packageName ) &&
				! isBundledPackage( packageName ) &&
				String( versionSpec ).startsWith( 'catalog:wp-' )
			) {
				packages.add( packageName );
			}
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

	return (
		normalizePackageList( packages ) ||
		getPackageJsonWordPressDependencies( findProjectPackageJson( cwd ) )
	);
}

module.exports = {
	getWordPressDependencyNames,
	resolveRequestedPackages,
};
