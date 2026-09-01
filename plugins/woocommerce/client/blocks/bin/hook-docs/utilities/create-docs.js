'use strict';
/* eslint no-console: 0 */

/**
 * External dependencies
 */
const fs = require( 'fs' );
const chalk = require( 'chalk' );

/**
 * Internal dependencies
 */
const { json2md } = require( './json2md' );

const createDocs = ( file, jsonDocs ) => {
	console.log( chalk.blue( `Creating file ${ file }...` ) );

	// Synchronous, so a write failure surfaces inside the caller's try/catch.
	fs.writeFileSync( file, json2md( jsonDocs ) );
};

module.exports = { createDocs };
