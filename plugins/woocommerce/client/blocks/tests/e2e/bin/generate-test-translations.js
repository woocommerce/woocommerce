#!/usr/bin/env node
const { readFileSync } = require( 'fs' );
const { ensureDirSync, writeJsonSync } = require( 'fs-extra' );
const crypto = require( 'crypto' );
const path = require( 'path' );
const glob = require( 'glob' );
const { translations } = require( '../test-data/data/data.ts' );
const { getTestTranslation } = require( '../utils/get-test-translation.js' );

const ROOT_DIR = path.resolve( __dirname, '../../../../../' );
const BUILD_DIR = path.resolve( __dirname, '../../../build/' );
const TESTS_DIR = path.resolve( __dirname, '../tests/' );
const LANGUAGES_DIR = path.join( ROOT_DIR, 'i18n/languages/' );

ensureDirSync( LANGUAGES_DIR );

const builtJsFiles = glob.sync( path.join( BUILD_DIR, '**/*.js' ) );
const testFiles = glob.sync( path.join( TESTS_DIR, '**/*.{js,ts}' ) );

// Scan the test files to collect translations used in the tests. We'll use this
// to generate the test translations json files.
let strings = [];
const regex = /getTestTranslation\(((?:.*?|\n)*?)'([^']*)'((?:.*?|\n)*?)\)/gm;
testFiles.forEach( ( filePath ) => {
	const fileContent = readFileSync( filePath );
	let matches;

	while ( ( matches = regex.exec( fileContent ) ) !== null ) {
		// This is necessary to avoid infinite loops with zero-width matches
		if ( matches.index === regex.lastIndex ) {
			regex.lastIndex++;
		}

		matches.forEach( ( match, groupIndex ) => {
			if ( groupIndex === 2 ) {
				strings.push( match );
			}
		} );
	}
} );

strings = [ ...new Set( strings ) ];
strings = strings.map( ( string ) => string.replace( /\d/, '%d' ) );

const { lang, locale } = translations;

builtJsFiles.forEach( ( filePath ) => {
	// console.log( filePath );
	const fileContent = readFileSync( filePath, {
		encoding: 'utf8',
		flag: 'r',
	} );

	// console.log( fileContent );
	const stringsInFile = strings.filter(
		( string ) => fileContent.indexOf( string ) !== -1
	);

	// console.log( stringsInFile );
	if ( stringsInFile.length === 0 ) {
		return;
	}
	const relativeFilePath = filePath
		.substring( filePath.indexOf( 'build/' ) )
		.replace( 'build', 'assets/client/blocks' );
	const data = {
		locale_data: {
			messages: {
				'': { lang },
			},
		},
		comment: {
			reference: relativeFilePath,
		},
	};
	stringsInFile.forEach( ( string ) => {
		data.locale_data.messages[ string ] = [ getTestTranslation( string ) ];
	} );
	const md5Path = crypto
		.createHash( 'md5' )
		.update( relativeFilePath )
		.digest( 'hex' );

	writeJsonSync(
		path.join( LANGUAGES_DIR, `woocommerce-${ locale }-${ md5Path }.json` ),
		data
	);
} );
