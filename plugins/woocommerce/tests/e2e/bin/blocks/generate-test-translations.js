#!/usr/bin/env node
const { readFileSync } = require( 'fs' );
const { ensureDirSync, writeJsonSync } = require( 'fs-extra' );
const crypto = require( 'crypto' );
const path = require( 'path' );
const glob = require( 'glob' );
const { translations } = require( '../../test-data/blocks/data/data.ts' );
const {
	getTestTranslation,
} = require( '../../utils/blocks/get-test-translation.js' );

const ROOT_DIR = path.resolve( __dirname, '../../../../' );
// When CI serves the shared plugin build artifact instead of the bind-mounted
// source checkout, the built JS lives in the artifact - the source checkout is
// never built. The JSONs still go to the source checkout's i18n/languages:
// .wp-env.json maps that directory into the container over the served plugin.
const BUILD_ROOT = process.env.WC_SHARED_PLUGIN_BUILD_PATH || ROOT_DIR;
const BUILD_DIR = path.resolve( BUILD_ROOT, 'assets/client/blocks/' );
const TESTS_DIR = path.resolve( __dirname, '../../tests/blocks' );
const LANGUAGES_DIR = path.join( ROOT_DIR, 'i18n/languages/' );

ensureDirSync( LANGUAGES_DIR );

const builtJsFiles = glob.sync( path.join( BUILD_DIR, '**/*.js' ) );
const testFiles = glob.sync( path.join( TESTS_DIR, '**/*.{js,ts}' ) );

if ( builtJsFiles.length === 0 ) {
	// Without built JS no translation JSONs are generated and the translation
	// specs fail much later with a confusing "untranslated string" error.
	throw new Error(
		`No built JS found under ${ BUILD_DIR }. Build the plugin or point WC_SHARED_PLUGIN_BUILD_PATH at a built plugin root.`
	);
}

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
	// Match WordPress's script handle path: assets are enqueued from
	// `plugins/woocommerce/assets/client/blocks/...` and WP hashes that
	// path (minus the plugin prefix) to find translation JSON files.
	const relativeFilePath = filePath.substring(
		filePath.indexOf( 'assets/client/blocks/' )
	);
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
