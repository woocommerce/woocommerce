/**
 * External dependencies
 */
const fs = require( 'fs' );
const path = require( 'path' );

const example = ( hookDoc ) => {
	const tags = hookDoc.tags || [];
	const exampleDoc =
		tags.filter( ( { name: tagName } ) => tagName === 'example' )[ 0 ] ||
		undefined;
	if ( ! exampleDoc || ! exampleDoc.content ) {
		return null;
	}

	// The @example content is the path of a Markdown file under
	// docs/examples. Anything else (a missing file, a directory, or a path
	// resolving outside the examples dir) is skipped with a warning instead
	// of aborting the docs build with the generated files already deleted by
	// prebuild:docs.
	let exampleContent;
	try {
		const examplesRoot = fs.realpathSync( 'docs/examples' ) + path.sep;
		const resolvedSource = fs.realpathSync( exampleDoc.content );
		if ( ! resolvedSource.startsWith( examplesRoot ) ) {
			throw new Error( 'path is outside docs/examples' );
		}
		exampleContent = fs.readFileSync( resolvedSource, 'utf8' );
	} catch {
		// eslint-disable-next-line no-console
		console.warn(
			`Skipping @example "${ exampleDoc.content }": not a readable file under docs/examples.`
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
