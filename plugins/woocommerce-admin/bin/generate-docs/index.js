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
	ANALYTICS_FOLDER,
	PACKAGES_FOLDER,
	DOCS_FOLDER,
	getExportedFileList,
	getMdFileName,
	getRealFilePaths,
	getTocSection,
} = require( './lib/file-system' );

const fileCollections = [
	{
		folder: ANALYTICS_FOLDER,
		route: 'analytics',
		title: 'Analytics components',
	},
	{
		folder: PACKAGES_FOLDER,
		route: 'packages',
		title: 'Package components',
	},
];

const tocSections = [];

fileCollections.forEach( fileCollection => {
	// Read components file to get a list of exported files, convert that to a list of absolute paths to public components.
	const files = getRealFilePaths( getExportedFileList( path.resolve( fileCollection.folder, 'index.js' ) ), fileCollection.folder );

	// Build documentation for components missing them
	buildComponentDocs( files, fileCollection.route );

	// Concatenate TOC contents
	tocSections.push( ...getTocSection( files, fileCollection.route, fileCollection.title ) );
} );

// Write TOC file
const tocFile = path.resolve( DOCS_FOLDER, '_sidebar.md' );
const tocHeader = '* [Home](/)\n\n* [Components](components/)\n\n';
fs.writeFileSync( tocFile, tocHeader + tocSections.join( '\n' ) );

// Sum the number of TOC lines and substract the titles
const numberOfFiles = tocSections.length - fileCollections.length;
console.log( `Wrote docs for ${ numberOfFiles } files.` );

/**
 * Parse each file's content & build up a markdown file.
 *
 * @param { Array } files The absolute path of this file.
 * @param { string } route Folder where the docs must be stored.
 */
function buildComponentDocs( files, route ) {
	// Build the documentation by reading each file.
	files.forEach( file => {
		try {
			const content = fs.readFileSync( file );
			buildDocs( file, route, content );
		} catch ( readErr ) {
			console.warn( file, readErr );
		}
	} );
}

/**
 * Parse each file's content & build up a markdown file.
 *
 * @param { string } fileName The absolute path of this file.
 * @param { string } route Folder where the docs must be stored.
 * @param { string } content Content of this file.
 * @param { boolean } multiple If there are multiple exports in this file, we need to use a different resolver.
 */
function buildDocs( fileName, route, content, multiple = false ) {
	let mdFileName = getMdFileName( fileName, route );

	// We symlink our package docs.
	if ( 'packages' === route ) {
		mdFileName = mdFileName.replace( 'docs/components/packages', 'packages/components/src' );
	}

	if ( fs.existsSync( mdFileName ) ) {
		// Don't overwrite existing files.
		return;
	}

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
			buildDocs( fileName, route, content, true );
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
	markdownString += '## Usage\n\n```jsx\n```\n\n';
	markdownString += getProps( docObject.props );
	return markdownString + '\n';
}
