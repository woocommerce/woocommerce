/**
 * External dependencies
 */
import directoryTree from 'directory-tree';

// A prototype for generating a manifest file format from a directory of markdown files.

// @ts-ignore
const generateMetadataForDirectory = ( item, path, stat ) => {};

// @ts-ignore
const generateMetadataForFile = ( item, path, stat ) => {
	console.log( item, path, stat );
};

const tree = directoryTree(
	'./example_docs',
	{ extensions: /\.md$/, attributes: [ 'type' ] },
	generateMetadataForDirectory,
	generateMetadataForFile
);

console.log( tree );
