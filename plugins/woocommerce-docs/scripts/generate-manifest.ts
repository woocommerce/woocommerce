/**
 * External dependencies
 */
import * as fs from 'fs';
import * as path from 'path';
import * as matter from 'gray-matter';

interface MarkdownFile {
	frontmatter: any;
	children: MarkdownFile[];
}

function traverseDirectory( dirPath: string ): MarkdownFile[] {
	const files = fs.readdirSync( dirPath );
	const markdownFiles: MarkdownFile[] = [];

	for ( const file of files ) {
		const filePath = path.join( dirPath, file );
		const stats = fs.statSync( filePath );

		if ( stats.isDirectory() ) {
			const children = traverseDirectory( filePath );
			markdownFiles.push( ...children );
		} else if ( stats.isFile() && path.extname( file ) === '.md' ) {
			const content = fs.readFileSync( filePath, 'utf-8' );
			const { data: frontmatter } = matter( content );

			const markdownFile: MarkdownFile = {
				frontmatter,
				children: [],
			};

			markdownFiles.push( markdownFile );
		}
	}

	return markdownFiles;
}

const directoryPath = '/path/to/markdown/files';
const markdownTree = traverseDirectory( directoryPath );
const jsonTree = JSON.stringify( markdownTree, null, 2 );

console.log( jsonTree );
