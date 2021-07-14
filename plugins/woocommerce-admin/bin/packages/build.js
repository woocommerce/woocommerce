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
const chalk = require( 'chalk' );
const mkdirp = require( 'mkdirp' );
const sass = require( 'sass' );
const postcss = require( 'postcss' );

/**
 * Internal dependencies
 */
const getPackages = require( './get-packages' );

/**
 * Module Constants
 */
const PACKAGES_DIR = path.resolve( __dirname, '../../packages' );
const SRC_DIR = 'src';
const BUILD_DIR = {
	main: 'build',
	module: 'build-module',
	style: 'build-style',
};
const DONE = chalk.reset.inverse.bold.green( ' DONE ' );

/**
 * Get the package name for a specified file
 *
 * @param  {string} file File name
 * @return {string}      Package name
 */
function getPackageName( file ) {
	return path.relative( PACKAGES_DIR, file ).split( path.sep )[ 0 ];
}

const isScssFile = ( filepath ) => {
	return /.\.scss$/.test( filepath );
};

/**
 * Get Build Path for a specified file
 *
 * @param  {string} file        File to build
 * @param  {string} buildFolder Output folder
 * @return {string}             Build path
 */
function getBuildPath( file, buildFolder ) {
	// if the file has extension of ts, replace with js
	const fileName = file.replace( '.ts', '.js' );
	const pkgName = getPackageName( fileName );
	const pkgSrcPath = path.resolve( PACKAGES_DIR, pkgName, SRC_DIR );
	const pkgBuildPath = path.resolve( PACKAGES_DIR, pkgName, buildFolder );
	const relativeToSrcPath = path.relative( pkgSrcPath, fileName );
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
			if ( isScssFile( filePath ) ) {
				const pkgName = getPackageName( filePath );
				const pkgPath = path.resolve( PACKAGES_DIR, pkgName );
				accumulator.scssPackagePaths.add( pkgPath );
			}
			return accumulator;
		},
		{ scssPackagePaths: new Set() }
	);

	buildPaths.scssPackagePaths.forEach( buildPackageScss );
}

/**
 * Build a package's scss styles
 *
 * @param {string} packagePath The path to the package.
 */
async function buildPackageScss( packagePath ) {
	const srcDir = path.resolve( packagePath, SRC_DIR );
	const scssFiles = glob.sync( `${ srcDir }/**/*.scss` );

	// Build scss files individually.
	return Promise.all( scssFiles.map( ( file ) => buildScssFile( file ) ) );
}

async function buildScssFile( styleFile ) {
	const outputFile = getBuildPath(
		styleFile.replace( '.scss', '.css' ),
		BUILD_DIR.style
	);
	const outputFileRTL = getBuildPath(
		styleFile.replace( '.scss', '-rtl.css' ),
		BUILD_DIR.style
	);

	await mkdirp.sync( path.dirname( outputFile ) );
	const builtSass = await sass.renderSync( {
		file: styleFile,
		includePaths: [
			path.resolve( __dirname, '../../client/stylesheets/abstracts' ),
			path.resolve( __dirname, '../../node_modules' ),
		],
		data:
			'@forward "sass:math"; @use "sass:math";' +
			[ 'colors', 'variables', 'breakpoints', 'mixins' ]
				.map( ( imported ) => `@import "_${ imported }";` )
				.join( ' ' ) +
			fs.readFileSync( styleFile, 'utf8' ),
	} );

	const postCSSConfig = require( '@wordpress/postcss-plugins-preset' );
	const ltrCSS = await postcss( postCSSConfig ).process( builtSass.css, {
		from: 'src/app.css',
		to: 'dest/app.css',
	} );
	fs.writeFileSync( outputFile, ltrCSS.css );

	const rtlCSS = await postcss( [ require( 'rtlcss' )() ] ).process( ltrCSS, {
		from: 'src/app.css',
		to: 'dest/app.css',
	} );
	fs.writeFileSync( outputFileRTL, rtlCSS.css );
}

/**
 * Build the provided package path
 *
 * @param {string} packagePath absolute package path
 */
async function buildPackage( packagePath ) {
	// Build package CSS files
	await buildPackageScss( packagePath );

	process.stdout.write( `${ path.basename( packagePath ) }\n` );
	process.stdout.write( `${ DONE }\n` );
}

const files = process.argv.slice( 2 );

if ( files.length ) {
	buildFiles( files );
} else {
	process.stdout.write( chalk.inverse( '>> Building packages \n' ) );
	getPackages()
		.filter(
			( package ) =>
				! /dependency-extraction-webpack-plugin/.test( package )
		)
		.forEach( buildPackage );
	process.stdout.write( '\n' );
}
