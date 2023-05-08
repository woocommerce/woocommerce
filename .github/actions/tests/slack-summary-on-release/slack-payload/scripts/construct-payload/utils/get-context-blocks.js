const fs = require( 'fs' );
const path = require( 'path' );

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

const filterContextBlocks = ( blocks, testName ) => {
	const divider = {
		type: 'divider',
	};

	let filteredBlocks = [];

	const matchingBlocks = blocks.filter( ( { elements } ) =>
		elements[ 0 ].text.includes( testName )
	);

	matchingBlocks.forEach( ( block ) => {
		filteredBlocks.push( block );
		filteredBlocks.push( divider );
	} );

	return filteredBlocks;
};

module.exports = {
	filterContextBlocks,
	readContextBlocksFromJsonFiles,
};
