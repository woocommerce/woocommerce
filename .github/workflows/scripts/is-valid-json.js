// This needs to be installed as part of the workflow.
const core = require( '@actions/core' );

const fs = require( 'fs' );
const path = require( 'path' );

const input_file = process.argv[2] || '';

if ( ! input_file ) {
	const basename = path.basename( process.argv[1] );
	console.log( 'Usage: node `${basename}` <json-file>' );
	process.exit();
}

let json = input_file;
if ( input_file.substring( 0, 1 ) != path.sep ) {
	json = path.join( process.cwd(), input_file );
}

if ( ! fs.existsSync( json ) ) {
	console.log( `file not found: ${input_file}` );
	process.exit();
}

const content = fs.readFileSync( json, { 'encoding': 'utf-8' } );
try {
	JSON.parse(content);
	core.setOutput( 'is-valid-json', 'yes' );
} catch (e) {
	core.setOutput( 'is-valid-json', 'no' );
}
