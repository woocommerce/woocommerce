const { JSONS_DIR, RELEASE_VERSION, SLACK_BLOCKS_ARTIFACT } = process.env;
const fs = require( 'fs' );
const path = require( 'path' );

const combineContextBlocks = () => {
	const jsonsPath = path.resolve( JSONS_DIR, SLACK_BLOCKS_ARTIFACT );
	const jsons = fs.readdirSync( jsonsPath );

	let contextBlocks = [];

	for ( const json of jsons ) {
		const jsonPath = path.resolve( jsonsPath, json );
		const contextBlock = require( jsonPath );

		contextBlocks.push( contextBlock );
	}

	return contextBlocks;
};

module.exports = () => {
	const headerBlock = {
		type: 'header',
		text: {
			type: 'plain_text',
			text: `Test summary for ${ RELEASE_VERSION }`,
			emoji: true,
		},
	};

	const contextBlocks = combineContextBlocks();

	const payload = { blocks: [ headerBlock ].concat( contextBlocks ) };

	return payload;
};
