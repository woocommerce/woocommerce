const { BLOCKS_DIR, RELEASE_VERSION } = process.env;
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

module.exports = () => {
	const headerText = `Test summary for ${ RELEASE_VERSION }`;
	const headerBlock = {
		type: 'header',
		text: {
			type: 'plain_text',
			text: headerText,
			emoji: true,
		},
	};

	const contextBlocks = combineContextBlocks();

	const payload = {
		text: headerText,
		blocks: [ headerBlock ].concat( contextBlocks ),
	};

	return payload;
};
