/**
 * External dependencies
 */
import path from 'path';
import fs from 'fs';

/**
 * Internal dependencies
 */
import { Category, Post, generatePostId } from './generate-manifest';

/**
 * Process relative markdown links in the manifest.
 *
 * @param manifest       Category or Post
 * @param rootDirectory  Root directory of the project
 * @param absoluteSubDir Path to directory of Markdown files to generate the manifest from.
 * @param projectName    Name of the project
 */
export const processMarkdownLinks = (
	manifest: Category,
	rootDirectory: string,
	absoluteSubDir: string,
	projectName: string
) => {
	const updatedManifest: Category = { ...manifest };

	if ( updatedManifest.posts ) {
		updatedManifest.posts = updatedManifest.posts.map( ( post ) => {
			const updatedPost: Post = { ...post };
			const filePath = path.resolve(
				rootDirectory,
				updatedPost.filePath as string
			);
			const fileContent = fs.readFileSync( filePath, 'utf-8' );
			const linkRegex = /\[(?:.*?)\]\((.*?)\)/g;

			let match;
			while ( ( match = linkRegex.exec( fileContent ) ) ) {
				const relativePath = match[ 1 ];
				const absoluteLinkedFilePath = path.resolve(
					path.dirname( filePath ),
					relativePath
				);
				const relativeLinkedFilePath = path.relative(
					absoluteSubDir,
					absoluteLinkedFilePath
				);

				if ( fs.existsSync( absoluteLinkedFilePath ) ) {
					const linkedId = generatePostId(
						relativeLinkedFilePath,
						projectName
					);
					updatedPost.links = updatedPost.links || {};
					updatedPost.links[ relativePath ] = linkedId;
				}
			}

			// dont expose filePath on updated posts
			delete updatedPost.filePath;
			return updatedPost;
		} );
	}

	if ( updatedManifest.categories ) {
		updatedManifest.categories = updatedManifest.categories.map(
			( category ) =>
				processMarkdownLinks(
					category,
					rootDirectory,
					absoluteSubDir,
					projectName
				)
		);
	}

	return updatedManifest;
};
