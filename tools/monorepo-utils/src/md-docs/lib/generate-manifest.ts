/**
 * External dependencies
 */
import fs from 'fs';
import path from 'path';
import matter from 'gray-matter';
import { glob } from 'glob';
import crypto from 'crypto';

interface Category {
	[ key: string ]: unknown;
}

interface Post {
	[ key: string ]: unknown;
}

function generatePageId( filePath: string, prefix = '' ) {
	const hash = crypto.createHash( 'sha1' );
	hash.update( prefix + filePath );
	return hash.digest( 'hex' );
}

export const generateFileUrl = (
	baseUrl: string,
	rootDirectory: string,
	directory: string,
	filePath: string
) => {
	const absoluteFilePath = path.resolve( directory, filePath );
	const relativePath = path.relative( rootDirectory, absoluteFilePath );

	return `${ baseUrl }/${ relativePath }`;
};

async function processDirectory(
	directory: string,
	rootDirectory: string,
	projectName: string,
	baseUrl: string,
	checkReadme = true
): Promise< Category > {
	let category: Category = {};

	// Process README.md (if exists) for the category definition.
	const readmePath = path.join( directory, 'README.md' );

	if ( checkReadme && fs.existsSync( readmePath ) ) {
		const readmeContent = fs.readFileSync( readmePath, 'utf-8' );
		const readmeFrontmatter = matter( readmeContent ).data;
		category = { ...readmeFrontmatter };
		category.posts = [];
	}

	const markdownFiles = glob.sync( path.join( directory, '*.md' ) );

	markdownFiles.forEach( ( filePath ) => {
		if ( filePath !== readmePath || ! checkReadme ) {
			// Skip README.md which we have already processed.
			const fileContent = fs.readFileSync( filePath, 'utf-8' );
			const fileFrontmatter = matter( fileContent ).data;
			const post: Post = { ...fileFrontmatter };
			// @ts-ignore
			category.posts.push( {
				...post,
				url: generateFileUrl(
					baseUrl,
					rootDirectory,
					directory,
					filePath
				),
				id: generatePageId( filePath, projectName ),
			} );
		}
	} );

	// Recursively process subdirectories.
	category.categories = [];
	const subdirectories = fs
		.readdirSync( directory, { withFileTypes: true } )
		.filter( ( dirent ) => dirent.isDirectory() )
		.map( ( dirent ) => path.join( directory, dirent.name ) );
	for ( const subdirectory of subdirectories ) {
		const subcategory = await processDirectory(
			subdirectory,
			rootDirectory,
			projectName,
			baseUrl
		);
		//  @ts-ignore
		category.categories.push( subcategory );
	}

	return category;
}

export async function generateManifestFromDirectory(
	directory: string,
	rootDirectory: string,
	projectName: string,
	baseUrl: string
) {
	return processDirectory(
		directory,
		rootDirectory,
		projectName,
		baseUrl,
		false
	);
}
