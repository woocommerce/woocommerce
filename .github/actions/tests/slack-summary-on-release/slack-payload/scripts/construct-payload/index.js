module.exports = () => {
	const { RELEASE_VERSION, BLOCKS_DIR } = process.env;
	const { getContextBlocks } = require( './utils' );

	const headerText = `Test summary for ${ RELEASE_VERSION }`;
	const headerBlock = {
		type: 'header',
		text: {
			type: 'plain_text',
			text: headerText,
			emoji: true,
		},
	};

	const block_wcUpdate = getContextBlocks( BLOCKS_DIR, 'WC Update' );
	const blocks_wpVersions = getContextBlocks( BLOCKS_DIR, 'WP Latest' );
	const blocks_phpVersions = getContextBlocks( BLOCKS_DIR, 'PHP' );
	const blocks_plugins = getContextBlocks( BLOCKS_DIR, 'With' );

	const blocksPayload = [ headerBlock ]
		.concat( block_wcUpdate )
		.concat( blocks_wpVersions )
		.concat( blocks_phpVersions )
		.concat( blocks_plugins );

	const payload = {
		text: headerText,
		blocks: blocksPayload,
	};

	return payload;
};
