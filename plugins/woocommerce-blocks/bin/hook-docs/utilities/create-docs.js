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

	fs.writeFile( file, json2md( jsonDocs ), function ( error ) {
		if ( error ) {
			throw error;
		}
	} );
};

module.exports = { createDocs };
