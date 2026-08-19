/**
 * Internal dependencies
 */
const { htmlToMarkdown } = require( '../utilities/html-to-markdown' );

const returns = ( hookDoc ) => {
	const tags = hookDoc.tags || [];
	const returnDoc =
		tags.filter( ( { name: tagName } ) => tagName === 'return' )[ 0 ] ||
		undefined;

	return returnDoc
		? {
				p: `\`${ returnDoc.types.join( ', ' ) }\` ${ htmlToMarkdown(
					returnDoc.content
				) }`.trim(),
		  }
		: null;
};

module.exports = { returns };
