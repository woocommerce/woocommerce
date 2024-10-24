/**
 * This script generates a PHP file from block JSON files.
 * To run it (this script only):
 *   pnpm run build:project:blocks-json-to-php
 * Or, it will be run automatically when you run:
 *   pnpm run build
 *
 * In watch mode:
 *   pnpm run watch:build:project:blocks-json-to-php
 * Or
 *   pnpm run watch:build
 */

const fs = require( 'fs' );
const path = require( 'path' );
const glob = require( 'glob' );
const json2php = require( 'json2php' );

const blocksDir = path.join( __dirname, '../build' );
const outputDir = path.join( __dirname, '../build' );
const outputFile = path.join( outputDir, 'blocks-json.php' );

const blocks = {};

const globSync = glob.sync;
const blockMetadataFiles = globSync( `${ blocksDir }/**/block.json` );

blockMetadataFiles.forEach( ( file ) => {
	const blockJson = JSON.parse( fs.readFileSync( file, 'utf8' ) );
	const directoryName = path.basename( path.dirname( file ) );
	blocks[ directoryName ] = blockJson;
} );

const printer = json2php.make( { linebreak:'\n', indent:'\t', shortArraySyntax: true } );
const phpContent = `<?php
// This file is generated. Do not modify it manually.
return ${ printer(  blocks ) };
`;

fs.mkdirSync( outputDir, { recursive: true } );
fs.writeFileSync( outputFile, phpContent );

console.log( 'blocks-json.php has been generated.' );
