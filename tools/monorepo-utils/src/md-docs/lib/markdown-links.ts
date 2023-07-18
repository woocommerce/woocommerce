/**
 * External dependencies
 */
import path from 'path';
import fs from 'fs';

/**
 * Internal dependencies
 */
import { Category, generatePageId } from './generate-manifest';

/**
 * Process markdown links in the manifest.
 *
 * @param manifestEntry Category or Post
 * @param rootDirectory Root directory of the project
 * @param projectName   Name of the project
 */
export const processMarkdownLinks = async (
	manifestEntry: Category,
	rootDirectory: string,
	projectName: string
) => {
	for ( const post of manifestEntry.posts || [] ) {
		const filePath = path.resolve(
			rootDirectory,
			post.file_path as string
		);
		const fileContent = fs.readFileSync( filePath, 'utf-8' );
		const linkRegex = /\[(.*?)\]\((.*?)\)/g;

		let match;
		while ( ( match = linkRegex.exec( fileContent ) ) ) {
			// const linkText = match[ 1 ];
			const relativePath = match[ 2 ];
			const linkedFilePath = path.resolve(
				path.dirname( filePath ),
				relativePath
			);

			if ( fs.existsSync( linkedFilePath ) ) {
				const linkedId = generatePageId( linkedFilePath, projectName );
				post.links = post.links || {};
				post.links[ relativePath ] = linkedId;
			}
		}
	}

	for ( const category of manifestEntry.categories || [] ) {
		await processMarkdownLinks( category, rootDirectory, projectName );
	}

	return manifestEntry;
};
