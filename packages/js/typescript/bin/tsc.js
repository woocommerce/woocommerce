#!/usr/bin/env node

// Roughly:

// 1. The package consumer should extend the base config defined in our package
// 2. This script will load their extended config, it will make paths absolute to the package root
// 3. Then it will run tsc with the new config that includes the absolute paths

const path = require( 'path' );
const fs = require( 'fs' );
const { spawnSync } = require( 'child_process' );

const projectRoot = path.join( process.cwd() );
const tsConfigPath = path.join( projectRoot, 'tsconfig.json' );

//  Fail if tsconfig.json is not found at project root.
if ( ! fs.existsSync( tsConfigPath ) ) {
	console.error(
		'tsconfig.json not found, you must provide a tsconfig.json at your project root.'
	);
	process.exit( 1 );
}

// Now load the config
const tsConfig = require( tsConfigPath );
const baseConfig = require( '../base.json' );

// Merge the base config with the consumer's config
const finalConfig = {
	...baseConfig,
	...tsConfig,
};

console.log(
	'Making absolute paths to tsconfig.json for outDir, outFile, rootDir, include, files, and typeRoots.'
);

if ( finalConfig.compilerOptions.outDir ) {
	finalConfig.compilerOptions.outDir = path.join(
		projectRoot,
		finalConfig.compilerOptions.outDir
	);
}

if ( finalConfig.compilerOptions.outFile ) {
	finalConfig.compilerOptions.outFile = path.join(
		projectRoot,
		finalConfig.compilerOptions.outFile
	);
}

if ( finalConfig.compilerOptions.rootDir ) {
	finalConfig.compilerOptions.rootDir = path.join(
		projectRoot,
		finalConfig.compilerOptions.rootDir
	);
}

if ( finalConfig.compilerOptions.include ) {
	finalConfig.compilerOptions.include =
		finalConfig.compilerOptions.include.map( ( include ) =>
			path.join( projectRoot, include )
		);
}

if ( tsConfig.compilerOptions.files ) {
	finalConfig.compilerOptions.files = finalConfig.compilerOptions.files.map(
		( file ) => path.join( projectRoot, file )
	);
}

if ( tsConfig.compilerOptions.typeRoots ) {
	finalConfig.compilerOptions.typeRoots =
		finalConfig.compilerOptions.typeRoots.map( ( typeRoot ) =>
			path.join( projectRoot, typeRoot )
		);
}

if ( tsConfig.compilerOptions.declarationDir ) {
	finalConfig.compilerOptions.declarationDir = path.join(
		projectRoot,
		finalConfig.compilerOptions.declarationDir
	);
}

fs.writeFileSync(
	'./generated-tsc-config.json',
	JSON.stringify( finalConfig )
);

const tscArgs = [
	...process.argv.slice( 2 ),
	'--project',
	'./generated-tsc-config.json',
];

// Now run tsc with the new config
const result = spawnSync( 'tsc', [ ...tscArgs ], {
	stdio: 'inherit',
} );

fs.unlinkSync( './generated-tsc-config.json' );

// Check the exit code of tsc
if ( result.status !== 0 ) {
	console.error( `tsc exited with code ${ result.status }` );
	process.exit( 1 );
}

console.log( 'tsc completed successfully' );
