/**
 * External dependencies
 */
const fs = require( 'fs' );

const example = ( hookDoc ) => {
	const tags = hookDoc.tags || [];
	const exampleDoc =
		tags.filter( ( { name: tagName } ) => tagName === 'example' )[ 0 ] ||
		undefined;
	if ( ! exampleDoc || ! exampleDoc.content ) {
		return null;
	}

	const exampleSource = exampleDoc.content.startsWith( 'See ' )
		? exampleDoc.content.slice( 4 ).trimStart()
		: exampleDoc.content;

	// An @example tag that isn't a readable file path would otherwise abort the
	// whole docs build with the generated files already deleted by prebuild:docs.
	let exampleContent;
	try {
		exampleContent = fs.readFileSync( exampleSource, 'utf8' );
	} catch {
		// eslint-disable-next-line no-console
		console.warn(
			`Skipping @example "${ exampleDoc.content }": not a readable file path.`
		);
		return null;
	}

	// Demote the example doc's title so it nests under the "Example" section
	// instead of showing up as a sibling of the per-hook headings.
	if ( exampleContent.startsWith( '# ' ) ) {
		exampleContent = `#### ${ exampleContent.slice( 2 ) }`;
	}

	return exampleContent
		? {
				html: exampleContent,
		  }
		: null;
};

module.exports = { example };
