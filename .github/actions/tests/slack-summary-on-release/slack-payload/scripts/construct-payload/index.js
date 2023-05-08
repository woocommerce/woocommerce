module.exports = () => {
	const { RELEASE_VERSION } = process.env;
	const {
		combineContextBlocks,
	} = require( './utils/combine-context-blocks' );
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
