const fs = require( 'fs' );
const path = require( 'path' );

/**
 * @param {string} blocksDir
 * @returns {any[][]}
 */
const readContextBlocksFromJsonFiles = ( blocksDir ) => {
	const jsonsDir = path.resolve( blocksDir );
	const jsons = fs.readdirSync( jsonsDir );

	let contextBlocks = [];

	for ( const json of jsons ) {
		const jsonPath = path.resolve( jsonsDir, json );
		const contextBlock = require( jsonPath );

		contextBlocks.push( contextBlock );
	}

	return contextBlocks;
};

module.exports = {
	readContextBlocksFromJsonFiles,
};
