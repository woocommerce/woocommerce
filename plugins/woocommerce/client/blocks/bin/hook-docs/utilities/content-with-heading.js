/**
 * Internal dependencies
 */
const { htmlToMarkdown } = require( './html-to-markdown' );

const contentWithHeading = ( content, heading, headingLevel = 'h3' ) => {
	return content && content.length
		? [ { [ headingLevel ]: heading }, { html: htmlToMarkdown( content ) } ]
		: [];
};

module.exports = { contentWithHeading };
