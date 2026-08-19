/**
 * Internal dependencies
 */
const { htmlToMarkdown } = require( '../utilities/html-to-markdown' );

const exceptions = ( hookDoc ) => {
	const tags = hookDoc.tags || [];
	const throwsDoc =
		tags.filter( ( { name: tagName } ) => tagName === 'throws' )[ 0 ] ||
		undefined;

	return throwsDoc
		? {
				p: `\`${ throwsDoc.types.join( ', ' ) }\` ${ htmlToMarkdown(
					throwsDoc.content
				) }`,
		  }
		: null;
};

module.exports = { exceptions };
