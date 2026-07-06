#!/usr/bin/env node
/**
 * External dependencies
 */
const fs = require( 'fs' );

/**
 * Internal dependencies
 */
const { getBlockJsonWithSharedEditorStyle } = require( './webpack-helpers' );

const [ sourcePath, destinationPath ] = process.argv.slice( 2 );

if ( ! sourcePath || ! destinationPath ) {
	throw new Error(
		'Usage: copy-block-json.js <sourcePath> <destinationPath>'
	);
}

const content = fs.readFileSync( sourcePath, 'utf8' );

fs.writeFileSync(
	destinationPath,
	getBlockJsonWithSharedEditorStyle( content )
);
