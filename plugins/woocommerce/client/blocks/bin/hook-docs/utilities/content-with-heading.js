/**
 * Internal dependencies
 */
const { docblockToMarkdown } = require( './docblock-to-markdown' );

const contentWithHeading = ( content, heading, headingLevel = 'h3' ) => {
	return content && content.length
		? [
				{ [ headingLevel ]: heading },
				{ html: docblockToMarkdown( content ) },
		  ]
		: [];
};

module.exports = { contentWithHeading };
