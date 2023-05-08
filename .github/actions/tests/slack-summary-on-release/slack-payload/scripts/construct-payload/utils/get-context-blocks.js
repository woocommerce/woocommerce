const fs = require( 'fs' );
const path = require( 'path' );

const getContextBlocks = ( blocksDir, testName ) => {
	const jsonsDir = path.resolve( blocksDir );
	const jsons = fs
		.readdirSync( jsonsDir )
		.filter( ( { elements } ) => elements[ 0 ].text.includes( testName ) );

	const dividerBlock = {
		type: 'divider',
	};

	let contextBlocks = [];

	for ( const json of jsons ) {
		const jsonPath = path.resolve( jsonsDir, json );
		const contextBlock = require( jsonPath );

		contextBlocks.push( contextBlock );
		contextBlocks.push( dividerBlock );
	}

	return contextBlocks;
};

module.exports = {
	getContextBlocks,
};
