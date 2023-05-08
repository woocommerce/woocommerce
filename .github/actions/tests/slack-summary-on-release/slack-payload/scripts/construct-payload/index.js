module.exports = () => {
	const { RELEASE_VERSION, BLOCKS_DIR } = process.env;
	const { combineContextBlocks, filterContextBlocks } = require( './utils' );

	// Setup header block
	const headerText = `Test summary for ${ RELEASE_VERSION }`;
	const headerBlock = {
		type: 'header',
		text: {
			type: 'plain_text',
			text: headerText,
			emoji: true,
		},
	};

	// Get all the context blocks and group them by title
	const contextBlocks = combineContextBlocks( BLOCKS_DIR );
	const blocks_wcUpdate = filterContextBlocks( contextBlocks, 'WC Update' );
	const blocks_wpVersions = filterContextBlocks( contextBlocks, 'WP Latest' );
	const blocks_phpVersions = filterContextBlocks( contextBlocks, 'PHP' );
	const blocks_withPlugins = filterContextBlocks( contextBlocks, 'With' );

	// Arrange blocks
	const blocks_payload = [ headerBlock ]
		.concat( blocks_wcUpdate )
		.concat( blocks_wpVersions )
		.concat( blocks_phpVersions )
		.concat( blocks_withPlugins );

	// Set up and return the payload
	const payload = {
		text: headerText,
		blocks: blocks_payload,
	};

	return payload;
};
