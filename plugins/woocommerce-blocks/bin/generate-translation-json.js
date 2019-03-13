#!/usr/bin/env node
/* eslint-disable no-console, no-useless-return */
// Node internals
const fs = require( 'fs' );
const path = require( 'path' );

// Packages
const po2json = require( 'po2json' );
const md5 = require( 'js-md5' );
const argv = require( 'yargs' )
	.usage( 'Usage: $0 [options]' )
	.describe( 'source', 'Path to the language directory.' )
	.describe( 'debug', 'Output which files are added to the zip during build.' )
	.alias( 'debug', 'v' )
	.default( 'source', './languages' )
	.boolean( 'v' )
	.argv;

const sourceDir = path.resolve( argv.source );
const showDebug = argv.debug;

// Check that we have a languages directory
if ( ! fs.existsSync( sourceDir ) ) {
	return;
}

// Get .po files
const files = fs.readdirSync( sourceDir ).filter( ( f ) => !! f.match( /\.po$/ ) );

if ( ! files.length ) {
	console.log( 'No language (.po) files found.' );
	return;
}
console.log( `Found ${ files.length } language files to convert.` );

// Get the built .js files
const jsFiles = fs.readdirSync( './build' ).filter( ( f ) => !! f.match( /\.js$/ ) );
console.log( `Found ${ jsFiles.length } scripts that need translations.` );

files.forEach( ( file ) => {
	if ( showDebug ) {
		console.log( `Converting ${ file }` );
	}
	const filePath = path.resolve( sourceDir, file );
	const name = path.basename( file )
		.replace( 'woo-gutenberg-products-block-', '' )
		.replace( '.po', '' );

	const poContent = fs.readFileSync( filePath );
	const jsonContent = po2json.parse( poContent, { format: 'jed', stringify: true } );

	jsFiles.forEach( ( jsFile ) => {
		const hash = md5( `build/${ jsFile }` );
		const filename = `woo-gutenberg-products-block-${ name }-${ hash }.json`;
		if ( showDebug ) {
			console.log( `   Writing ${ filename }` );
		}
		fs.writeFile(
			path.resolve( './languages/', filename ),
			jsonContent,
			( error ) => {
				if ( error ) {
					console.warn( 'Error writing the JSON file.' );
					console.log( error );
				}
			}
		);
	} );
} );

console.log( `Done processing language files.` );
