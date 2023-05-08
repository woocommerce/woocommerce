const { BLOCKS_DIR } = process.env;
const fs = require( 'fs' );
const path = require( 'path' );

const combineContextBlocks = () => {
	const jsonsDir = path.resolve( BLOCKS_DIR );
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
	combineContextBlocks,
};
