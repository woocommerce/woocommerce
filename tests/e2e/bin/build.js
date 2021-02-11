/**
 * script to build packages into `build/` directory.
 *
 * Example:
 *  node ./bin/packages/build.js
 */

/**
 * External dependencies
 */
const fs = require( 'fs' );
const path = require( 'path' );
const glob = require( 'glob' );
const babel = require( '@babel/core' );
const chalk = require( 'chalk' );
const mkdirp = require( 'mkdirp' );
const deasync = require( 'deasync' );

/**
 * Internal dependencies
 */
const getBabelConfig = require( './get-babel-config' );

/**
 * Module Constants
 */
const PACKAGE_DIR = process.cwd();
const SRC_DIR = 'src';
const BUILD_DIR = {
	main: 'build',
	module: 'build-module',
};
const DONE = chalk.reset.inverse.bold.green( ' DONE ' );

const isJsFile = ( filepath ) => {
	return /.\.js$/.test( filepath );
};

/**
 * Get Build Path for a specified file
 *
 * @param  {string} file        File to build
 * @param  {string} buildFolder Output folder
 * @return {string}             Build path
 */
function getBuildPath( file, buildFolder ) {
	const pkgSrcPath = path.resolve( PACKAGE_DIR, SRC_DIR );
	const pkgBuildPath = path.resolve( PACKAGE_DIR, buildFolder );
	const relativeToSrcPath = path.relative( pkgSrcPath, file );
	return path.resolve( pkgBuildPath, relativeToSrcPath );
}

/**
 * Given a list of scss and js filepaths, divide them into sets them and rebuild.
 *
 * @param {Array} files list of files to rebuild
 */
function buildFiles( files ) {
	// Reduce files into a unique sets of javaScript files and scss packages.
	const buildPaths = files.reduce(
		( accumulator, filePath ) => {
			if ( isJsFile( filePath ) ) {
				accumulator.jsFiles.add( filePath );
			}
			return accumulator;
		},
		{ jsFiles: new Set() }
	);

	buildPaths.jsFiles.forEach( buildJsFile );
}

/**
 * Build a javaScript file for the required environments (node and ES5)
 *
 * @param {string} file    File path to build
 * @param {boolean} silent Show logs
 */
function buildJsFile( file, silent ) {
	buildJsFileFor( file, silent, 'main' );
	buildJsFileFor( file, silent, 'module' );
}

/**
 * Build a file for a specific environment
 *
 * @param {string}  file        File path to build
 * @param {boolean} silent      Show logs
 * @param {string}  environment Dist environment (node or es5)
 */
function buildJsFileFor( file, silent, environment ) {
	const buildDir = BUILD_DIR[ environment ];
	const destPath = getBuildPath( file, buildDir );
	const babelOptions = getBabelConfig( environment );
	babelOptions.sourceMaps = true;
	babelOptions.sourceFileName = file;

	mkdirp.sync( path.dirname( destPath ) );
	const transformed = babel.transformFileSync( file, babelOptions );
	fs.writeFileSync( destPath + '.map', JSON.stringify( transformed.map ) );
	fs.writeFileSync(
		destPath,
		transformed.code +
			'\n//# sourceMappingURL=' +
			path.basename( destPath ) +
			'.map'
	);

	if ( ! silent ) {
		process.stdout.write(
			chalk.green( '  \u2022 ' ) +
				path.relative( PACKAGE_DIR, file ) +
				chalk.green( ' \u21D2 ' ) +
				path.relative( PACKAGE_DIR, destPath ) +
				'\n'
		);
	}
}

/**
 * Build the provided package path
 *
 * @param {string} packagePath absolute package path
 */
function buildPackage( packagePath ) {
	const srcDir = path.resolve( packagePath, SRC_DIR );

	let packageName;
	try {
		packageName = require( path.resolve( PACKAGE_DIR, 'package.json' ) ).name;
	} catch ( e ) {
		packageName = PACKAGE_DIR.split( path.sep ).pop();
	}
	process.stdout.write( chalk.inverse( `>> Building package: ${ packageName }\n` ) );

	const jsFiles = glob.sync( `${ srcDir }/**/*.js`, {
		ignore: [
			`${ srcDir }/**/test/**/*.js`,
			`${ srcDir }/**/__mocks__/**/*.js`,
		],
		nodir: true,
	} );

	// Build js files individually.
	jsFiles.forEach( ( file ) => buildJsFile( file, true ) );

	process.stdout.write( `${ DONE }\n` );
}

const files = process.argv.slice( 2 );

if ( files.length ) {
	buildFiles( files );
} else {
	buildPackage( PACKAGE_DIR );
}
