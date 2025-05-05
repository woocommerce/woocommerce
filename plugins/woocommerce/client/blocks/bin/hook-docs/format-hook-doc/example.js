/**
 * External dependencies
 */
const fs = require( 'fs' );

const example = ( hookDoc ) => {
	const tags = hookDoc.tags || [];
	const exampleDoc =
		tags.filter( ( { name: tagName } ) => tagName === 'example' )[ 0 ] ||
		undefined;
	const exampleSource = exampleDoc ? exampleDoc.content : false;

	if ( ! exampleSource ) {
		return null;
	}

	const buffer = fs.readFileSync( `${ exampleSource }` );
	const exampleContent = buffer.toString();

	return exampleContent
		? {
				html: exampleContent,
		  }
		: null;
};

module.exports = { example };
