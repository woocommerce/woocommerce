module.exports = () => {
	const { RELEASE_VERSION, BLOCKS_DIR } = process.env;
	const {
		filterContextBlocks,
		readContextBlocksFromJsonFiles,
	} = require( './utils' );

	const headerText = `Test summary for ${ RELEASE_VERSION }`;
	const headerBlock = {
		type: 'header',
		text: {
			type: 'plain_text',
			text: headerText,
			emoji: true,
		},
	};

	const blocks_all = readContextBlocksFromJsonFiles( BLOCKS_DIR );
	const blocks_wcUpdate = filterContextBlocks( blocks_all, 'WC Update' );
	const blocks_wpVersions = filterContextBlocks( blocks_all, 'WP Latest' );
	const blocks_phpVersions = filterContextBlocks( blocks_all, 'PHP' );
	const blocks_plugins = filterContextBlocks( blocks_all, 'With' );

	const blocksPayload = [ headerBlock ]
		.concat( blocks_wcUpdate )
		.concat( blocks_wpVersions )
		.concat( blocks_phpVersions )
		.concat( blocks_plugins );

	const payload = {
		text: headerText,
		blocks: blocksPayload,
	};

	return payload;
};
