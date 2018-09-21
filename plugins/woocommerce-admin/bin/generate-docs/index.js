/** @format */
/* eslint-disable no-console */
/**
 * External dependencies
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { parse, resolver } = require( 'react-docgen' );

/**
 * Internal dependencies
 */
const { getDescription, getProps, getTitle } = require( './lib/formatting' );
const {
	COMPONENTS_FOLDER,
	deleteExistingDocs,
	getExportedFileList,
	getMdFileName,
	getRealFilePaths,
	writeTableOfContents,
} = require( './lib/file-system' );

const filePath = path.resolve( COMPONENTS_FOLDER, 'index.js' );

// Start by wiping the existing docs. **Change this if we end up manually editing docs**
deleteExistingDocs();

// Read components file to get a list of exported files, convert that to a list of absolute paths to public components.
const files = getRealFilePaths( getExportedFileList( filePath ) );

// Build the documentation by reading each file.
files.forEach( file => {
	try {
		const content = fs.readFileSync( file );
		buildDocs( file, content );
	} catch ( readErr ) {
		console.warn( file, readErr );
	}
} );

writeTableOfContents( files );

console.log( `Wrote docs for ${ files.length } files.` );

/**
 * Parse each file's content & build up a markdown file.
 *
 * @param { string } fileName The absolute path of this file.
 * @param { string } content Content of this file.
 * @param { boolean } multiple If there are multiple exports in this file, we need to use a different resolver.
 */
function buildDocs( fileName, content, multiple = false ) {
	const mdFileName = getMdFileName( fileName );
	let markdown;

	try {
		if ( multiple ) {
			const docObject = parse( content, resolver.findAllExportedComponentDefinitions );
			markdown = docObject.map( doc => generateMarkdown( doc ) ).join( '\n\n' );
		} else {
			const docObject = parse( content );
			markdown = generateMarkdown( docObject );
		}
		fs.appendFileSync( mdFileName, markdown );
	} catch ( parseErr ) {
		if ( ! multiple ) {
			// The most likely error is that there are multiple exported components
			buildDocs( fileName, content, true );
			return;
		}
		console.warn( fileName, parseErr );
	}
}

/**
 * Convert documentation object to a markdown string.
 *
 * @param { object } docObject The parsed documentation object.
 * @return { string } Generated markdown.
 */
function generateMarkdown( docObject ) {
	let markdownString = getTitle( docObject.displayName ) + '\n';
	markdownString += getDescription( docObject.description ) + '\n';
	markdownString += getProps( docObject.props );
	return markdownString + '\n';
}
