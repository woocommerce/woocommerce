'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );

function findUp( fileName, startDirectory = process.cwd() ) {
	let currentDirectory = path.resolve( startDirectory );

	while ( true ) {
		const candidate = path.join( currentDirectory, fileName );

		if ( fs.existsSync( candidate ) ) {
			return candidate;
		}

		const parentDirectory = path.dirname( currentDirectory );

		if ( parentDirectory === currentDirectory ) {
			return undefined;
		}

		currentDirectory = parentDirectory;
	}
}

function readJsonFile( filePath ) {
	return JSON.parse( fs.readFileSync( filePath, 'utf8' ) );
}

function writeJsonFile( filePath, contents ) {
	fs.writeFileSync( filePath, JSON.stringify( contents, null, 2 ) + '\n' );
}

module.exports = {
	findUp,
	readJsonFile,
	writeJsonFile,
};
