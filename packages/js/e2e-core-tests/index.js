/*
 * Internal dependencies
 */
const allSpecs = require( './specs' );
const fs = require( 'fs' );

/**
 * Read test set up configuration for the test scaffolding tool.
 */
const getObjectFromJsonFile = ( filename ) => {
	const specs = fs.readFileSync( filename );
	return JSON.parse( specs );
};

const getTestInstallSpecs = () => getObjectFromJsonFile( './data/install-specs.json' );
const getSampleDefaultJson = () => getObjectFromJsonFile( './data/default-test-config.json' );

module.exports = {
	allSpecs,
	getTestInstallSpecs,
	getSampleDefaultJson,
};
